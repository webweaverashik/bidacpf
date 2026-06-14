<?php
namespace App\Exports\Reports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Generic Excel/CSV exporter for any tabular report produced by ReportService.
 *
 * It consumes the standard report envelope — title, subtitle, headings, rows,
 * (optional) meta and summary — and renders a workbook that matches the
 * system-wide BIDA header convention (org name, report title, generated stamp,
 * shaded heading row, frozen pane). One class serves every summary report, so
 * adding a report never means adding an export class.
 */
class ReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    private int $headerRows;
    private int $colCount;

    /**
     * @param array $report The ReportService envelope.
     */
    public function __construct(private array $report)
    {
        $this->colCount = max(1, count($report['headings'] ?? []));
        // Org + title + generated + (meta lines) + spacer
        $metaLines        = count($report['meta'] ?? []);
        $this->headerRows = 3 + ($metaLines > 0 ? $metaLines + 1 : 0) + 1; // +1 spacer before headings
    }

    public function collection(): Collection
    {
        return collect($this->report['rows'] ?? []);
    }

    public function headings(): array
    {
        return $this->report['headings'] ?? [];
    }

    public function title(): string
    {
        return mb_substr($this->report['title'] ?? 'Report', 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        // Heading row sits just below the injected header block.
        $headingRow = $this->headerRows + 1;

        return [
            $headingRow => [
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
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $this->colLetter($this->colCount);

                // Inject the header block above the heading row.
                $sheet->insertNewRowBefore(1, $this->headerRows);

                // Row 1 — Organisation
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'Bangladesh Investment Development Authority (BIDA)');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2937']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Row 2 — Report title
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', $this->report['title'] ?? 'Report');
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['size' => 11, 'color' => ['argb' => 'FF374151']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Row 3 — Subtitle + generated stamp
                $sheet->mergeCells("A3:{$lastCol}3");
                $sheet->setCellValue('A3', ($this->report['subtitle'] ?? '') . '  ·  Generated ' . now()->format('d-M-Y h:i A'));
                $sheet->getStyle('A3')->applyFromArray([
                    'font'      => ['size' => 9, 'color' => ['argb' => 'FF6B7280']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Optional meta lines (label: value)
                $row = 4;
                foreach (($this->report['meta'] ?? []) as $m) {
                    $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                    $sheet->setCellValue("A{$row}", ($m['label'] ?? '') . ': ' . ($m['value'] ?? ''));
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['size' => 9, 'color' => ['argb' => 'FF4B5563']],
                    ]);
                    $row++;
                }

                // Freeze below the heading row.
                $headingRow = $this->headerRows + 1;
                $sheet->freezePane('A' . ($headingRow + 1));

                // Right-align numeric columns across the data body.
                $aligns   = $this->report['aligns'] ?? [];
                $firstRow = $headingRow + 1;
                $lastRow  = $firstRow + count($this->report['rows'] ?? []) - 1;
                if ($lastRow >= $firstRow) {
                    foreach ($aligns as $idx => $align) {
                        if ($align === 'num') {
                            $col = $this->colLetter($idx + 1);
                            $sheet->getStyle("{$col}{$firstRow}:{$col}{$lastRow}")
                                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                    }
                }

                // Append summary footer rows, if any — label merged across all
                // but the last column (right-aligned), value in the last column,
                // so multiple totals line up cleanly under the data.
                $valueCol = $this->colLetter($this->colCount);
                $labelEnd = $this->colCount > 1 ? $this->colLetter($this->colCount - 1) : 'A';
                foreach (($this->report['summary'] ?? []) as $s) {
                    $r = ++$lastRow;

                    if ($this->colCount > 1) {
                        $sheet->mergeCells("A{$r}:{$labelEnd}{$r}");
                    }
                    $sheet->setCellValue("A{$r}", $s['label'] ?? '');
                    $sheet->setCellValue("{$valueCol}{$r}", $s['value'] ?? '');

                    $sheet->getStyle("A{$r}:{$valueCol}{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF1F2937']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEEF2FF']],
                    ]);
                    $sheet->getStyle("A{$r}:{$labelEnd}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("{$valueCol}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }

    /** 1→A, 27→AA. */
    private function colLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod    = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index  = intdiv($index - 1, 26);
        }

        return $letter;
    }
}
