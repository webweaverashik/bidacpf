<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel/CSV export of the global CPF ledger transaction log.
 * Receives an already-filtered collection of CpfLedger rows (with the
 * emp_name / emp_acc aliases from transactionsQuery()).
 */
class LedgerTransactionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    private const DATA_START_ROW = 4;
    private int $row             = 0;

    public function __construct(private Collection $rows)
    {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['#', 'Date', 'Employee', 'CPF A/C No.', 'Type', 'Reference', 'Debit (BDT)', 'Credit (BDT)', 'Balance (BDT)', 'Remarks'];
    }

    public function map($t): array
    {
        $this->row++;

        return [
            $this->row,
            optional($t->transaction_date)->format('d-M-Y'),
            $t->emp_name,
            $t->emp_acc,
            $t->transaction_type?->label() ?? '',
            $t->reference_no ?: '',
            $t->debit > 0 ? $t->debit : '',
            $t->credit > 0 ? $t->credit : '',
            $t->balance,
            $t->remarks ?: '',
        ];
    }

    public function title(): string
    {
        return 'Ledger Transactions';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            self::DATA_START_ROW - 1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF1F2937']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, self::DATA_START_ROW - 1);

                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', 'Bangladesh Investment Development Authority (BIDA)');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2937']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells('A2:J2');
                $sheet->setCellValue('A2', 'CPF Ledger — Transaction Log (Generated ' . now()->format('d-M-Y h:i A') . ')');
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['size' => 10, 'color' => ['argb' => 'FF4B5563']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->freezePane('A' . self::DATA_START_ROW);
            },
        ];
    }
}
