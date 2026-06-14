<?php
namespace App\Services;

use App\Models\Employee\PayScale;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Pay scale bulk-upload pipeline (grade × step grid).
 *
 * The uploaded sheet is a matrix:
 *   grade | step-1 | step-2 | … | step-20
 * one row per grade, a basic salary in each step column the grade actually
 * has (trailing columns left blank for grades with fewer steps).
 *
 * Unlike the employee upload, a pay scale is all-or-nothing: if ANY row is
 * invalid the whole upload is rejected (a pay scale must be internally
 * complete and consistent). Pay scales are immutable after creation — there
 * is no edit/step-edit path — so this is the only way grades/steps are written.
 */
class PayScaleUploadService
{
    /** Highest step column we read (step-1 … step-20). */
    public const MAX_STEP = 20;

    /** Safety cap on grades per upload. */
    public const MAX_GRADES = 60;

    /** Upper bound for a single basic salary (matches unsignedInteger column). */
    private const MAX_SALARY = 4294967295;

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    */

    /**
     * Parse + validate the uploaded grid for the preview screen.
     *
     * @return array{error: ?string, summary: array, grid: array, normalized: array}
     */
    public function preview(UploadedFile $file): array
    {
        [$stepNumbers, $rows] = $this->readGrid($file);

        if (empty($stepNumbers)) {
            return $this->fail('No step columns found. The header row must read: grade, step-1, step-2, …');
        }

        if (empty($rows)) {
            return $this->fail('The file contains no grade rows.');
        }

        if (count($rows) > self::MAX_GRADES) {
            return $this->fail('Too many grades (' . count($rows) . '). Limit is ' . self::MAX_GRADES . '.');
        }

        $evaluated = $this->evaluate($rows, $stepNumbers);

        return [
            'error'      => null,
            'summary'    => $this->summarize($evaluated),
            'grid'       => $this->toDisplayGrid($evaluated),
            'normalized' => array_map(fn($r) => ['grade' => $r['grade'], 'steps' => $r['steps']], $evaluated['rows']),
        ];
    }

