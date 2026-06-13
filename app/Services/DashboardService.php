<?php
namespace App\Services;

use App\Enums\AdvanceStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\RecoveryStatus;
use App\Models\Auth\LoginActivity;
use App\Models\Cpf\CpfAdvance;
use App\Models\Cpf\CpfContributionBatch;
use App\Models\Cpf\CpfFinalSettlement;
use App\Models\Cpf\CpfLedger;
use App\Models\Employee\Employee;
use App\Models\Interest\BankInterestBatch;
use App\Support\FiscalYearService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Aggregates every figure the dashboard renders. All querying lives here so the
 * Blade views stay presentation-only (per the project convention that object
 * instantiation and heavy logic belong outside templates).
 *
 * Money values are whole-BDT integers throughout, matching the ledger schema.
 * Financial figures are read from POSTED ledger rows only, so drafts and pending
 * batches never inflate the headline numbers.
 */
class DashboardService
{
    /*
    |--------------------------------------------------------------------------
    | Fiscal year helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Fiscal years available in the dropdown, newest first. Spans from the FY of
     * the earliest ledger posting up to the current FY; falls back to the current
     * FY alone when the ledger is empty.
     *
     * @return array<int, string>  e.g. ['2025-2026', '2024-2025', ...]
     */
    public function availableFiscalYears(): array
    {
        $current  = FiscalYearService::current();
        $earliest = CpfLedger::min('transaction_date');

        if (! $earliest) {
            return [$current];
        }

        $startYear = (int) explode('-', FiscalYearService::fromDate($earliest))[0];
        $endYear   = (int) explode('-', $current)[0];

        $list = [];
        for ($year = $endYear; $year >= $startYear; $year--) {
            $list[] = sprintf('%s-%s', $year, $year + 1);
        }

        return $list;
    }

    /*
    |--------------------------------------------------------------------------
    | Headline stat tiles
    |--------------------------------------------------------------------------
    */

    /** Count of active CPF members. */
    public function totalActiveMembers(): int
    {
        return Employee::active()->count();
    }

