<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Downloadable .xlsx template for the employee bulk upload.
 *
 * Header keys MUST stay in sync with EmployeeUploadService::HEADERS — they are
 * the column names the importer reads (snake_cased) when validating a file.
 * Date columns use plain text (YYYY-MM-DD); the importer also accepts native
 * Excel date cells.
 */
class EmployeeImportTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Employees';
    }

    public function headings(): array
    {
        return [
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
    }

    /** Two illustrative sample rows (delete before importing real data). */
    public function array(): array
    {
        return [
            [
                'PRA/K/0001/25', 'Md. Sample Officer', 'Assistant Director',
                'sample.officer@example.com', '01700000000',
                '2015-07-01', '2045-06-30',
                9, 1,
                120000, 100000, 15000,
                '2024-06-30',
            ],
            [
                'PRA/K/0002/25', 'Ms. Sample Employee', 'Office Assistant',
                '', '01800000000',
                '2018-01-15', '',
                16, 3,
                45000, 38000, 5000,
                '2024-06-30',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Bold the header row.
                $event->sheet->getStyle('A1:M1')->getFont()->setBold(true);
            },
        ];
    }
}
