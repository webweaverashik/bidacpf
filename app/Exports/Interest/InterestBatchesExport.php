<?php
namespace App\Exports\Interest;

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
 * Excel/CSV export of bank interest distribution batches.
 *
 * Receives an already-filtered collection of BankInterestBatch rows carrying
 * the distributions_count / distributed_total aggregates added by
 * BankInterestController::batchesQuery().
 */
class InterestBatchesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    private const DATA_START_ROW = 4;
    private int $row             = 0;

    public function __construct(private Collection $batches)
    {}

    public function collection(): Collection
    {
        return $this->batches;
    }

    public function headings(): array
    {
        return ['#', 'Reference', 'Cut-off Date', 'Fiscal Year', 'Interest Received (BDT)', 'Members', 'Distributed (BDT)', 'Status'];
    }

    public function map($b): array
    {
        $this->row++;

        return [
            $this->row,
            $b->reference_no,
            $b->distribution_date?->format('d-M-Y'),
            $b->fiscal_year,
            (int) $b->total_interest_amount,
            (int) ($b->distributions_count ?? 0),
            (int) ($b->distributed_total ?? 0),
            $b->status->label(),
        ];
    }

    public function title(): string
    {
        return 'Bank Interest Batches';
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

        return [
            AfterSheet::class => function (AfterSheet $event) use ($lastCol) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, self::DATA_START_ROW - 1);

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'Bangladesh Investment Development Authority (BIDA)');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2937']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', 'Bank Interest Distribution — Batches (Generated ' . now()->format('d-M-Y h:i A') . ')');
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['size' => 10, 'color' => ['argb' => 'FF4B5563']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->freezePane('A' . self::DATA_START_ROW);
            },
        ];
    }
}
