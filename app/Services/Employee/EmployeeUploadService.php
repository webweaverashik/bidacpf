<?php
namespace App\Services\Employee;

use App\Enums\LedgerTransactionType;
use App\Enums\SourceType;
use App\Models\Cpf\CpfOpeningBalance;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeSalaryHistory;
use App\Models\Employee\PayScaleStep;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Employee bulk-upload pipeline.
 *
 * One pipeline, two entry points so the preview and the commit can never drift:
 *   - preview() parses the uploaded file → normalised rows → evaluate().
 *   - commit()  takes the cached normalised rows → evaluate() → inserts the
 *               rows that pass.
 *
 * Each imported row mirrors EmployeeController@store exactly:
 *   employee → initial salary-history row → CPF opening balance →
 *   single OPENING_BALANCE ledger credit posted through LedgerService.
 *
 * Photos are intentionally out of scope for bulk upload.
 */
class EmployeeUploadService
{
    /** Expected column headers (must match EmployeeImportTemplateExport). */
    public const HEADERS = [
        'cpf_account_no',
        'name',
        'designation',
        'email',
        'mobile_number',
        'joining_date',
        'retirement_date',
        'grade',
        'step',
        'opening_employee_contribution',
        'opening_government_contribution',
        'opening_bank_interest',
        'opening_effective_date',
    ];

    /** Hard cap to keep a single upload sane. */
    public const MAX_ROWS = 1000;

    public function __construct(private LedgerService $ledgerService)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    */

    /**
     * Parse + validate an uploaded file for the preview screen.
     *
     * @return array{summary: array, rows: array, normalized: array, error: ?string}
     */
    public function preview(UploadedFile $file, int $payScaleId): array
    {
        $normalized = collect($this->readRows($file))
            ->map(fn($row) => $this->normalizeRow($row))
            ->filter(fn($row) => $this->rowHasContent($row))
            ->values()
            ->all();

        if (count($normalized) === 0) {
            return ['error' => 'The file contains no data rows.', 'summary' => [], 'rows' => [], 'normalized' => []];
        }

        if (count($normalized) > self::MAX_ROWS) {
            return [
                'error'   => 'Too many rows (' . count($normalized) . '). Limit is ' . self::MAX_ROWS . ' per upload.',
                'summary' => [], 'rows' => [], 'normalized' => [],
            ];
        }

        $evaluated = $this->evaluate($normalized, $payScaleId);

        return [
            'error'      => null,
            'summary'    => $this->summarize($evaluated),
            'rows'       => array_map([$this, 'toDisplayRow'], $evaluated),
            'normalized' => $normalized,
        ];
    }

