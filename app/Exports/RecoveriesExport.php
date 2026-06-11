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
 * Excel/CSV export of CPF advance recoveries.
 * Receives an already-filtered collection of CpfAdvanceRecovery rows carrying
 * the adv_no / emp_name / emp_acc aliases from recoveriesQuery().
 */
class RecoveriesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    private const DATA_START_ROW = 4;
    private int $row             = 0;

    public function __construct(private Collection $recoveries)
    {}

    public function collection(): Collection
    {
        return $this->recoveries;
    }

    public function headings(): array
    {
        return ['#', 'Recovery No', 'Advance No', 'CPF A/C No.', 'Employee', 'Recovery Date', 'Amount (BDT)', 'Deposit Ref', 'Bank', 'Status'];
    }

    public function map($r): array
    {
        $this->row++;

        return [
            $this->row,
            $r->recovery_no,
            $r->adv_no,
            $r->emp_acc,
            $r->emp_name,
            $r->recovery_date?->format('d-M-Y'),
            (int) $r->amount,
            $r->deposit_reference ?? '-',
            $r->bank_name ?? '-',
            $r->status->label(),
        ];
    }

    public function title(): string
    {
        return 'CPF Recoveries';
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
                $sheet->setCellValue('A2', 'CPF Advance Recoveries (Generated ' . now()->format('d-M-Y h:i A') . ')');
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['size' => 10, 'color' => ['argb' => 'FF4B5563']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->freezePane('A' . self::DATA_START_ROW);
            },
        ];
    }
}
