<?php
namespace App\Exports;

use App\Services\PayScaleUploadService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Downloadable .xlsx template for a pay scale: one row per grade, a basic
 * salary in each step column the grade has (leave the rest blank).
 *
 * Headers: grade, step-1, step-2, … step-20.
 */
class PayScaleImportTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Pay Scale';
    }

    public function headings(): array
    {
        $headers = ['grade'];
        for ($n = 1; $n <= PayScaleUploadService::MAX_STEP; $n++) {
            $headers[] = "step-{$n}";
        }

        return $headers;
    }

    /** Illustrative rows (grades with different step counts). Delete before use. */
    public function array(): array
    {
        return [
            $this->row(1,  [78000]),
            $this->row(2,  [66000, 68480, 71050, 73720, 76490]),
            $this->row(9,  [50000, 52000, 54080, 56250, 58500, 60840, 63280, 65820, 68460, 71200]),
        ];
    }

    /** grade + its step values, right-padded with blanks to MAX_STEP columns. */
    private function row(int $grade, array $steps): array
    {
        $line = [$grade];
        for ($n = 1; $n <= PayScaleUploadService::MAX_STEP; $n++) {
            $line[] = $steps[$n - 1] ?? null;
        }

        return $line;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastCol = $event->sheet->getDelegate()->getHighestColumn();
                $event->sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
            },
        ];
    }
}
