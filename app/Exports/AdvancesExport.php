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
 * Excel/CSV export of CPF advances.
 * Receives an already-filtered collection of CpfAdvance rows carrying the
 * emp_name / emp_acc aliases from CpfAdvanceController::advancesQuery().
 * Set $outstanding = true for the outstanding-advances layout.
 */
class AdvancesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    private const DATA_START_ROW = 4;
    private int $row             = 0;

    public function __construct(private Collection $advances, private bool $outstanding = false)
    {}

    public function collection(): Collection
    {
        return $this->advances;
    }

    public function headings(): array
    {
        if ($this->outstanding) {
            return ['#', 'Advance No', 'CPF A/C No.', 'Employee', 'Approved (BDT)', 'Outstanding (BDT)', 'Per Installment (BDT)', 'Progress (%)'];
        }

        return ['#', 'Advance No', 'CPF A/C No.', 'Employee', 'Application Date', 'Amount (BDT)', 'Interest Rate (%)', 'Installments', 'Outstanding (BDT)', 'Status'];
    }

    public function map($a): array
    {
        $this->row++;

        if ($this->outstanding) {
            return [
                $this->row,
                $a->advance_no,
                $a->emp_acc,
                $a->emp_name,
                (int) $a->approved_amount,
                (int) $a->outstanding_amount,
                (int) $a->installment_amount,
                $a->progressPercent(),
            ];
        }

        return [
            $this->row,
            $a->advance_no,
            $a->emp_acc,
            $a->emp_name,
            $a->application_date?->format('d-M-Y'),
            (int) ($a->approved_amount ?? $a->requested_amount),
            (float) $a->interest_rate,
            $a->installment_count,
            (int) $a->outstanding_amount,
            $a->status->label(),
        ];
    }

    public function title(): string
    {
        return $this->outstanding ? 'Outstanding Advances' : 'CPF Advances';
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
        $lastCol = $this->outstanding ? 'H' : 'J';
        $subtitle = $this->outstanding ? 'Outstanding CPF Advances' : 'CPF Advances — Summary';

        return [
            AfterSheet::class => function (AfterSheet $event) use ($lastCol, $subtitle) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, self::DATA_START_ROW - 1);

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'Bangladesh Investment Development Authority (BIDA)');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2937']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', $subtitle . ' (Generated ' . now()->format('d-M-Y h:i A') . ')');
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['size' => 10, 'color' => ['argb' => 'FF4B5563']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->freezePane('A' . self::DATA_START_ROW);
            },
        ];
    }
}
