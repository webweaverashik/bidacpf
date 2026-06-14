<?php
namespace App\Services;

use App\Enums\AdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\RecoveryStatus;
use App\Enums\SettlementStatus;
use App\Enums\SettlementType;
use App\Models\Auth\LoginActivity;
use App\Models\Auth\User;
use App\Models\Cpf\CpfAdvance;
use App\Models\Cpf\CpfAdvanceRecovery;
use App\Models\Cpf\CpfContribution;
use App\Models\Cpf\CpfContributionBatch;
use App\Models\Cpf\CpfFinalSettlement;
use App\Models\Cpf\CpfLedger;
use App\Models\Employee\Employee;
use App\Models\Interest\BankInterestBatch;
use App\Models\Interest\BankInterestDistribution;
use App\Support\FiscalYearService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Builds the data behind every report and certificate in the Reporting module.
 *
 * Tabular ("summary") reports return a uniform envelope consumed identically by
 * the on-screen preview, the generic PDF table and the generic Excel export:
 *
 *   [
 *     'title'    => string,
 *     'subtitle' => string,
 *     'meta'     => [['label'=>.., 'value'=>..], ...]   // optional header block
 *     'headings' => [string, ...],
 *     'aligns'   => ['center'|'left'|'num', ...],        // per-column
 *     'rows'     => [[scalar, ...], ...],                // pre-formatted cells
 *     'summary'  => [['label'=>.., 'value'=>.., 'span'=>int], ...] // optional footer
 *   ]
 *
 * Certificates return a model-bound payload consumed by a dedicated Blade.
 *
 * Ledger figures are read through LedgerService so balances stay consistent.
 */