    /**
     * Total CPF fund balance across every member: SUM(credit) - SUM(debit) over
     * all posted ledger rows. Equal to the sum of all members' running balances,
     * but resolved in a single query.
     */
    public function totalFundBalance(): int
    {
        return (int) CpfLedger::query()
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) AS net')
            ->value('net');
    }

    /** Combined outstanding (principal + interest) across all approved advances. */
    public function totalOutstandingAdvances(): int
    {
        return (int) CpfAdvance::approved()->sum('outstanding_amount');
    }

    /** Member + government contributions posted to the ledger in the given FY. */
    public function contributionsForFy(string $fiscalYear): int
    {
        [$start, $end] = $this->fyRange($fiscalYear);

        return (int) CpfLedger::query()
            ->whereBetween('transaction_date', [$start, $end])
            ->whereIn('transaction_type', [
                LedgerTransactionType::EMPLOYEE_CONTRIBUTION->value,
                LedgerTransactionType::GOVERNMENT_CONTRIBUTION->value,
            ])
            ->sum('credit');
    }

    /*
    |--------------------------------------------------------------------------
    | Admin — pending approval queue
    |--------------------------------------------------------------------------
    */

    /**
     * Items awaiting admin approval, each with the count, deep link and the
     * permission that gates it. Consumed by the "Action Required" strip.
     *
     * @return array<int, array{key:string,label:string,count:int,route:string,icon:string,color:string,permission:string}>
     */
    public function pendingApprovals(): array
    {
        return [
            [
                'key'        => 'contributions',
                'label'      => 'Contribution Batches',
                'count'      => CpfContributionBatch::submitted()->count(),
                'route'      => 'cpf-contributions.index',
                'icon'       => 'ki-wallet',
                'color'      => 'primary',
                'permission' => 'cpf_contribution.approve',
            ],
            [
                'key'        => 'advances',
                'label'      => 'Advance Requests',
                'count'      => CpfAdvance::submitted()->count(),
                'route'      => 'cpf-advances.index',
                'icon'       => 'ki-handcart',
                'color'      => 'warning',
                'permission' => 'cpf_advance.approve',
            ],
            [
                'key'        => 'settlements',
                'label'      => 'Final Settlements',
                'count'      => CpfFinalSettlement::submitted()->count(),
                'route'      => 'cpf-settlements.index',
                'icon'       => 'ki-financial-schedule',
                'color'      => 'danger',
                'permission' => 'cpf_settlement.approve',
            ],
            [
                'key'        => 'interest',
                'label'      => 'Interest Distributions',
                'count'      => BankInterestBatch::submitted()->count(),
                'route'      => 'bank-interest.index',
                'icon'       => 'ki-percentage',
                'color'      => 'success',
                'permission' => 'bank_interest.approve',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Officer — work list
    |--------------------------------------------------------------------------
    */

    /**
     * Draft items the officer can act on. Advances and settlements are scoped to
     * the signed-in officer (their own drafts); contribution batches are shared
     * (auto-generated monthly) so the draft count is system-wide.
     *
     * @return array<int, array{key:string,label:string,count:int,route:string,icon:string,color:string}>
     */
    public function officerWorklist(int $userId): array
    {
        return [
            [
                'key'   => 'draft_batches',
                'label' => 'Draft Contribution Batches',
                'count' => CpfContributionBatch::draft()->count(),
                'route' => 'cpf-contributions.index',
                'icon'  => 'ki-wallet',
                'color' => 'primary',
            ],
            [
                'key'   => 'my_draft_advances',
                'label' => 'My Draft Advances',
                'count' => CpfAdvance::draft()->where('created_by', $userId)->count(),
                'route' => 'cpf-advances.index',
                'icon'  => 'ki-handcart',
                'color' => 'warning',
            ],
            [
                'key'   => 'my_draft_settlements',
                'label' => 'My Draft Settlements',
                'count' => CpfFinalSettlement::draft()->where('created_by', $userId)->count(),
                'route' => 'cpf-settlements.index',
                'icon'  => 'ki-financial-schedule',
                'color' => 'danger',
            ],
            [
                'key'   => 'pending_recoveries',
                'label' => 'Recoveries Awaiting Approval',
                'count' => \App\Models\Cpf\CpfAdvanceRecovery::pending()->count(),
                'route' => 'cpf-advances.index',
                'icon'  => 'ki-dollar',
                'color' => 'info',
            ],
        ];
    }

    /**
     * The contribution batch for the current calendar month, if one exists. Lets
     * the officer view tell at a glance whether this month's batch still needs to
     * be generated or submitted.
     */
    public function currentMonthBatch(): ?CpfContributionBatch
    {
        return CpfContributionBatch::query()
            ->whereYear('contribution_month', now()->year)
            ->whereMonth('contribution_month', now()->month)
            ->latest('id')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Charts — fiscal-year scoped (driven by the FY selector / AJAX endpoint)
    |--------------------------------------------------------------------------
    */

    /**
     * All FY-scoped chart series for one fiscal year, shaped for ApexCharts.
     * A single narrowed ledger read backs the three series to keep load light.
     *
     * @return array{
     *   fiscal_year:string,
     *   months:array<int,string>,
     *   fund_growth:array<int,int>,
     *   employee_contribution:array<int,int>,
     *   government_contribution:array<int,int>,
     *   composition:array{labels:array<int,string>,values:array<int,int>}
     * }
     */
    public function chartData(string $fiscalYear): array
    {
        [$start, $end] = $this->fyRange($fiscalYear);

        // 12 month buckets, July -> June.
        $labels   = [];
        $monthKey = [];
        $cursor   = $start->copy();
        for ($i = 0; $i < 12; $i++) {
            $labels[$i]            = $cursor->format('M Y');
            $monthKey[$cursor->format('Y-m')] = $i;
            $cursor->addMonth();
        }

        $employee = array_fill(0, 12, 0);
        $govt     = array_fill(0, 12, 0);
        $net      = array_fill(0, 12, 0);   // monthly (credit - debit) for the growth line
        $composition = [];                   // credit total keyed by transaction type value

        $rows = CpfLedger::query()
            ->whereBetween('transaction_date', [$start, $end])
            ->get(['transaction_date', 'transaction_type', 'debit', 'credit']);

        foreach ($rows as $row) {
            $key = $row->transaction_date->format('Y-m');
            if (! isset($monthKey[$key])) {
                continue;
            }
            $idx = $monthKey[$key];

            $net[$idx] += (int) $row->credit - (int) $row->debit;

            $type = $row->transaction_type;
            if ($type === LedgerTransactionType::EMPLOYEE_CONTRIBUTION) {
                $employee[$idx] += (int) $row->credit;
            } elseif ($type === LedgerTransactionType::GOVERNMENT_CONTRIBUTION) {
                $govt[$idx] += (int) $row->credit;
            }

            if ((int) $row->credit > 0) {
                $label = $type->label();
                $composition[$label] = ($composition[$label] ?? 0) + (int) $row->credit;
            }
        }

        // Fund-growth line = opening fund at FY start + cumulative monthly net.
        $opening = (int) CpfLedger::query()
            ->whereDate('transaction_date', '<', $start->toDateString())
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) AS net')
            ->value('net');

        $fundGrowth = [];
        $running    = $opening;
        foreach ($net as $idx => $monthNet) {
            $running         += $monthNet;
            $fundGrowth[$idx] = $running;
        }

        arsort($composition);

        return [
            'fiscal_year'             => $fiscalYear,
            'months'                  => array_values($labels),
            'fund_growth'             => array_values($fundGrowth),
            'employee_contribution'   => array_values($employee),
            'government_contribution' => array_values($govt),
            'composition'             => [
                'labels' => array_keys($composition),
                'values' => array_values($composition),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Charts — point-in-time snapshots (not fiscal-year dependent)
    |--------------------------------------------------------------------------
    */

    /**
     * Advance portfolio by status (for a donut) plus the headline outstanding vs
     * recovered totals.
     *
     * @return array{labels:array<int,string>,values:array<int,int>,outstanding:int,recovered:int}
     */
    public function advancePortfolio(): array
    {
        $byStatus = CpfAdvance::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $values = [];
        foreach (AdvanceStatus::cases() as $case) {
            $count = (int) ($byStatus[$case->value] ?? 0);
            if ($count > 0) {
                $labels[] = $case->label();
                $values[] = $count;
            }
        }

        $outstanding = $this->totalOutstandingAdvances();
        $recovered   = (int) \App\Models\Cpf\CpfAdvanceRecovery::query()
            ->where('status', RecoveryStatus::APPROVED)
            ->sum('amount');

        return [
            'labels'      => $labels,
            'values'      => $values,
            'outstanding' => $outstanding,
            'recovered'   => $recovered,
        ];
    }

    /**
     * Active members grouped by pay-scale grade (for a bar chart).
     *
     * @return array{labels:array<int,string>,values:array<int,int>}
     */
    public function membersByGrade(): array
    {
        $rows = Employee::query()
            ->active()
            ->join('pay_scale_steps', 'employees.pay_scale_step_id', '=', 'pay_scale_steps.id')
            ->selectRaw('pay_scale_steps.grade AS grade, COUNT(*) AS total')
            ->groupBy('pay_scale_steps.grade')
            ->orderBy('pay_scale_steps.grade')
            ->pluck('total', 'grade');

        $labels = [];
        $values = [];
        foreach ($rows as $grade => $total) {
            $labels[] = 'Grade ' . $grade;
            $values[] = (int) $total;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /*
    |--------------------------------------------------------------------------
    | Recent activity feeds
    |--------------------------------------------------------------------------
    */

    /** Latest ledger postings, newest first. */
    public function recentTransactions(int $limit = 8): Collection
    {
        return CpfLedger::query()
            ->with('employee:id,name,cpf_account_no')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /** Latest advances, newest first. */
    public function recentAdvances(int $limit = 6): Collection
    {
        return CpfAdvance::query()
            ->with('employee:id,name,cpf_account_no')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /** Latest sign-ins (admin feed). */
    public function recentLogins(int $limit = 6): Collection
    {
        return LoginActivity::query()
            ->with('user:id,name,email')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /** Latest audit-log entries (admin feed). */
    public function recentAuditActivity(int $limit = 6): Collection
    {
        return Activity::query()
            ->with('causer')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Irregular / defaulting advance recovery members
    |--------------------------------------------------------------------------
    */

    /**
     * Members whose advance repayments are behind schedule. Since recoveries are
     * recorded ad-hoc (no due-date table), the schedule is treated as implicit:
     * one installment per month, with the first due ONE month after approval.
     *
     *   expected = whole months since approval_date, capped at installment_count
     *   paid     = count of APPROVED recoveries
     *   missed   = max(0, expected - paid)
     *
     * Classification:
     *   - defaulter : missed >= 3   (the "more than 2 installments" rule)
     *   - irregular : missed 1-2, or a gap month between first and last recovery
     *
     * Fully on-track members are omitted. Sorted worst-first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function irregularRecoveries(): Collection
    {
        $today = now();

        $advances = CpfAdvance::query()
            ->where('status', AdvanceStatus::APPROVED)
            ->where('outstanding_amount', '>', 0)
            ->with([
                'employee:id,name,cpf_account_no',
                'recoveries' => fn ($q) => $q
                    ->where('status', RecoveryStatus::APPROVED)
                    ->orderBy('recovery_date'),
            ])
            ->get();

        return $advances
            ->map(function (CpfAdvance $advance) use ($today) {
                if (! $advance->approval_date) {
                    return null;
                }

                $count    = (int) $advance->installment_count;
                // Carbon 3 returns a signed float; we want whole completed months.
                $elapsed  = (int) floor(abs($advance->approval_date->diffInMonths($today))); // first due next month
                $expected = min($elapsed, $count);

                $recoveries = $advance->recoveries;
                $paid       = $recoveries->count();
                $missed     = max(0, $expected - $paid);

                // Gap detection: distinct payment months vs the span they cover.
                $hasGap = false;
                $months = $recoveries
                    ->map(fn ($r) => $r->recovery_date->format('Y-m'))
                    ->unique()
                    ->sort()
                    ->values();

                if ($months->count() >= 2) {
                    $first = Carbon::parse($months->first() . '-01');
                    $last  = Carbon::parse($months->last() . '-01');
                    $span  = (int) round($first->diffInMonths($last)) + 1;
                    $hasGap = $months->count() < $span;
                }

                if ($missed >= 3) {
                    $flag = 'defaulter';
                } elseif ($missed >= 1 || $hasGap) {
                    $flag = 'irregular';
                } else {
                    return null; // on track
                }

                $lastRecovery = $recoveries->last();

                return [
                    'advance_id'         => $advance->id,
                    'advance_no'         => $advance->advance_no,
                    'employee_name'      => $advance->employee?->name ?? '—',
                    'cpf_account_no'     => $advance->employee?->cpf_account_no ?? '—',
                    'installment_amount' => (int) $advance->installment_amount,
                    'paid'               => $paid,
                    'expected'           => $expected,
                    'missed'             => $missed,
                    'outstanding'        => (int) $advance->outstanding_amount,
                    'last_recovery_date' => $lastRecovery?->recovery_date,
                    'flag'               => $flag,
                ];
            })
            ->filter()
            ->sortByDesc('missed')
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Internal
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve a fiscal-year string to its [start, end] Carbon date bounds.
     *
     * @return array{0:Carbon,1:Carbon}
     */
    protected function fyRange(string $fiscalYear): array
    {
        return [
            FiscalYearService::startDate($fiscalYear),
            FiscalYearService::endDate($fiscalYear),
        ];
    }
}