    /**
     * Create the pay scale + all its steps from the cached, previewed rows.
     * Re-validates first; throws if anything is invalid (shouldn't happen).
     */
    public function commit(array $normalized, array $meta): PayScale
    {
        // Re-shape the cached rows back into the evaluator's expected input.
        $rows = array_map(function ($r) {
            return ['grade' => $r['grade'], 'steps' => $r['steps']];
        }, $normalized);

        $stepNumbers = [];
        foreach ($rows as $r) {
            foreach (array_keys($r['steps']) as $n) {
                $stepNumbers[(int) $n] = (int) $n;
            }
        }
        ksort($stepNumbers);

        $evaluated = $this->evaluate($rows, array_values($stepNumbers));

        if (! $this->summarize($evaluated)['valid']) {
            throw new \RuntimeException('The pay scale data is no longer valid. Please upload the file again.');
        }

        return DB::transaction(function () use ($evaluated, $meta) {
            $payScale = PayScale::create([
                'name'           => $meta['name'],
                'effective_year' => $meta['effective_year'],
                'effective_from' => $meta['effective_from'],
                'effective_to'   => $meta['effective_to'] ?? null,
                'total_grades'   => count($evaluated['rows']),
                'is_active'      => false, // activated below only when requested
            ]);

            // Bulk-insert steps (no per-row model events → no activity-log flood;
            // the single "Pay Scale was created" entry is the audit record).
            $now  = now();
            $bulk = [];
            foreach ($evaluated['rows'] as $row) {
                foreach ($row['steps'] as $stepNo => $salary) {
                    $bulk[] = [
                        'pay_scale_id' => $payScale->id,
                        'grade'        => $row['grade'],
                        'step'         => (int) $stepNo,
                        'basic_salary' => (int) $salary,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ];
                }
            }
            foreach (array_chunk($bulk, 500) as $chunk) {
                DB::table('pay_scale_steps')->insert($chunk);
            }

            // Enforce "at most one active pay scale".
            if (! empty($meta['is_active'])) {
                PayScale::where('id', '!=', $payScale->id)->where('is_active', true)->update(['is_active' => false]);
                $payScale->update(['is_active' => true]);
            }

            return $payScale;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Reading
    |--------------------------------------------------------------------------
    */

    /**
     * Read the grid into [stepNumbers, rows].
     *   stepNumbers: sorted list of step indexes present (e.g. [1,2,3,4,5]).
     *   rows: [ ['grade' => rawGrade, 'steps' => [stepNo => rawCell, …]], … ]
     *
     * @return array{0: int[], 1: array<int, array>}
     */
    private function readGrid(UploadedFile $file): array
    {
        $path = $file->getRealPath() ?: $file->getPathname();
        if (! $path || ! is_file($path)) {
            throw new \RuntimeException('The uploaded file could not be located on the server.');
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        $readerType = match ($ext) {
            'xls' => 'Xls',
            'csv' => 'Csv',
            default => 'Xlsx',
        };

        $reader = IOFactory::createReader($readerType);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $spreadsheet = $reader->load($path);
        $raw = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (empty($raw)) {
            return [[], []];
        }

        // Map header columns → meaning.
        $header = array_map(fn($h) => $this->normalizeHeader($h), array_shift($raw));

        $gradeCol    = null;
        $stepColumns = []; // colIndex => stepNo
        foreach ($header as $i => $key) {
            if ($key === 'grade') {
                $gradeCol = $i;
            } elseif (preg_match('/^step_?(\d+)$/', $key, $m)) {
                $n = (int) $m[1];
                if ($n >= 1 && $n <= self::MAX_STEP) {
                    $stepColumns[$i] = $n;
                }
            }
        }

        if ($gradeCol === null || empty($stepColumns)) {
            return [[], []];
        }

        $stepNumbers = array_values($stepColumns);
        sort($stepNumbers);

        $rows = [];
        foreach ($raw as $line) {
            $grade = $line[$gradeCol] ?? null;

            $steps = [];
            foreach ($stepColumns as $colIndex => $stepNo) {
                $steps[$stepNo] = $line[$colIndex] ?? null;
            }

            // Skip completely blank rows.
            $hasContent = ($grade !== null && trim((string) $grade) !== '')
                || count(array_filter($steps, fn($v) => $v !== null && trim((string) $v) !== '')) > 0;

            if ($hasContent) {
                $rows[] = ['grade' => $grade, 'steps' => $steps];
            }
        }

        return [$stepNumbers, $rows];
    }

    /** Header cell → snake_case key ("Step-1" → "step_1"). */
    private function normalizeHeader($header): string
    {
        if ($header === null || trim((string) $header) === '') {
            return '';
        }

        return Str::slug(trim((string) $header), '_');
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    /**
     * Validate every grade row.
     *
     * @return array{rows: array, max_step: int, global_errors: array}
     */
    private function evaluate(array $rows, array $stepNumbers): array
    {
        $seenGrades = [];
        $maxStep    = 0;
        $result     = [];

        foreach ($rows as $index => $row) {
            $rowNo  = $index + 2; // +1 header, +1 one-based
            $errors = [];

            // ── grade ───────────────────────────────────────────────────
            $grade = $this->parseInt($row['grade'] ?? null);
            if ($grade === null) {
                $errors[] = 'Grade is required and must be a whole number.';
            } elseif ($grade < 1 || $grade > 99) {
                $errors[] = 'Grade must be between 1 and 99.';
            } elseif (isset($seenGrades[$grade])) {
                $errors[] = "Duplicate grade (also on row {$seenGrades[$grade]}).";
            } else {
                $seenGrades[$grade] = $rowNo;
            }

            // ── steps ───────────────────────────────────────────────────
            $steps = [];
            foreach ($stepNumbers as $stepNo) {
                $cell = $row['steps'][$stepNo] ?? null;
                if ($cell === null || trim((string) $cell) === '') {
                    continue; // grade simply doesn't have this step
                }

                $salary = $this->parseInt($cell);
                if ($salary === null) {
                    $errors[] = "Step-{$stepNo} is not a valid amount.";
                } elseif ($salary < 1) {
                    $errors[] = "Step-{$stepNo} must be greater than zero.";
                } elseif ($salary > self::MAX_SALARY) {
                    $errors[] = "Step-{$stepNo} amount is too large.";
                } else {
                    $steps[$stepNo] = $salary;
                }
            }

            if (empty($steps)) {
                $errors[] = 'Grade has no step amounts.';
            } else {
                $maxStep = max($maxStep, max(array_keys($steps)));
            }

            $result[] = [
                'row_no' => $rowNo,
                'grade'  => $grade,
                'steps'  => $steps,
                'errors' => $errors,
                'valid'  => empty($errors),
                'min'    => $steps ? min($steps) : null,
                'max'    => $steps ? max($steps) : null,
            ];
        }

        return ['rows' => $result, 'max_step' => $maxStep, 'global_errors' => []];
    }

    /** Whole-upload totals + validity (all-or-nothing). */
    private function summarize(array $evaluated): array
    {
        $rows       = $evaluated['rows'];
        $validRows  = array_filter($rows, fn($r) => $r['valid']);
        $totalSteps = array_sum(array_map(fn($r) => count($r['steps']), $rows));

        return [
            'grades'  => count($rows),
            'steps'   => $totalSteps,
            'invalid' => count($rows) - count($validRows),
            'valid'   => count($rows) > 0 && count($validRows) === count($rows),
        ];
    }

    /** Shape the evaluated rows for the preview grid (formatted). */
    private function toDisplayGrid(array $evaluated): array
    {
        $maxStep = $evaluated['max_step'];

        $rows = array_map(function ($r) use ($maxStep) {
            $cells = [];
            for ($n = 1; $n <= $maxStep; $n++) {
                $cells[(string) $n] = isset($r['steps'][$n]) ? number_format($r['steps'][$n]) : null;
            }

            return [
                'grade'  => $r['grade'],
                'range'  => $this->rangeLabel($r['min'], $r['max']),
                'cells'  => $cells,
                'valid'  => $r['valid'],
                'errors' => $r['errors'],
            ];
        }, $evaluated['rows']);

        return ['max_step' => $maxStep, 'rows' => $rows];
    }

    private function rangeLabel(?int $min, ?int $max): string
    {
        if ($min === null) {
            return '—';
        }

        return $min === $max
            ? number_format($min) . ' (fixed)'
            : number_format($min) . ' – ' . number_format($max);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function fail(string $message): array
    {
        return ['error' => $message, 'summary' => [], 'grid' => [], 'normalized' => []];
    }

    /** Parse an integer cell (allows commas / decimals like 45,610 or 45610.0). */
    private function parseInt($value): ?int
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        $clean = is_string($value) ? str_replace([',', ' '], '', $value) : $value;

        if (! is_numeric($clean)) {
            return null;
        }

        return (int) round((float) $clean);
    }
}