class ReportService
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /*
    |==========================================================================
    | Summary reports
    |==========================================================================
    */

    public function build(string $key, array $p): array
    {
        return match ($key) {
            'employee_directory'            => $this->employeeDirectory($p),
            'employee_salary_register'      => $this->employeeSalaryRegister($p),
            'contribution_summary'          => $this->contributionSummary($p),
            'contribution_register'         => $this->contributionRegister($p),
            'advance_summary'               => $this->advanceSummary($p),
            'outstanding_advances'          => $this->outstandingAdvances($p),
            'advance_recovery_register'     => $this->advanceRecoveryRegister($p),
            'interest_distribution_summary' => $this->interestDistributionSummary($p),
            'interest_distribution_detail'  => $this->interestDistributionDetail($p),
            'cpf_fund_position'             => $this->cpfFundPosition($p),
            'member_balance_summary'        => $this->memberBalanceSummary($p),
            'ledger_transactions'           => $this->ledgerTransactions($p),
            'activity_audit_log'            => $this->activityAuditLog($p),
            'login_activity'                => $this->loginActivity($p),
            default                         => throw new \InvalidArgumentException("Unknown report [{$key}]."),
        };
    }

    // ───────────────────────────── Employee ─────────────────────────────────

    private function employeeDirectory(array $p): array
    {
        $service = $p['service_status'] ?? null;

        $q = Employee::query()
            ->leftJoin('pay_scale_steps', 'employees.pay_scale_step_id', '=', 'pay_scale_steps.id')
            ->leftJoin('pay_scales', 'pay_scale_steps.pay_scale_id', '=', 'pay_scales.id')
            ->select([
                'employees.id', 'employees.cpf_account_no', 'employees.name', 'employees.designation',
                'employees.status',
                'pay_scale_steps.grade as ps_grade', 'pay_scale_steps.basic_salary as ps_basic',
                'pay_scales.name as ps_name',
            ])
            ->selectSub($this->balanceSub(), 'current_balance')
            ->orderBy('employees.name');

        if ($service && EmployeeStatus::tryFrom($service)) {
            $q->where('employees.status', $service);
        }

        $i    = 0;
        $rows = $q->get()->map(function ($e) use (&$i) {
            return [
                ++$i,
                $e->cpf_account_no,
                $e->name,
                $e->designation,
                $e->ps_name ?: '—',
                $e->ps_grade ?: '—',
                number_format((int) $e->ps_basic),
                number_format((int) $e->current_balance),
                $e->status?->label() ?? '—',
            ];
        })->all();

        return [
            'title'    => 'Employee Directory',
            'subtitle' => $this->serviceStatusSubtitle($service),
            'meta'     => [],
            'headings' => ['#', 'CPF A/C No.', 'Name', 'Designation', 'Pay Scale', 'Grade', 'Basic Salary (Tk)', 'Balance (Tk)', 'Service Status'],
            'aligns'   => ['center', 'left', 'left', 'left', 'left', 'center', 'num', 'num', 'center'],
            'rows'     => $rows,
            'summary'  => [['label' => 'Total Members', 'value' => count($rows), 'span' => 8]],
        ];
    }

    private function employeeSalaryRegister(array $p): array
    {
        $status     = $p['status_employee'] ?? null;
        $employeeId = $p['employee'] ?? null;

        $q = Employee::query()
            ->leftJoin('pay_scale_steps', 'employees.pay_scale_step_id', '=', 'pay_scale_steps.id')
            ->leftJoin('pay_scales', 'pay_scale_steps.pay_scale_id', '=', 'pay_scales.id')
            ->select([
                'employees.id', 'employees.cpf_account_no', 'employees.name', 'employees.designation',
                'employees.joining_date', 'employees.is_active',
                'pay_scale_steps.grade as ps_grade', 'pay_scale_steps.step as ps_step',
                'pay_scale_steps.basic_salary as ps_basic', 'pay_scales.name as ps_name',
            ])
            ->orderBy('employees.name');

        if ($status === 'active') {
            $q->where('employees.is_active', true);
        } elseif ($status === 'inactive') {
            $q->where('employees.is_active', false);
        }
        if ($employeeId) {
            $q->where('employees.id', $employeeId);
        }

        $i     = 0;
        $total = 0;
        $rows  = $q->get()->map(function ($e) use (&$i, &$total) {
            $total += (int) $e->ps_basic;

            return [
                ++$i,
                $e->cpf_account_no,
                $e->name,
                $e->designation,
                $e->ps_name ?: '—',
                $e->ps_grade ?: '—',
                $e->ps_step ?: '—',
                number_format((int) $e->ps_basic),
                $e->joining_date ? Carbon::parse($e->joining_date)->format('d-M-Y') : '—',
            ];
        })->all();

        return [
            'title'    => 'Salary & Pay-Scale Register',
            'subtitle' => $this->statusSubtitle($status, 'members'),
            'meta'     => [],
            'headings' => ['#', 'CPF A/C No.', 'Name', 'Designation', 'Pay Scale', 'Grade', 'Step', 'Basic Salary (Tk)', 'Joining Date'],
            'aligns'   => ['center', 'left', 'left', 'left', 'left', 'center', 'center', 'num', 'center'],
            'rows'     => $rows,
            'summary'  => [['label' => 'Total Basic Salary (Tk)', 'value' => number_format($total), 'span' => 7]],
        ];
    }

    // ──────────────────────────── Contribution ──────────────────────────────

    private function contributionSummary(array $p): array
    {
        $fy = $p['fiscal_year'] ?? null;

        $q = CpfContributionBatch::query()->orderBy('contribution_month');
        if ($fy) {
            $q->where('fiscal_year', $fy);
        }

        $batches = $q->get();

        $i     = 0;
        $tEmp  = 0;
        $tGovt = 0;
        $rows  = $batches->map(function ($b) use (&$i, &$tEmp, &$tGovt) {
            $emp    = $b->totalEmployeeContribution();
            $govt   = $b->totalGovernmentContribution();
            $tEmp  += $emp;
            $tGovt += $govt;

            return [
                ++$i,
                $b->contribution_month->format('F Y'),
                $b->fiscal_year,
                $b->employeeCount(),
                rtrim(rtrim(number_format((float) $b->employee_rate, 2), '0'), '.') . '%',
                rtrim(rtrim(number_format((float) $b->government_rate, 2), '0'), '.') . '%',
                number_format($emp),
                number_format($govt),
                number_format($emp + $govt),
                $b->status->label(),
            ];
        })->all();

        return [
            'title'    => 'Monthly Contribution Summary',
            'subtitle' => ($fy ? "Fiscal Year {$fy}" : 'All fiscal years') . ' · ' . count($rows) . ' batch(es)',
            'meta'     => [],
            'headings' => ['#', 'Month', 'Fiscal Year', 'Members', 'Emp %', 'Govt %', 'Employee (Tk)', 'Government (Tk)', 'Total (Tk)', 'Status'],
            'aligns'   => ['center', 'left', 'center', 'center', 'center', 'center', 'num', 'num', 'num', 'center'],
            'rows'     => $rows,
            'summary'  => [
                ['label' => 'Employee Total (Tk)', 'value' => number_format($tEmp), 'span' => 6],
                ['label' => 'Government Total (Tk)', 'value' => number_format($tGovt), 'span' => 1],
                ['label' => 'Grand Total (Tk)', 'value' => number_format($tEmp + $tGovt), 'span' => 1],
            ],
        ];
    }

    private function contributionRegister(array $p): array
    {
        $batchId    = $p['contribution_batch'] ?? null;
        $fy         = $p['fiscal_year'] ?? null;
        $employeeId = $p['employee'] ?? null;

        $q = CpfContribution::query()
            ->join('cpf_contribution_batches as b', 'cpf_contributions.cpf_contribution_batch_id', '=', 'b.id')
            ->join('employees as e', 'cpf_contributions.employee_id', '=', 'e.id')
            ->select([
                'cpf_contributions.*',
                'b.contribution_month', 'b.fiscal_year',
                'e.name as emp_name', 'e.cpf_account_no as emp_acc',
            ])
            ->orderBy('b.contribution_month')->orderBy('e.name');

        if ($batchId) {
            $q->where('b.id', $batchId);
        } elseif ($fy) {
            $q->where('b.fiscal_year', $fy);
        }
        if ($employeeId) {
            $q->where('cpf_contributions.employee_id', $employeeId);
        }

        $i     = 0;
        $tEmp  = 0;
        $tGovt = 0;
        $rows  = $q->get()->map(function ($c) use (&$i, &$tEmp, &$tGovt) {
            $tEmp  += (int) $c->employee_contribution;
            $tGovt += (int) $c->government_contribution;

            return [
                ++$i,
                Carbon::parse($c->contribution_month)->format('M Y'),
                $c->emp_acc,
                $c->emp_name,
                number_format((int) $c->basic_salary),
                number_format((int) $c->employee_contribution),
                number_format((int) $c->government_contribution),
                number_format((int) $c->employee_contribution + (int) $c->government_contribution),
            ];
        })->all();

        $scope = $batchId
            ? 'Batch #' . $batchId
            : ($fy ? "Fiscal Year {$fy}" : 'All contributions');

        return [
            'title'    => 'Contribution Register (Member-wise)',
            'subtitle' => $scope . ' · ' . count($rows) . ' line(s)',
            'meta'     => [],
            'headings' => ['#', 'Month', 'CPF A/C No.', 'Member', 'Basic Salary (Tk)', 'Employee (Tk)', 'Government (Tk)', 'Total (Tk)'],
            'aligns'   => ['center', 'center', 'left', 'left', 'num', 'num', 'num', 'num'],
            'rows'     => $rows,
            'summary'  => [
                ['label' => 'Employee Total (Tk)', 'value' => number_format($tEmp), 'span' => 5],
                ['label' => 'Government Total (Tk)', 'value' => number_format($tGovt), 'span' => 1],
                ['label' => 'Grand Total (Tk)', 'value' => number_format($tEmp + $tGovt), 'span' => 1],
            ],
        ];
    }

    // ───────────────────────────── Advance ──────────────────────────────────

    private function advanceSummary(array $p): array
    {
        $status = $p['status_advance'] ?? null;
        $from   = $p['date_from'] ?? null;
        $to     = $p['date_to'] ?? null;

        $q = CpfAdvance::query()
            ->join('employees as e', 'cpf_advances.employee_id', '=', 'e.id')
            ->select(['cpf_advances.*', 'e.name as emp_name', 'e.cpf_account_no as emp_acc'])
            ->orderBy('cpf_advances.application_date', 'desc');

        if ($status) {
            $q->where('cpf_advances.status', $status);
        }
        if ($from) {
            $q->whereDate('cpf_advances.application_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('cpf_advances.application_date', '<=', $to);
        }

        $i    = 0;
        $tAmt = 0;
        $tOut = 0;
        $rows = $q->get()->map(function ($a) use (&$i, &$tAmt, &$tOut) {
            $amt   = (int) ($a->approved_amount ?? $a->requested_amount);
            $tAmt += $amt;
            $tOut += (int) $a->outstanding_amount;

            return [
                ++$i,
                $a->advance_no,
                $a->emp_acc,
                $a->emp_name,
                $a->application_date?->format('d-M-Y'),
                number_format($amt),
                rtrim(rtrim(number_format((float) $a->interest_rate, 2), '0'), '.'),
                $a->installment_count,
                number_format((int) $a->outstanding_amount),
                $a->status->label(),
            ];
        })->all();

        return [
            'title'    => 'Advance Summary',
            'subtitle' => $this->periodSubtitle($from, $to) . ' · ' . count($rows) . ' application(s)',
            'meta'     => [],
            'headings' => ['#', 'Advance No', 'CPF A/C No.', 'Member', 'App. Date', 'Amount (Tk)', 'Rate %', 'Inst.', 'Outstanding (Tk)', 'Status'],
            'aligns'   => ['center', 'left', 'left', 'left', 'center', 'num', 'center', 'center', 'num', 'center'],
            'rows'     => $rows,
            'summary'  => [
                ['label' => 'Total Disbursed (Tk)', 'value' => number_format($tAmt), 'span' => 8],
                ['label' => 'Total Outstanding (Tk)', 'value' => number_format($tOut), 'span' => 1],
            ],
        ];
    }

    private function outstandingAdvances(array $p): array
    {
        $employeeId = $p['employee'] ?? null;

        $q = CpfAdvance::query()
            ->join('employees as e', 'cpf_advances.employee_id', '=', 'e.id')
            ->select(['cpf_advances.*', 'e.name as emp_name', 'e.cpf_account_no as emp_acc'])
            ->where('cpf_advances.status', AdvanceStatus::APPROVED)
            ->where('cpf_advances.outstanding_amount', '>', 0)
            ->orderBy('cpf_advances.outstanding_amount', 'desc');

        if ($employeeId) {
            $q->where('cpf_advances.employee_id', $employeeId);
        }

        $i    = 0;
        $tOut = 0;
        $rows = $q->get()->map(function ($a) use (&$i, &$tOut) {
            $tOut += (int) $a->outstanding_amount;

            return [
                ++$i,
                $a->advance_no,
                $a->emp_acc,
                $a->emp_name,
                number_format((int) $a->approved_amount),
                number_format((int) $a->outstanding_amount),
                number_format((int) $a->installment_amount),
                $a->progressPercent() . '%',
            ];
        })->all();

        return [
            'title'    => 'Outstanding Advances',
            'subtitle' => count($rows) . ' approved advance(s) with a balance',
            'meta'     => [],
            'headings' => ['#', 'Advance No', 'CPF A/C No.', 'Member', 'Approved (Tk)', 'Outstanding (Tk)', 'Per Inst. (Tk)', 'Progress'],
            'aligns'   => ['center', 'left', 'left', 'left', 'num', 'num', 'num', 'center'],
            'rows'     => $rows,
            'summary'  => [['label' => 'Total Outstanding (Tk)', 'value' => number_format($tOut), 'span' => 5]],
        ];
    }

    private function advanceRecoveryRegister(array $p): array
    {
        $from       = $p['date_from'] ?? null;
        $to         = $p['date_to'] ?? null;
        $employeeId = $p['employee'] ?? null;

        $q = CpfAdvanceRecovery::query()
            ->join('cpf_advances as a', 'cpf_advance_recoveries.cpf_advance_id', '=', 'a.id')
            ->join('employees as e', 'a.employee_id', '=', 'e.id')
            ->select([
                'cpf_advance_recoveries.*',
                'a.advance_no', 'e.name as emp_name', 'e.cpf_account_no as emp_acc',
            ])
            ->where('cpf_advance_recoveries.status', RecoveryStatus::APPROVED)
            ->orderBy('cpf_advance_recoveries.recovery_date', 'desc');

        if ($from) {
            $q->whereDate('cpf_advance_recoveries.recovery_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('cpf_advance_recoveries.recovery_date', '<=', $to);
        }
        if ($employeeId) {
            $q->where('a.employee_id', $employeeId);
        }

        $i    = 0;
        $tAmt = 0;
        $tPr  = 0;
        $tInt = 0;
        $rows = $q->get()->map(function ($r) use (&$i, &$tAmt, &$tPr, &$tInt) {
            $tAmt += (int) $r->amount;
            $tPr  += (int) $r->principal_applied;
            $tInt += (int) $r->interest_applied;

            return [
                ++$i,
                $r->recovery_no,
                $r->recovery_date?->format('d-M-Y'),
                $r->emp_acc,
                $r->emp_name,
                $r->advance_no,
                number_format((int) $r->principal_applied),
                number_format((int) $r->interest_applied),
                number_format((int) $r->amount),
                $r->bank_name ?: '—',
            ];
        })->all();

        return [
            'title'    => 'Recovery Register',
            'subtitle' => $this->periodSubtitle($from, $to) . ' · ' . count($rows) . ' posting(s)',
            'meta'     => [],
            'headings' => ['#', 'Recovery No', 'Date', 'CPF A/C No.', 'Member', 'Advance No', 'Principal (Tk)', 'Interest (Tk)', 'Amount (Tk)', 'Bank'],
            'aligns'   => ['center', 'left', 'center', 'left', 'left', 'left', 'num', 'num', 'num', 'left'],
            'rows'     => $rows,
            'summary'  => [
                ['label' => 'Principal (Tk)', 'value' => number_format($tPr), 'span' => 6],
                ['label' => 'Interest (Tk)', 'value' => number_format($tInt), 'span' => 1],
                ['label' => 'Total (Tk)', 'value' => number_format($tAmt), 'span' => 1],
            ],
        ];
    }

    // ───────────────────────────── Interest ─────────────────────────────────

    private function interestDistributionSummary(array $p): array
    {
        $fy = $p['fiscal_year'] ?? null;

        $q = BankInterestBatch::query()->orderBy('distribution_date', 'desc');
        if ($fy) {
            $q->where('fiscal_year', $fy);
        }

        $i    = 0;
        $tInt = 0;
        $rows = $q->get()->map(function ($b) use (&$i, &$tInt) {
            $tInt += (int) $b->total_interest_amount;

            return [
                ++$i,
                $b->distribution_date->format('d-M-Y'),
                $b->fiscal_year,
                $b->distributionCount(),
                number_format((int) $b->total_eligible_balance),
                number_format((int) $b->total_interest_amount),
                number_format($b->totalDistributed()),
                number_format($b->roundingResidual()),
                $b->status->label(),
            ];
        })->all();

        return [
            'title'    => 'Interest Distribution Summary',
            'subtitle' => ($fy ? "Fiscal Year {$fy}" : 'All fiscal years') . ' · ' . count($rows) . ' batch(es)',
            'meta'     => [],
            'headings' => ['#', 'Cut-off Date', 'Fiscal Year', 'Members', 'Eligible Balance (Tk)', 'Interest (Tk)', 'Distributed (Tk)', 'Residual (Tk)', 'Status'],
            'aligns'   => ['center', 'center', 'center', 'center', 'num', 'num', 'num', 'num', 'center'],
            'rows'     => $rows,
            'summary'  => [['label' => 'Total Interest (Tk)', 'value' => number_format($tInt), 'span' => 5]],
        ];
    }

    private function interestDistributionDetail(array $p): array
    {
        $batchId = $p['interest_batch_required'] ?? ($p['interest_batch'] ?? null);
        $batch   = $batchId ? BankInterestBatch::find($batchId) : null;

        $rows = [];
        $tInt = 0;
        if ($batch) {
            $i    = 0;
            $dist = $batch->distributions()
                ->join('employees as e', 'bank_interest_distributions.employee_id', '=', 'e.id')
                ->select(['bank_interest_distributions.*', 'e.name as emp_name', 'e.cpf_account_no as emp_acc'])
                ->orderBy('e.name')->get();

            foreach ($dist as $d) {
                $tInt   += (int) $d->interest_amount;
                $rows[]  = [
                    ++$i,
                    $d->emp_acc,
                    $d->emp_name,
                    number_format((int) $d->eligible_balance),
                    number_format($d->ratio * 100, 4) . '%',
                    number_format((int) $d->interest_amount),
                ];
            }
        }

        $subtitle = $batch
            ? 'Cut-off ' . $batch->distribution_date->format('d-M-Y') . ' · ' . count($rows) . ' member(s)'
            : 'Select an interest batch';

        return [
            'title'    => 'Interest Distribution (Member-wise)',
            'subtitle' => $subtitle,
            'meta'     => $batch ? [
                ['label' => 'Total Interest', 'value' => 'Tk ' . number_format((int) $batch->total_interest_amount)],
                ['label' => 'Eligible Fund', 'value' => 'Tk ' . number_format((int) $batch->total_eligible_balance)],
                ['label' => 'Status', 'value' => $batch->status->label()],
            ] : [],
            'headings' => ['#', 'CPF A/C No.', 'Member', 'Eligible Balance (Tk)', 'Ratio', 'Interest (Tk)'],
            'aligns'   => ['center', 'left', 'left', 'num', 'num', 'num'],
            'rows'     => $rows,
            'summary'  => [['label' => 'Total Distributed (Tk)', 'value' => number_format($tInt), 'span' => 5]],
        ];
    }

    // ──────────────────────────── Management ────────────────────────────────

    private function cpfFundPosition(array $p): array
    {
        $asOf = isset($p['as_of']) && $p['as_of'] ? Carbon::parse($p['as_of']) : today();

        $netByType = function (LedgerTransactionType $type) use ($asOf) {
            $row = CpfLedger::query()
                ->where('transaction_type', $type)
                ->where('transaction_date', '<=', $asOf)
                ->selectRaw('COALESCE(SUM(credit),0) as c, COALESCE(SUM(debit),0) as d')
                ->first();

            return (int) $row->c - (int) $row->d;
        };

        $empContrib   = $netByType(LedgerTransactionType::EMPLOYEE_CONTRIBUTION);
        $govtContrib  = $netByType(LedgerTransactionType::GOVERNMENT_CONTRIBUTION);
        $bankInterest = $netByType(LedgerTransactionType::BANK_INTEREST);
        $advInterest  = $netByType(LedgerTransactionType::ADVANCE_INTEREST);
        $manual       = $netByType(LedgerTransactionType::MANUAL_ADJUSTMENT);
        $opening      = $netByType(LedgerTransactionType::OPENING_BALANCE);

        $disbursed = (int) CpfLedger::query()
            ->where('transaction_type', LedgerTransactionType::ADVANCE_DISBURSEMENT)
            ->where('transaction_date', '<=', $asOf)->sum('debit');
        $recovered = (int) CpfLedger::query()
            ->where('transaction_type', LedgerTransactionType::ADVANCE_RECOVERY)
            ->where('transaction_date', '<=', $asOf)->sum('credit');
        $settled = (int) CpfLedger::query()
            ->where('transaction_type', LedgerTransactionType::FINAL_SETTLEMENT)
            ->where('transaction_date', '<=', $asOf)->sum('debit');

        $outstandingAdvance = (int) CpfAdvance::query()
            ->where('status', AdvanceStatus::APPROVED)->sum('outstanding_amount');

        // Net fund = all credits − all debits up to the cut-off (= Σ member balances).
        $net = CpfLedger::query()
            ->where('transaction_date', '<=', $asOf)
            ->selectRaw('COALESCE(SUM(credit),0) as c, COALESCE(SUM(debit),0) as d')->first();
        $netFund = (int) $net->c - (int) $net->d;

        $members = Employee::active()->count();

        $rows = [
            ['Opening Balances Carried In', number_format($opening)],
            ['Employee Contributions', number_format($empContrib)],
            ['Government Contributions', number_format($govtContrib)],
            ['Bank Interest Credited', number_format($bankInterest)],
            ['Advance Interest Recovered', number_format($advInterest)],
            ['Manual Adjustments', number_format($manual)],
            ['Advance Disbursed (Debit)', number_format($disbursed)],
            ['Advance Principal Recovered', number_format($recovered)],
            ['Final Settlements Paid (Debit)', number_format($settled)],
            ['Outstanding Advances (open)', number_format($outstandingAdvance)],
        ];

        return [
            'title'    => 'CPF Fund Position',
            'subtitle' => 'As of ' . $asOf->format('d-M-Y') . ' · ' . $members . ' active member(s)',
            'meta'     => [],
            'headings' => ['Particulars', 'Amount (Tk)'],
            'aligns'   => ['left', 'num'],
            'rows'     => $rows,
            'summary'  => [['label' => 'Net CPF Fund Balance (Tk)', 'value' => number_format($netFund), 'span' => 1]],
        ];
    }

    private function memberBalanceSummary(array $p): array
    {
        $status = $p['status_employee'] ?? null;

        $q = Employee::query()
            ->select(['employees.id', 'employees.cpf_account_no', 'employees.name', 'employees.designation', 'employees.is_active'])
            ->selectSub($this->balanceSub(), 'current_balance')
            ->orderBy('employees.name');

        if ($status === 'active') {
            $q->where('employees.is_active', true);
        } elseif ($status === 'inactive') {
            $q->where('employees.is_active', false);
        }

        $i     = 0;
        $total = 0;
        $rows  = $q->get()->map(function ($e) use (&$i, &$total) {
            $total += (int) $e->current_balance;

            return [
                ++$i,
                $e->cpf_account_no,
                $e->name,
                $e->designation,
                number_format((int) $e->current_balance),
                $e->is_active ? 'Active' : 'Inactive',
            ];
        })->all();

        return [
            'title'    => 'Member Balance Summary',
            'subtitle' => $this->statusSubtitle($status, 'members'),
            'meta'     => [],
            'headings' => ['#', 'CPF A/C No.', 'Name', 'Designation', 'Current Balance (Tk)', 'Status'],
            'aligns'   => ['center', 'left', 'left', 'left', 'num', 'center'],
            'rows'     => $rows,
            'summary'  => [['label' => 'Total Fund Balance (Tk)', 'value' => number_format($total), 'span' => 4]],
        ];
    }

    private function ledgerTransactions(array $p): array
    {
        $from       = $p['date_from'] ?? null;
        $to         = $p['date_to'] ?? null;
        $type       = $p['ledger_type'] ?? null;
        $employeeId = $p['employee'] ?? null;

        $q = CpfLedger::query()
            ->join('employees as e', 'cpf_ledgers.employee_id', '=', 'e.id')
            ->select(['cpf_ledgers.*', 'e.name as emp_name', 'e.cpf_account_no as emp_acc'])
            ->orderBy('cpf_ledgers.transaction_date')->orderBy('cpf_ledgers.id');

        if ($from) {
            $q->whereDate('cpf_ledgers.transaction_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('cpf_ledgers.transaction_date', '<=', $to);
        }
        if ($type) {
            $q->where('cpf_ledgers.transaction_type', $type);
        }
        if ($employeeId) {
            $q->where('cpf_ledgers.employee_id', $employeeId);
        }

        $i      = 0;
        $tDebit = 0;
        $tCred  = 0;
        $rows   = $q->get()->map(function ($l) use (&$i, &$tDebit, &$tCred) {
            $tDebit += (int) $l->debit;
            $tCred  += (int) $l->credit;

            return [
                ++$i,
                $l->transaction_date->format('d-M-Y'),
                $l->emp_acc,
                $l->emp_name,
                $l->transaction_type->label(),
                $l->reference_no ?: '—',
                $l->debit > 0 ? number_format((int) $l->debit) : '',
                $l->credit > 0 ? number_format((int) $l->credit) : '',
                number_format((int) $l->balance),
            ];
        })->all();

        return [
            'title'    => 'Ledger Transactions',
            'subtitle' => $this->periodSubtitle($from, $to) . ' · ' . count($rows) . ' entry(ies)',
            'meta'     => [],
            'headings' => ['#', 'Date', 'CPF A/C No.', 'Member', 'Type', 'Reference', 'Debit (Tk)', 'Credit (Tk)', 'Balance (Tk)'],
            'aligns'   => ['center', 'center', 'left', 'left', 'left', 'left', 'num', 'num', 'num'],
            'rows'     => $rows,
            'summary'  => [
                ['label' => 'Total Debit (Tk)', 'value' => number_format($tDebit), 'span' => 6],
                ['label' => 'Total Credit (Tk)', 'value' => number_format($tCred), 'span' => 1],
            ],
        ];
    }

    // ─────────────────────────── Audit & Login ──────────────────────────────

    private function activityAuditLog(array $p): array
    {
        $from  = $p['date_from'] ?? null;
        $to    = $p['date_to'] ?? null;
        $event = $p['audit_event'] ?? null;

        $userMorph = (new User)->getMorphClass();

        $q = Activity::query()
            ->leftJoin('users', function ($join) use ($userMorph) {
                $join->on('activity_log.causer_id', '=', 'users.id')
                    ->where('activity_log.causer_type', '=', $userMorph);
            })
            ->select(['activity_log.*', 'users.name as causer_name'])
            ->orderBy('activity_log.created_at', 'desc');

        if ($from) {
            $q->whereDate('activity_log.created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('activity_log.created_at', '<=', $to);
        }
        if ($event) {
            $q->where('activity_log.event', $event);
        }

        $i    = 0;
        $rows = $q->limit(5000)->get()->map(function ($a) use (&$i) {
            return [
                ++$i,
                optional($a->created_at)->format('d-M-Y h:i A') ?? '—',
                Str::ucfirst((string) ($a->description ?: '—')),
                Str::headline((string) $a->event),
                $a->log_name ? Str::headline($a->log_name) : '—',
                $a->subject_type ? class_basename($a->subject_type) . ($a->subject_id ? ' #' . $a->subject_id : '') : '—',
                $a->causer_name ?: 'System',
            ];
        })->all();

        return [
            'title'    => 'Activity Audit Log',
            'subtitle' => $this->periodSubtitle($from, $to) . ' · ' . count($rows) . ' event(s)' . (count($rows) >= 5000 ? ' (capped)' : ''),
            'meta'     => [],
            'headings' => ['#', 'When', 'Description', 'Event', 'Log', 'Subject', 'Causer'],
            'aligns'   => ['center', 'left', 'left', 'center', 'left', 'left', 'left'],
            'rows'     => $rows,
            'summary'  => [],
        ];
    }

    private function loginActivity(array $p): array
    {
        $from   = $p['date_from'] ?? null;
        $to     = $p['date_to'] ?? null;
        $userId = $p['user'] ?? null;

        $q = LoginActivity::query()
            ->join('users as u', 'login_activities.user_id', '=', 'u.id')
            ->select(['login_activities.*', 'u.name as user_name', 'u.email as user_email'])
            ->orderBy('login_activities.created_at', 'desc');

        if ($from) {
            $q->whereDate('login_activities.created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('login_activities.created_at', '<=', $to);
        }
        if ($userId) {
            $q->where('login_activities.user_id', $userId);
        }

        $i    = 0;
        $rows = $q->limit(5000)->get()->map(function ($r) use (&$i) {
            return [
                ++$i,
                optional($r->created_at)->format('d-M-Y h:i A') ?? '—',
                $r->user_name,
                $r->user_email,
                $r->ip_address ?: '—',
                $r->device ?: '—',
                Str::limit($r->user_agent ?: '—', 60),
            ];
        })->all();

        return [
            'title'    => 'Login Activity',
            'subtitle' => $this->periodSubtitle($from, $to) . ' · ' . count($rows) . ' sign-in(s)',
            'meta'     => [],
            'headings' => ['#', 'When', 'User', 'Email', 'IP Address', 'Device', 'User Agent'],
            'aligns'   => ['center', 'left', 'left', 'left', 'left', 'left', 'left'],
            'rows'     => $rows,
            'summary'  => [],
        ];
    }

    /*
    |==========================================================================
    | Certificates (model-bound payloads for dedicated Blades)
    |==========================================================================
    */

    public function certificate(string $key, array $p): array
    {
        return match ($key) {
            'cert_annual_statement'      => $this->certAnnualStatement($p),
            'cert_balance'               => $this->certBalance($p),
            'cert_advance_sanction'      => $this->certAdvanceSanction($p),
            'cert_advance_clearance'     => $this->certAdvanceClearance($p),
            'cert_final_settlement'      => $this->certFinalSettlement($p),
            'cert_nominee_settlement'    => $this->certNomineeSettlement($p),
            'cert_interest_distribution' => $this->certInterestDistribution($p),
            default                      => throw new \InvalidArgumentException("Unknown certificate [{$key}]."),
        };
    }

    private function certAnnualStatement(array $p): array
    {
        $employee = Employee::with('payScaleStep.payScale')->findOrFail($p['employee_required'] ?? $p['employee'] ?? null);
        $fy       = $p['fiscal_year_required'] ?? $p['fiscal_year'] ?? FiscalYearService::current();

        $opening = $this->ledgerService->fiscalYearOpeningBalance($employee->id, $fy);
        $entries = $this->ledgerService->statementForFiscalYear($employee->id, $fy);
        $closing = $entries->isNotEmpty()
            ? (int) $entries->last()->balance
            : $opening;

        $net = fn(LedgerTransactionType $t) => (int) $entries
            ->where('transaction_type', $t)
            ->reduce(fn($c, $e) => $c + (int) $e->credit - (int) $e->debit, 0);

        return [
            'view'     => 'exports.certificates.annual-statement',
            'filename' => 'cpf-annual-statement-' . $employee->cpf_account_no . '-' . $fy,
            'data'     => [
                'employee'    => $employee,
                'fiscalYear'  => $fy,
                'opening'     => $opening,
                'closing'     => $closing,
                'entries'     => $entries,
                'totals'      => [
                    'employee'   => $net(LedgerTransactionType::EMPLOYEE_CONTRIBUTION),
                    'government' => $net(LedgerTransactionType::GOVERNMENT_CONTRIBUTION),
                    'interest'   => $net(LedgerTransactionType::BANK_INTEREST),
                    'advance'    => $net(LedgerTransactionType::ADVANCE_DISBURSEMENT),
                    'recovery'   => $net(LedgerTransactionType::ADVANCE_RECOVERY),
                ],
                'generatedAt' => now(),
            ],
        ];
    }

    private function certBalance(array $p): array
    {
        $employee = Employee::with('payScaleStep.payScale')->findOrFail($p['employee_required'] ?? $p['employee'] ?? null);
        $asOf     = isset($p['as_of']) && $p['as_of'] ? Carbon::parse($p['as_of']) : today();

        $balance     = $this->ledgerService->balanceAsOf($employee->id, $asOf);
        $outstanding = (int) $employee->advances()->where('status', AdvanceStatus::APPROVED)->sum('outstanding_amount');

        return [
            'view'     => 'exports.certificates.balance',
            'filename' => 'cpf-balance-certificate-' . $employee->cpf_account_no,
            'data'     => [
                'employee'    => $employee,
                'asOf'        => $asOf,
                'balance'     => $balance,
                'outstanding' => $outstanding,
                'generatedAt' => now(),
            ],
        ];
    }

    private function certAdvanceSanction(array $p): array
    {
        $advance = CpfAdvance::with('employee', 'approver')->findOrFail($p['advance_approved'] ?? null);

        abort_unless(
            in_array($advance->status, [AdvanceStatus::APPROVED, AdvanceStatus::COMPLETED], true),
            422,
            'A sanction letter can only be generated for an approved advance.'
        );

        return [
            'view'     => 'exports.certificates.advance-sanction',
            'filename' => 'advance-sanction-' . $advance->advance_no,
            'data'     => ['advance' => $advance, 'generatedAt' => now()],
        ];
    }

    private function certAdvanceClearance(array $p): array
    {
        $advance = CpfAdvance::with('employee', 'recoveries')->findOrFail($p['advance_completed'] ?? null);

        abort_unless(
            $advance->isCompleted(),
            422,
            'A clearance certificate can only be generated for a fully-recovered advance.'
        );

        return [
            'view'     => 'exports.certificates.advance-clearance',
            'filename' => 'advance-clearance-' . $advance->advance_no,
            'data'     => ['advance' => $advance, 'generatedAt' => now()],
        ];
    }

    private function certFinalSettlement(array $p): array
    {
        $settlement = CpfFinalSettlement::with('employee', 'approver')->findOrFail($p['settlement_standard'] ?? null);

        abort_unless(
            $settlement->status === SettlementStatus::APPROVED,
            422,
            'Only approved settlements can be certified.'
        );

        return [
            'view'     => 'exports.certificates.final-settlement',
            'filename' => 'final-settlement-' . $settlement->settlement_no,
            'data'     => ['settlement' => $settlement, 'generatedAt' => now()],
        ];
    }

    private function certNomineeSettlement(array $p): array
    {
        $settlement = CpfFinalSettlement::with('employee', 'approver')->findOrFail($p['settlement_nominee'] ?? null);

        abort_unless(
            $settlement->status === SettlementStatus::APPROVED && $settlement->settlement_type === SettlementType::DECEASED,
            422,
            'A nominee certificate applies only to approved settlements for a deceased member.'
        );

        return [
            'view'     => 'exports.certificates.nominee-settlement',
            'filename' => 'nominee-settlement-' . $settlement->settlement_no,
            'data'     => ['settlement' => $settlement, 'generatedAt' => now()],
        ];
    }

    private function certInterestDistribution(array $p): array
    {
        $batch    = BankInterestBatch::findOrFail($p['interest_batch_required'] ?? $p['interest_batch'] ?? null);
        $employee = Employee::findOrFail($p['employee_required'] ?? $p['employee'] ?? null);

        $distribution = BankInterestDistribution::with('employee')
            ->where('bank_interest_batch_id', $batch->id)
            ->where('employee_id', $employee->id)
            ->first();

        abort_unless(
            $distribution !== null,
            422,
            'This member has no interest allocation in the selected batch.'
        );

        return [
            'view'     => 'exports.certificates.interest-distribution',
            'filename' => 'interest-certificate-' . $employee->cpf_account_no . '-' . $batch->distribution_date->format('Ymd'),
            'data'     => [
                'batch'        => $batch,
                'employee'     => $employee,
                'distribution' => $distribution,
                'generatedAt'  => now(),
            ],
        ];
    }

    /*
    |==========================================================================
    | Option resolvers (feed the AJAX parameter-panel selectors)
    |==========================================================================
    */

    public function options(string $source): array
    {
        return match ($source) {
            'employees' => Employee::orderBy('name')
                ->get(['id', 'name', 'cpf_account_no'])
                ->mapWithKeys(fn($e) => [$e->id => "{$e->name} ({$e->cpf_account_no})"])->all(),

            'fiscal_years' => $this->fiscalYearOptions(),

            'contribution_batches' => CpfContributionBatch::orderBy('contribution_month', 'desc')->get()
                ->mapWithKeys(fn($b) => [$b->id => $b->contribution_month->format('M Y') . ' — ' . $b->status->label()])->all(),

            'interest_batches' => BankInterestBatch::orderBy('distribution_date', 'desc')->get()
                ->mapWithKeys(fn($b) => [$b->id => $b->distribution_date->format('d-M-Y') . ' — ' . $b->status->label()])->all(),

            'approved_advances' => CpfAdvance::with('employee:id,name')
                ->whereIn('status', [AdvanceStatus::APPROVED, AdvanceStatus::COMPLETED])
                ->orderBy('id', 'desc')->get()
                ->mapWithKeys(fn($a) => [$a->id => $a->advance_no . ' — ' . ($a->employee?->name ?? '')])->all(),

            'completed_advances' => CpfAdvance::with('employee:id,name')
                ->where(fn($q) => $q->where('status', AdvanceStatus::COMPLETED)
                        ->orWhere(fn($s) => $s->where('status', AdvanceStatus::APPROVED)->where('outstanding_amount', '<=', 0)))
                ->orderBy('id', 'desc')->get()
                ->mapWithKeys(fn($a) => [$a->id => $a->advance_no . ' — ' . ($a->employee?->name ?? '')])->all(),

            'settlements' => CpfFinalSettlement::with('employee:id,name')
                ->where('status', SettlementStatus::APPROVED)
                ->whereIn('settlement_type', [SettlementType::RETIREMENT, SettlementType::RESIGNATION])
                ->orderBy('id', 'desc')->get()
                ->mapWithKeys(fn($s) => [$s->id => $s->settlement_no . ' — ' . ($s->employee?->name ?? '')])->all(),

            'deceased_settlements' => CpfFinalSettlement::with('employee:id,name')
                ->where('status', SettlementStatus::APPROVED)
                ->where('settlement_type', SettlementType::DECEASED)
                ->orderBy('id', 'desc')->get()
                ->mapWithKeys(fn($s) => [$s->id => $s->settlement_no . ' — ' . ($s->employee?->name ?? '')])->all(),

            'ledger_types' => CpfLedger::query()
                ->getQuery() // base query → raw string values, bypassing the enum cast
                ->select('transaction_type')->distinct()
                ->orderBy('transaction_type')
                ->pluck('transaction_type')
                ->filter()
                ->mapWithKeys(function ($value) {
                    // Be robust whether $value arrives as a raw string or an enum.
                    $enum = $value instanceof LedgerTransactionType
                        ? $value
                        : LedgerTransactionType::tryFrom((string) $value);
                    $key = $enum?->value ?? (string) $value;

                    return [$key => $enum?->label() ?? (string) $value];
                })->all(),

            'employee_statuses' => EmployeeStatus::options(),

            'audit_events' => Activity::query()->whereNotNull('event')->distinct()
                ->orderBy('event')->pluck('event')
                ->mapWithKeys(fn($e) => [$e => Str::headline($e)])->all(),

            'users' => User::orderBy('name')->get(['id', 'name'])
                ->mapWithKeys(fn($u) => [$u->id => $u->name])->all(),

            default => [],
        };
    }

    /*
    |==========================================================================
    | Helpers
    |==========================================================================
    */

    /** Correlated sub-query returning each employee's latest running balance. */
    private function balanceSub()
    {
        return CpfLedger::query()
            ->selectRaw('balance')
            ->whereColumn('cpf_ledgers.employee_id', 'employees.id')
            ->orderByDesc('transaction_date')->orderByDesc('id')
            ->limit(1);
    }

    /** Fiscal years present in the ledger, newest first, plus the current one. */
    private function fiscalYearOptions(): array
    {
        $min     = CpfLedger::min('transaction_date');
        $current = FiscalYearService::current();

        if (! $min) {
            return [$current => $current];
        }

        $startYear = (int) substr(FiscalYearService::fromDate(Carbon::parse($min)), 0, 4);
        $endYear   = (int) substr($current, 0, 4);

        $years = [];
        for ($y = $endYear; $y >= $startYear; $y--) {
            $fy         = sprintf('%d-%d', $y, $y + 1);
            $years[$fy] = $fy;
        }

        return $years;
    }

    private function statusSubtitle(?string $status, string $noun): string
    {
        $label = match ($status) {
            'active'   => 'Active',
            'inactive' => 'Inactive',
            default    => 'All',
        };

        return "{$label} {$noun}";
    }

    private function serviceStatusSubtitle(?string $status): string
    {
        $label = ($status && ($e = EmployeeStatus::tryFrom($status)))
            ? $e->label()
            : 'All';

        return "{$label} members";
    }

    private function periodSubtitle(?string $from, ?string $to): string
    {
        if (! $from && ! $to) {
            return 'All dates';
        }

        $f = $from ? Carbon::parse($from)->format('d-M-Y') : '—';
        $t = $to ? Carbon::parse($to)->format('d-M-Y') : '—';

        return "{$f} to {$t}";
    }
}
