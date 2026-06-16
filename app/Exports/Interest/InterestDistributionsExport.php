<?php
namespace App\Exports\Interest;

use App\Models\Interest\BankInterestBatch;
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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel/CSV export of a single batch's per-member interest distribution.
 *
 * Receives the batch (for the header band) and an already-filtered collection
 * of distribution rows carrying emp_name / emp_designation / emp_acc from the
 * controller's join.
 */
class InterestDistributionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    private const DATA_START_ROW = 6; // 5 header band rows + heading row
    private int $row             = 0;

    public function __construct(private BankInterestBatch $batch, private Collection $rows)
    {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['#', 'CPF A/C No', 'Member', 'Designation', 'Balance @ Cut-off (BDT)', 'Ratio (%)', 'Calculated (BDT)', 'Allocated (BDT)'];
    }

    public function map($d): array
    {
        $this->row++;

        return [
            $this->row,
            $d->emp_acc,
            $d->emp_name,
            $d->emp_designation,
            (int) $d->eligible_balance,
            round($d->ratio * 100, 4),
            round($d->calculated_interest, 2),
            (int) $d->interest_amount,
        ];
    }

    public function title(): string
    {
        return 'Interest Distribution';
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
        $lastCol = 'H';
        $batch   = $this->batch;
        $rows    = $this->rows;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($lastCol, $batch, $rows) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, self::DATA_START_ROW - 1);

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'Bangladesh Investment Development Authority (BIDA)');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2937']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', 'Bank Interest Distribution — ' . $batch->reference_no);
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF374151']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A3:{$lastCol}3");
                $sheet->setCellValue('A3', sprintf(
                    'Cut-off %s  ·  FY %s  ·  Interest Received: %s BDT  ·  Distributed: %s BDT  ·  Status: %s',
                    $batch->distribution_date->format('d-M-Y'),
                    $batch->fiscal_year,
                    number_format((int) $batch->total_interest_amount),
                    number_format($batch->totalDistributed()),
                    $batch->status->label()
                ));
                $sheet->getStyle('A3')->applyFromArray([
                    'font'      => ['size' => 10, 'color' => ['argb' => 'FF4B5563']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A4:{$lastCol}4");
                $sheet->setCellValue('A4', 'Generated ' . now()->format('d-M-Y h:i A'));
                $sheet->getStyle('A4')->applyFromArray([
                    'font'      => ['size' => 9, 'italic' => true, 'color' => ['argb' => 'FF6B7280']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->freezePane('A' . self::DATA_START_ROW);

                // ----- Totals row (sum of the exported/filtered rows) -------
                if ($rows->isNotEmpty()) {
                    $sumBalance   = (int) $rows->sum(fn($d) => (int) $d->eligible_balance);
                    $sumAllocated = (int) $rows->sum(fn($d) => (int) $d->interest_amount);
                    $ratioTotal   = $batch->total_eligible_balance > 0
                        ? $sumBalance / (int) $batch->total_eligible_balance * 100
                        : 0;

                    // getHighestRow() now points at the last data row (band rows
                    // are above it), so the total sits directly beneath the data.
                    $totalRow = $sheet->getHighestRow() + 1;

                    $sheet->mergeCells("A{$totalRow}:D{$totalRow}");
                    $sheet->setCellValue("A{$totalRow}", 'Total');
                    $sheet->setCellValue("E{$totalRow}", $sumBalance);
                    $sheet->setCellValue("F{$totalRow}", round($ratioTotal, 4));
                    $sheet->setCellValue("H{$totalRow}", $sumAllocated);

                    $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
                        'font'    => ['bold' => true, 'color' => ['argb' => 'FF1F2937']],
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
                    ]);
                    $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("E{$totalRow}:{$lastCol}{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }
}