    /**
     * Re-validate the cached rows and import everything that passes, inside a
     * single transaction.
     *
     * @return array{imported: int, skipped: int}
     */
    public function commit(array $normalized, int $payScaleId, int $userId): array
    {
        $evaluated = $this->evaluate($normalized, $payScaleId);

        $valid = array_filter($evaluated, fn($r) => $r['valid']);

        DB::transaction(function () use ($valid) {
            foreach ($valid as $row) {
                $this->importRow($row['resolved']);
            }
        });

        return [
            'imported' => count($valid),
            'skipped'  => count($evaluated) - count($valid),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Normalisation
    |--------------------------------------------------------------------------
    */

    /**
     * Read the uploaded spreadsheet into heading-keyed rows.
     *
     * Reads with PhpSpreadsheet directly (rather than going through the Excel
     * facade) because an uploaded file's temp path has no extension, which
     * trips up reader/path resolution ("Path cannot be empty"). The reader is
     * chosen explicitly from the upload's client extension instead.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readRows(UploadedFile $file): array
    {
        $path = $file->getRealPath() ?: $file->getPathname();

        if (! $path || ! is_file($path)) {
            throw new \RuntimeException('The uploaded file could not be located on the server.');
        }

        $ext        = strtolower((string) $file->getClientOriginalExtension());
        $readerType = match ($ext) {
            'xls'   => 'Xls',
            'csv'   => 'Csv',
            default => 'Xlsx',
        };

        $reader = IOFactory::createReader($readerType);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true); // not supported by the CSV reader
        }

        $spreadsheet = $reader->load($path);
        // Unformatted values: date cells come back as Excel serials (parseDate
        // handles them) and numbers without thousands separators.
        $raw = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (empty($raw)) {
            return [];
        }

        // First row is the header; map every subsequent row onto those keys.
        $headers = array_map(fn($h) => $this->normalizeHeader($h), array_shift($raw));

        $rows = [];
        foreach ($raw as $line) {
            $assoc = [];
            foreach ($headers as $i => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = $line[$i] ?? null;
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    /** Header cell → snake_case key (e.g. "CPF Account No" → "cpf_account_no"). */
    private function normalizeHeader($header): string
    {
        if ($header === null || trim((string) $header) === '') {
            return '';
        }

        return Str::slug(trim((string) $header), '_');
    }

    /** Pull the expected columns out of a heading-keyed row, trimmed. */
    private function normalizeRow(array $raw): array
    {
        $out = [];
        foreach (self::HEADERS as $key) {
            $value     = $raw[$key] ?? null;
            $out[$key] = is_string($value) ? trim($value) : $value;
        }

        return $out;
    }

    /** A row is "content" if any expected column is non-empty. */
    private function rowHasContent(array $row): bool
    {
        foreach ($row as $v) {
            if ($v !== null && $v !== '') {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    /**
     * Validate every normalised row against the DB + the selected pay scale,
     * resolving grade/step → pay_scale_step_id and detecting in-file dupes.
     *
     * @return array<int, array{row_no:int, data:array, errors:array, resolved:?array, valid:bool}>
     */
    private function evaluate(array $rows, int $payScaleId): array
    {
        // Pre-fetch existing identifiers + the scale's steps (no per-row queries).
        $existingCpf = Employee::withTrashed()
            ->pluck('cpf_account_no')
            ->map(fn($v) => mb_strtolower(trim((string) $v)))
            ->flip();

        $existingEmail = Employee::withTrashed()
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn($v) => mb_strtolower(trim((string) $v)))
            ->flip();

        $stepMap = PayScaleStep::where('pay_scale_id', $payScaleId)
            ->get(['id', 'grade', 'step', 'basic_salary'])
            ->mapWithKeys(fn($s) => ["{$s->grade}-{$s->step}" => ['id' => $s->id, 'basic_salary' => (int) $s->basic_salary]])
            ->all();

        $seenCpf   = [];
        $seenEmail = [];
        $result    = [];

        foreach ($rows as $index => $row) {
            $rowNo  = $index + 2; // +1 for heading row, +1 for 1-based.
            $errors = [];

            // ── CPF account number ──────────────────────────────────────
            $cpf = (string) ($row['cpf_account_no'] ?? '');
            if ($cpf === '') {
                $errors[] = 'CPF account number is required.';
            } else {
                if (mb_strlen($cpf) > 50) {
                    $errors[] = 'CPF account number exceeds 50 characters.';
                }
                $cpfKey = mb_strtolower($cpf);
                if (isset($existingCpf[$cpfKey])) {
                    $errors[] = 'CPF account number already exists.';
                } elseif (isset($seenCpf[$cpfKey])) {
                    $errors[] = 'Duplicate CPF account number within the file (row ' . $seenCpf[$cpfKey] . ').';
                } else {
                    $seenCpf[$cpfKey] = $rowNo;
                }
            }

            // ── Name / designation ──────────────────────────────────────
            if (($row['name'] ?? '') === '') {
                $errors[] = 'Name is required.';
            }
            if (($row['designation'] ?? '') === '') {
                $errors[] = 'Designation is required.';
            }

            // ── Email (optional, unique) ────────────────────────────────
            $email = (string) ($row['email'] ?? '');
            if ($email !== '') {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Email is not a valid address.';
                } else {
                    $emailKey = mb_strtolower($email);
                    if (isset($existingEmail[$emailKey])) {
                        $errors[] = 'Email already exists.';
                    } elseif (isset($seenEmail[$emailKey])) {
                        $errors[] = 'Duplicate email within the file (row ' . $seenEmail[$emailKey] . ').';
                    } else {
                        $seenEmail[$emailKey] = $rowNo;
                    }
                }
            }

            // ── Mobile (optional) ───────────────────────────────────────
            if (mb_strlen((string) ($row['mobile_number'] ?? '')) > 20) {
                $errors[] = 'Mobile number exceeds 20 characters.';
            }

            // ── Dates ───────────────────────────────────────────────────
            $joining    = $this->parseDate($row['joining_date'] ?? null);
            $retirement = $this->parseDate($row['retirement_date'] ?? null);
            $openingEff = $this->parseDate($row['opening_effective_date'] ?? null);

            if ($joining['empty']) {
                $errors[] = 'Joining date is required.';
            } elseif (! $joining['ok']) {
                $errors[] = 'Joining date is not a valid date.';
            }

            if (! $retirement['empty'] && ! $retirement['ok']) {
                $errors[] = 'Retirement date is not a valid date.';
            } elseif ($joining['ok'] && $retirement['ok'] && ! $retirement['empty']
                && Carbon::parse($retirement['value'])->lte(Carbon::parse($joining['value']))) {
                $errors[] = 'Retirement date must be after the joining date.';
            }

            if ($openingEff['empty']) {
                $errors[] = 'Opening effective date is required.';
            } elseif (! $openingEff['ok']) {
                $errors[] = 'Opening effective date is not a valid date.';
            }

            // ── Grade / step → pay scale step ───────────────────────────
            $grade       = $this->parseInt($row['grade'] ?? null);
            $step        = $this->parseInt($row['step'] ?? null);
            $stepId      = null;
            $basicSalary = null;

            if ($grade === null) {
                $errors[] = 'Grade is required and must be a whole number.';
            }
            if ($step === null) {
                $errors[] = 'Step is required and must be a whole number.';
            }
            if ($grade !== null && $step !== null) {
                $match = $stepMap["{$grade}-{$step}"] ?? null;
                if ($match === null) {
                    $errors[] = "Grade {$grade} / Step {$step} does not exist in the selected pay scale.";
                } else {
                    $stepId      = $match['id'];
                    $basicSalary = $match['basic_salary'];
                }
            }

            // ── Opening contributions ───────────────────────────────────
            $self     = $this->parseInt($row['opening_employee_contribution'] ?? null);
            $govt     = $this->parseInt($row['opening_government_contribution'] ?? null);
            $interest = $this->parseInt($row['opening_bank_interest'] ?? null);

            foreach ([
                'Employee contribution (opening)'   => $self,
                'Government contribution (opening)' => $govt,
                'Bank interest (opening)'           => $interest,
            ] as $label => $val) {
                if ($val === null) {
                    $errors[] = "{$label} is required and must be a whole number.";
                } elseif ($val < 0) {
                    $errors[] = "{$label} cannot be negative.";
                }
            }

            $valid    = empty($errors);
            $resolved = null;

            if ($valid) {
                $net      = (int) $self + (int) $govt + (int) $interest;
                $resolved = [
                    'cpf_account_no'                  => $cpf,
                    'name'                            => $row['name'],
                    'designation'                     => $row['designation'],
                    'email'                           => $email !== '' ? $email : null,
                    'mobile_number'                   => ($row['mobile_number'] ?? '') !== '' ? $row['mobile_number'] : null,
                    'joining_date'                    => $joining['value'],
                    'retirement_date'                 => $retirement['empty'] ? null : $retirement['value'],
                    'pay_scale_step_id'               => $stepId,
                    'opening_employee_contribution'   => (int) $self,
                    'opening_government_contribution' => (int) $govt,
                    'opening_bank_interest'           => (int) $interest,
                    'opening_effective_date'          => $openingEff['value'],
                    'net_balance'                     => $net,
                ];
            }

            $result[] = [
                'row_no'       => $rowNo,
                'data'         => $row,
                'errors'       => $errors,
                'resolved'     => $resolved,
                'valid'        => $valid,
                'grade'        => $grade,
                'step'         => $step,
                'basic_salary' => $basicSalary,
            ];
        }

        return $result;
    }

    /** Per-file totals for the summary cards. */
    private function summarize(array $evaluated): array
    {
        $valid = count(array_filter($evaluated, fn($r) => $r['valid']));

        return [
            'total'   => count($evaluated),
            'valid'   => $valid,
            'invalid' => count($evaluated) - $valid,
        ];
    }

    /** Shape a row for the preview table. */
    private function toDisplayRow(array $r): array
    {
        $d   = $r['data'];
        $net = ($r['resolved']['net_balance'] ?? null);

        return [
            'row_no'         => $r['row_no'],
            'cpf_account_no' => (string) ($d['cpf_account_no'] ?? ''),
            'name'           => (string) ($d['name'] ?? ''),
            'designation'    => (string) ($d['designation'] ?? ''),
            'grade'          => $r['grade'],
            'step'           => $r['step'],
            'basic_salary'   => $r['basic_salary'] !== null ? number_format($r['basic_salary']) : '—',
            'joining_date'   => (string) ($d['joining_date'] ?? ''),
            'self'           => (string) ($d['opening_employee_contribution'] ?? ''),
            'govt'           => (string) ($d['opening_government_contribution'] ?? ''),
            'interest'       => (string) ($d['opening_bank_interest'] ?? ''),
            'net'            => $net !== null ? number_format($net) : '—',
            'valid'          => $r['valid'],
            'errors'         => $r['errors'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Import (mirrors EmployeeController@store)
    |--------------------------------------------------------------------------
    */

    private function importRow(array $r): void
    {
        $employee = Employee::create([
            'cpf_account_no'    => $r['cpf_account_no'],
            'name'              => $r['name'],
            'designation'       => $r['designation'],
            'email'             => $r['email'],
            'mobile_number'     => $r['mobile_number'],
            'joining_date'      => $r['joining_date'],
            'retirement_date'   => $r['retirement_date'],
            'pay_scale_step_id' => $r['pay_scale_step_id'],
        ]);

        // Initial salary-history entry.
        EmployeeSalaryHistory::create([
            'employee_id'       => $employee->id,
            'pay_scale_step_id' => $employee->pay_scale_step_id,
            'effective_date'    => $employee->created_at->toDateString(),
            'change_type'       => 'initial',
            'remarks'           => 'Initial pay scale step on employee creation (bulk upload).',
        ]);

        // CPF opening balance snapshot.
        $net = $r['net_balance'];

        $openingBalance = CpfOpeningBalance::create([
            'employee_id'             => $employee->id,
            'effective_date'          => $r['opening_effective_date'],
            'self_contribution'       => $r['opening_employee_contribution'],
            'government_contribution' => $r['opening_government_contribution'],
            'interest_amount'         => $r['opening_bank_interest'],
            'outstanding_advance'     => 0,
            'net_balance'             => $net,
            'remarks'                 => 'Opening balance captured at employee onboarding (bulk upload).',
        ]);

        // Single OPENING_BALANCE credit through the ledger service.
        $this->ledgerService->create([
            'employee_id'      => $employee->id,
            'transaction_date' => $openingBalance->effective_date,
            'transaction_type' => LedgerTransactionType::OPENING_BALANCE,
            'source_type'      => SourceType::OPENING_BALANCE->value,
            'source_id'        => $openingBalance->id,
            'reference_no'     => null,
            'remarks'          => 'Opening balance',
            'credit'           => $net,
            'debit'            => 0,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Parsing helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Parse a date cell. Accepts native Excel serials and common string forms.
     *
     * @return array{ok:bool, empty:bool, value:?string}  value is Y-m-d when ok.
     */
    private function parseDate($value): array
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return ['ok' => false, 'empty' => true, 'value' => null];
        }

        // Native Excel date serial (numeric).
        if (is_numeric($value)) {
            try {
                return ['ok' => true, 'empty' => false, 'value' => ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d')];
            } catch (\Throwable $e) {
                return ['ok' => false, 'empty' => false, 'value' => null];
            }
        }

        try {
            return ['ok' => true, 'empty' => false, 'value' => Carbon::parse(trim((string) $value))->format('Y-m-d')];
        } catch (\Throwable $e) {
            return ['ok' => false, 'empty' => false, 'value' => null];
        }
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
