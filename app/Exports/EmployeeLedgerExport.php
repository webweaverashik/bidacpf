<?php
namespace App\Exports;

use App\Models\Employee\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel export of an employee's CPF ledger.
 *
 * Receives an ALREADY-FILTERED collection of CpfLedger rows from the
 * controller, so the workbook always matches what the user filtered/searched
 * on screen.
 *
 * Layout
 * ------
 * Row 1  : Organisation name (merged, centred, bold)
 * Row 2  : Report title (merged, centred)
 * Row 3  : (blank spacer)
 * Row 4  : Employee meta – Name / CPF Account No.
 * Row 5  : Employee meta – Designation / Grade & Step
 * Row 6  : Employee meta – Basic Salary / Generated At
 * Row 7  : (blank spacer)
 * Row 8  : Column headings (bold, shaded)
 * Row 9+ : Data rows
 */
class EmployeeLedgerExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    /** Data starts at this row (1-indexed). */
    private const DATA_START_ROW = 9;

    /** Number of columns in the data table. */
    private const COL_COUNT = 10;

    private int $row = 0;

    /**
     * @param \Illuminate\Support\Collection $ledgers  Pre-filtered CpfLedger rows.
     * @param \App\Models\Employee\Employee  $employee The owning employee.
     */
    public function __construct(private Collection $ledgers, private Employee $employee) {}

    // -------------------------------------------------------------------------
    // Data
    // -------------------------------------------------------------------------

    public function collection(): Collection
    {
        return $this->ledgers;
    }

    public function headings(): array
    {
        return ['#', 'Date', 'Type', 'Source', 'Reference', 'Basic Salary (BDT)', 'Debit (BDT)', 'Credit (BDT)', 'Balance (BDT)', 'Remarks', 'Recorded By'];
    }

    /**
     * @param \App\Models\Cpf\CpfLedger $ledger
     */
    public function map($ledger): array
    {
        $this->row++;

        return [$this->row, optional($ledger->transaction_date)->format('d-M-Y'), Str::headline($ledger->transaction_type?->value ?? ''), $ledger->source_label ?: '', $ledger->reference_no ?: '', $this->employee->current_basic_salary ?? '', $ledger->debit > 0 ? $ledger->debit : '', $ledger->credit > 0 ? $ledger->credit : '', $ledger->balance, $ledger->remarks ?: '', $ledger->creator?->name ?? 'System'];
    }

    // -------------------------------------------------------------------------
    // Meta
    // -------------------------------------------------------------------------

    public function title(): string
    {
        return 'CPF Ledger';
    }

    // -------------------------------------------------------------------------
    // Styling (column headings row only – AfterSheet handles the rest)
    // -------------------------------------------------------------------------

    public function styles(Worksheet $sheet): array
    {
        // Column-heading row (row 8).
        return [
            self::DATA_START_ROW - 1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FF1F2937']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // AfterSheet – inject the header block above the data table
    // -------------------------------------------------------------------------

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $this->colLetter(self::COL_COUNT); // J

                // Insert 8 blank rows at the top so the data table starts at row 9.
                $sheet->insertNewRowBefore(1, self::DATA_START_ROW - 1);

                // ── Row 1: Organisation ──────────────────────────────────────
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'Bangladesh Investment Development Authority (BIDA)');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2937']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── Row 2: Report title ──────────────────────────────────────
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', 'Contributory Provident Fund — Employee Ledger');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 11, 'color' => ['argb' => 'FF4B5563']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── Row 3: Spacer ────────────────────────────────────────────
                // (left blank)

                // ── Rows 4-6: Employee meta (label | value | label | value) ──
                $metaRows = [
                    4 => ['Name:', $this->employee->name, 'CPF Account No.:', $this->employee->cpf_account_no],
                    5 => ['Designation:', $this->employee->designation, 'Grade / Step:', $this->employee->payScaleStep ? 'Grade ' . $this->employee->grade . ' / Step ' . $this->employee->current_step : '-'],
                    6 => ['Basic Salary:', $this->employee->current_basic_salary ? 'BDT ' . number_format($this->employee->current_basic_salary) : '-', 'Generated:', now()->format('d-M-Y h:i A')],
                ];

                foreach ($metaRows as $rowNum => $cells) {
                    // Merge A–B for label, C–D for value, E–F for label, G–J for value
                    $sheet->mergeCells("A{$rowNum}:B{$rowNum}");
                    $sheet->mergeCells("C{$rowNum}:D{$rowNum}");
                    $sheet->mergeCells("E{$rowNum}:F{$rowNum}");
                    $sheet->mergeCells("G{$rowNum}:{$lastCol}{$rowNum}");

                    $sheet->setCellValue("A{$rowNum}", $cells[0]);
                    $sheet->setCellValue("C{$rowNum}", $cells[1]);
                    $sheet->setCellValue("E{$rowNum}", $cells[2]);
                    $sheet->setCellValue("G{$rowNum}", $cells[3]);

                    // Style labels (A, E) as muted; values (C, G) as bold.
                    foreach (['A', 'E'] as $lCol) {
                        $sheet->getStyle("{$lCol}{$rowNum}")->applyFromArray([
                            'font' => ['color' => ['argb' => 'FF6B7280']],
                        ]);
                    }
                    foreach (['C', 'G'] as $vCol) {
                        $sheet->getStyle("{$vCol}{$rowNum}")->applyFromArray([
                            'font' => ['bold' => true],
                        ]);
                    }
                }

                // ── Row 7: Spacer ────────────────────────────────────────────
                // (left blank)

                // ── Freeze pane below the heading row ────────────────────────
                $dataRow = self::DATA_START_ROW;
                $sheet->freezePane("A{$dataRow}");
            },
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Convert a 1-based column index to its letter (1→A, 10→J, 26→Z, 27→AA).
     */
    private function colLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - 1, 26);
        }
        return $letter;
    }
}
