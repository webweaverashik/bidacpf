<?php
namespace App\Exports\Reports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * CPF Account Slip workbook — one worksheet per member, mirroring the legacy
 * "one tab per employee" format. Each sheet is rendered from the slip view.
 */
class AccountSlipExport implements WithMultipleSheets
{
    /**
     * @param array  $slips     per-member slip data (from ReportService::accountSlip)
     * @param string $sheetView blade used to render a single slip worksheet
     * @param array  $meta      ['fiscalYear', 'asOfLabel', 'generatedAt', ...]
     */
    public function __construct(
        protected array $slips,
        protected string $sheetView,
        protected array $meta
    ) {}

    public function sheets(): array
    {
        $sheets = [];
        $used   = [];

        foreach ($this->slips as $slip) {
            $sheets[] = new AccountSlipSheet(
                $slip,
                $this->sheetView,
                $this->meta,
                $this->uniqueTitle($slip, $used)
            );
        }

        return $sheets ?: [new AccountSlipSheet(
            ['account_no' => '', 'name' => 'No members', 'designation' => '', 'open_own' => 0, 'open_govt' => 0,
                'open_int' => 0, 'open_total' => 0, 'year_own' => 0, 'year_govt' => 0, 'year_int' => 0,
                'total_deposit' => 0, 'adv_taken' => 0, 'adv_recovery' => 0, 'adv_remaining' => 0,
                'net_deposit' => 0, 'in_words' => 'Zero Taka Only'],
            $this->sheetView,
            $this->meta,
            'No members'
        )];
    }

    /**
     * Excel worksheet titles must be ≤ 31 chars, can't contain : \ / ? * [ ],
     * and must be unique within the workbook.
     */
    private function uniqueTitle(array $slip, array &$used): string
    {
        $base = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', (string) ($slip['name'] ?? 'Member'));
        $base = trim(mb_substr($base, 0, 28)) ?: 'Member';

        $title = $base;
        $n     = 1;
        while (in_array(mb_strtolower($title), $used, true)) {
            $title = trim(mb_substr($base, 0, 25)) . ' ' . (++$n);
        }

        $used[] = mb_strtolower($title);

        return $title;
    }
}

/**
 * A single member's worksheet within the account-slip workbook.
 */
class AccountSlipSheet implements FromView, WithTitle
{
    public function __construct(
        protected array $slip,
        protected string $sheetView,
        protected array $meta,
        protected string $sheetTitle
    ) {}

    public function view(): View
    {
        return view($this->sheetView, [
            'slip'        => $this->slip,
            'fiscalYear'  => $this->meta['fiscalYear'] ?? '',
            'asOfLabel'   => $this->meta['asOfLabel'] ?? '',
            'generatedAt' => $this->meta['generatedAt'] ?? now(),
        ]);
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
