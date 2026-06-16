<?php
namespace App\Exports\Employee;

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
 * Excel/CSV export of the employee list.
 *
 * Receives an already-filtered collection of Employee rows carrying the
 * ps_name / ps_grade / ps_basic / current_balance / is_settled aliases
 * produced by EmployeeController::employeesQuery().
 */
class EmployeesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    private const DATA_START_ROW = 4;
    private const LAST_COL       = 'L';
    private int $row             = 0;

    public function __construct(private Collection $employees)
    {}

    public function collection(): Collection
    {
        return $this->employees;
    }

    public function headings(): array
    {
        return [
            '#',
            'CPF A/C No.',
            'Name',
            'Designation',
            'Mobile',
            'Joining Date',
            'Pay Scale',
            'Grade',
            'Basic Salary (BDT)',
            'Current Balance (BDT)',
            'Service Status',
            'Activation',
        ];
    }

    public function map($e): array
    {
        $this->row++;

        return [
            $this->row,
            $e->cpf_account_no,
            $e->name,
            $e->designation,
            $e->mobile_number ?: '-',
            optional($e->joining_date)->format('d-M-Y') ?? '-',
            $e->ps_name ?? '-',
            $e->ps_grade ?? '-',
            (int) $e->ps_basic,
            (int) $e->current_balance,
            $e->status?->label() ?? '-',
            (bool) $e->is_settled ? 'Settled' : ($e->is_active ? 'Active' : 'Inactive'),
        ];
    }

    public function title(): string
    {
        return 'Employees';
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

                $sheet->mergeCells('A1:' . self::LAST_COL . '1');
                $sheet->setCellValue('A1', 'Bangladesh Investment Development Authority (BIDA)');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2937']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells('A2:' . self::LAST_COL . '2');
                $sheet->setCellValue('A2', 'Employee List (Generated ' . now()->format('d-M-Y h:i A') . ')');
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['size' => 10, 'color' => ['argb' => 'FF4B5563']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->freezePane('A' . self::DATA_START_ROW);
            },
        ];
    }
}
