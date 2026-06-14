<?php
namespace App\Support;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Collection;

/**
 * Central catalogue for the BIDA CPF Reporting module.
 *
 * Every report (and certificate) the system can produce is declared here once:
 * its group, label, the parameters it needs, the permission/role that gates it,
 * whether it is a tabular "summary" report or a single-record "certificate", and
 * which export formats it supports.
 *
 * The single report page (resources/views/reports/index.blade.php) builds its
 * grouped <select> from groupedFor(); the controller validates an incoming
 * report key against find(); and the parameter panel + preview are driven by
 * the `params` each definition declares.
 *
 * Nothing here touches the database — option lists for employee / fiscal-year /
 * batch selectors are injected at request time by ReportController::params().
 */
class ReportRegistry
{
    public const KIND_SUMMARY     = 'summary';     // tabular report → table preview + xlsx/csv/pdf
    public const KIND_CERTIFICATE = 'certificate'; // letter / per-record document → pdf (+ xlsx data sheet)

    /*
    |--------------------------------------------------------------------------
    | Group metadata (drives the <optgroup> labels + ordering)
    |--------------------------------------------------------------------------
    */
    public static function groups(): array
    {
        return [
            'employee'     => 'Employee Reports',
            'contribution' => 'Contribution Reports',
            'advance'      => 'Advance Reports',
            'interest'     => 'Interest Reports',
            'management'   => 'Management Reports',
            'audit'        => 'Audit & Login Reports',
            'certificate'  => 'Certificates',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Parameter metadata (drives the AJAX-loaded parameter panel inputs)
    |--------------------------------------------------------------------------
    | type:    employee | fiscal_year | date | date_from | date_to | select | month_year
    | options: 'static' inline list, or a server-resolved key (employees,
    |          fiscal_years, contribution_batches, interest_batches,
    |          approved_advances, completed_advances, settlements,
    |          deceased_settlements, ledger_types, audit_events, users)
    */
    public static function paramMeta(): array
    {
        return [
            'employee'            => ['label' => 'Employee', 'type' => 'employee', 'options' => 'employees'],
            'employee_required'   => ['label' => 'Employee', 'type' => 'employee', 'options' => 'employees', 'required' => true],
            'fiscal_year'         => ['label' => 'Fiscal Year', 'type' => 'fiscal_year', 'options' => 'fiscal_years'],
            'fiscal_year_required'=> ['label' => 'Fiscal Year', 'type' => 'fiscal_year', 'options' => 'fiscal_years', 'required' => true],
            'date_from'           => ['label' => 'From Date', 'type' => 'date_from'],
            'date_to'             => ['label' => 'To Date', 'type' => 'date_to'],
            'as_of'               => ['label' => 'As of Date', 'type' => 'date'],
            'month_year'          => ['label' => 'Month', 'type' => 'month_year'],
            'status_employee'     => ['label' => 'Status', 'type' => 'select', 'options' => [
                'active' => 'Active', 'inactive' => 'Inactive',
            ]],
            'status_advance'      => ['label' => 'Status', 'type' => 'select', 'options' => [
                'draft' => 'Draft', 'submitted' => 'Pending Approval', 'approved' => 'Approved',
                'rejected' => 'Rejected', 'completed' => 'Completed',
            ]],
            'ledger_type'         => ['label' => 'Transaction Type', 'type' => 'select', 'options' => 'ledger_types'],
            'contribution_batch'  => ['label' => 'Contribution Batch', 'type' => 'select', 'options' => 'contribution_batches'],
            'interest_batch'      => ['label' => 'Interest Batch', 'type' => 'select', 'options' => 'interest_batches'],
            'interest_batch_required' => ['label' => 'Interest Batch', 'type' => 'select', 'options' => 'interest_batches', 'required' => true],
            'advance_approved'    => ['label' => 'Advance', 'type' => 'select', 'options' => 'approved_advances', 'required' => true],
            'advance_completed'   => ['label' => 'Advance', 'type' => 'select', 'options' => 'completed_advances', 'required' => true],
            'settlement_standard' => ['label' => 'Settlement', 'type' => 'select', 'options' => 'settlements', 'required' => true],
            'settlement_nominee'  => ['label' => 'Settlement', 'type' => 'select', 'options' => 'deceased_settlements', 'required' => true],
            'audit_event'         => ['label' => 'Event', 'type' => 'select', 'options' => 'audit_events'],
            'user'                => ['label' => 'User', 'type' => 'select', 'options' => 'users'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | The catalogue
    |--------------------------------------------------------------------------
    | gate: a permission name (checked via $user->can()) OR "role:Name"
    |       (checked via $user->hasRole()).
    */
    public static function all(): array
    {
        return [
            // ───────────────────────── Employee Reports ─────────────────────
            'employee_directory' => [
                'group'   => 'employee',
                'label'   => 'Employee Directory',
                'desc'    => 'All CPF members with grade, basic salary, current balance and status.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'employee.view',
                'params'  => ['status_employee'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],
            'employee_salary_register' => [
                'group'   => 'employee',
                'label'   => 'Salary & Pay-Scale Register',
                'desc'    => 'Current grade, step and basic salary for each member as of a date.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'employee_salary.view',
                'params'  => ['employee', 'status_employee'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],

            // ──────────────────────── Contribution Reports ──────────────────
            'contribution_summary' => [
                'group'   => 'contribution',
                'label'   => 'Monthly Contribution Summary',
                'desc'    => 'Per-batch totals (employee + government share) for a fiscal year.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'cpf_contribution.view',
                'params'  => ['fiscal_year'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],
            'contribution_register' => [
                'group'   => 'contribution',
                'label'   => 'Contribution Register (Member-wise)',
                'desc'    => 'Member-by-member contribution lines for an approved batch or fiscal year.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'cpf_contribution.view',
                'params'  => ['contribution_batch', 'fiscal_year', 'employee'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],

            // ───────────────────────── Advance Reports ──────────────────────
            'advance_summary' => [
                'group'   => 'advance',
                'label'   => 'Advance Summary',
                'desc'    => 'All advance applications with amount, rate, installments and status.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'cpf_advance.view',
                'params'  => ['status_advance', 'date_from', 'date_to'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],
            'outstanding_advances' => [
                'group'   => 'advance',
                'label'   => 'Outstanding Advances',
                'desc'    => 'Approved advances with a remaining balance, plus repayment progress.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'cpf_advance.view',
                'params'  => ['employee'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],
            'advance_recovery_register' => [
                'group'   => 'advance',
                'label'   => 'Recovery Register',
                'desc'    => 'Approved recovery postings (principal + interest) over a period.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'cpf_advance.view',
                'params'  => ['date_from', 'date_to', 'employee'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],

            // ───────────────────────── Interest Reports ─────────────────────
            'interest_distribution_summary' => [
                'group'   => 'interest',
                'label'   => 'Interest Distribution Summary',
                'desc'    => 'Per-batch bank interest distribution totals by cut-off date.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'bank_interest.view',
                'params'  => ['fiscal_year'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],
            'interest_distribution_detail' => [
                'group'   => 'interest',
                'label'   => 'Interest Distribution (Member-wise)',
                'desc'    => 'Member allocations for a single interest distribution batch.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'bank_interest.view',
                'params'  => ['interest_batch_required'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],

            // ──────────────────────── Management Reports ────────────────────
            'cpf_fund_position' => [
                'group'   => 'management',
                'label'   => 'CPF Fund Position',
                'desc'    => 'Fund-level totals: contributions, interest credited, advances outstanding and net fund balance as of a date.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'report.view',
                'params'  => ['as_of'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'portrait',
            ],
            'member_balance_summary' => [
                'group'   => 'management',
                'label'   => 'Member Balance Summary',
                'desc'    => 'Current CPF balance for every member with status filter.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'cpf_ledger.view',
                'params'  => ['status_employee'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],
            'ledger_transactions' => [
                'group'   => 'management',
                'label'   => 'Ledger Transactions',
                'desc'    => 'Raw ledger movements across all members over a period, by type.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'cpf_ledger.view',
                'params'  => ['date_from', 'date_to', 'ledger_type', 'employee'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],

            // ─────────────────────── Audit & Login Reports ──────────────────
            'activity_audit_log' => [
                'group'   => 'audit',
                'label'   => 'Activity Audit Log',
                'desc'    => 'System activity trail (create / update / delete) over a period.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'role:Admin',
                'params'  => ['date_from', 'date_to', 'audit_event'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],
            'login_activity' => [
                'group'   => 'audit',
                'label'   => 'Login Activity',
                'desc'    => 'User sign-in history with IP, device and timestamp.',
                'kind'    => self::KIND_SUMMARY,
                'gate'    => 'role:Admin',
                'params'  => ['date_from', 'date_to', 'user'],
                'formats' => ['pdf', 'xlsx', 'csv'],
                'orient'  => 'landscape',
            ],

            // ─────────────────────────── Certificates ───────────────────────
            'cert_annual_statement' => [
                'group'   => 'certificate',
                'label'   => 'Annual Statement Certificate',
                'desc'    => 'Year-end CPF account slip: opening balance, contributions, interest and closing balance for a fiscal year.',
                'kind'    => self::KIND_CERTIFICATE,
                'gate'    => 'cpf_ledger.view',
                'params'  => ['employee_required', 'fiscal_year_required'],
                'formats' => ['pdf', 'xlsx'],
                'orient'  => 'portrait',
            ],
            'cert_balance' => [
                'group'   => 'certificate',
                'label'   => 'CPF Balance Certificate',
                'desc'    => 'Official statement of a member\'s current CPF balance as of a date.',
                'kind'    => self::KIND_CERTIFICATE,
                'gate'    => 'cpf_ledger.view',
                'params'  => ['employee_required', 'as_of'],
                'formats' => ['pdf'],
                'orient'  => 'portrait',
            ],
            'cert_advance_sanction' => [
                'group'   => 'certificate',
                'label'   => 'Advance Sanction Letter',
                'desc'    => 'Sanction letter for an approved CPF advance with the repayment schedule.',
                'kind'    => self::KIND_CERTIFICATE,
                'gate'    => 'cpf_advance.view',
                'params'  => ['advance_approved'],
                'formats' => ['pdf'],
                'orient'  => 'portrait',
            ],
            'cert_advance_clearance' => [
                'group'   => 'certificate',
                'label'   => 'Advance Clearance Certificate',
                'desc'    => 'Certifies a fully-recovered CPF advance is cleared with no dues.',
                'kind'    => self::KIND_CERTIFICATE,
                'gate'    => 'cpf_advance.view',
                'params'  => ['advance_completed'],
                'formats' => ['pdf'],
                'orient'  => 'portrait',
            ],
            'cert_final_settlement' => [
                'group'   => 'certificate',
                'label'   => 'Final Settlement Certificate',
                'desc'    => 'Final settlement document for a retired / resigned member.',
                'kind'    => self::KIND_CERTIFICATE,
                'gate'    => 'cpf_settlement.view',
                'params'  => ['settlement_standard'],
                'formats' => ['pdf'],
                'orient'  => 'portrait',
            ],
            'cert_nominee_settlement' => [
                'group'   => 'certificate',
                'label'   => 'Nominee Settlement Certificate',
                'desc'    => 'Settlement document payable to the nominee of a deceased member.',
                'kind'    => self::KIND_CERTIFICATE,
                'gate'    => 'cpf_settlement.view',
                'params'  => ['settlement_nominee'],
                'formats' => ['pdf'],
                'orient'  => 'portrait',
            ],
            'cert_interest_distribution' => [
                'group'   => 'certificate',
                'label'   => 'Interest Distribution Certificate',
                'desc'    => 'Certifies the bank interest credited to a member in a distribution batch.',
                'kind'    => self::KIND_CERTIFICATE,
                'gate'    => 'bank_interest.view',
                'params'  => ['interest_batch_required', 'employee_required'],
                'formats' => ['pdf'],
                'orient'  => 'portrait',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Lookups
    |--------------------------------------------------------------------------
    */

    /**
     * A single report definition (with its key folded in), or null.
     */
    public static function find(string $key): ?array
    {
        $all = self::all();

        return isset($all[$key]) ? ['key' => $key] + $all[$key] : null;
    }

    /**
     * Whether the given user may access a report definition.
     */
    public static function allows(?Authorizable $user, array $report): bool
    {
        if (! $user) {
            return false;
        }

        $gate = $report['gate'] ?? null;
        if (! $gate) {
            return true;
        }

        if (str_starts_with($gate, 'role:')) {
            return method_exists($user, 'hasRole')
                && $user->hasRole(substr($gate, 5));
        }

        return $user->can($gate);
    }

    /**
     * All report definitions the user may access, grouped for the dropdown:
     *
     *   [ 'Employee Reports' => [ ['key'=>..., 'label'=>...], ... ], ... ]
     *
     * Empty groups are dropped, group order follows groups().
     */
    public static function groupedFor(?Authorizable $user): array
    {
        $groups   = self::groups();
        $accessible = collect(self::all())
            ->filter(fn($report) => self::allows($user, $report));

        $out = [];
        foreach ($groups as $groupKey => $groupLabel) {
            $items = $accessible
                ->filter(fn($r) => ($r['group'] ?? null) === $groupKey)
                ->map(fn($r, $key) => [
                    'key'   => $key,
                    'label' => $r['label'],
                    'desc'  => $r['desc'] ?? '',
                    'kind'  => $r['kind'],
                ])
                ->values()
                ->all();

            if (! empty($items)) {
                $out[$groupLabel] = $items;
            }
        }

        return $out;
    }

    /**
     * Resolve the ordered, hydrated parameter list for a report key:
     * each item carries its meta (label, type, required, options-source).
     *
     * @return Collection<int, array>
     */
    public static function paramsFor(string $reportKey): Collection
    {
        $report = self::find($reportKey);
        if (! $report) {
            return collect();
        }

        $meta = self::paramMeta();

        return collect($report['params'] ?? [])
            ->map(function (string $paramKey) use ($meta) {
                $m = $meta[$paramKey] ?? ['label' => str($paramKey)->headline(), 'type' => 'text'];

                return [
                    'key'      => $paramKey,
                    'label'    => $m['label'],
                    'type'     => $m['type'],
                    'required' => $m['required'] ?? false,
                    'options'  => $m['options'] ?? null, // resolved later by the controller
                ];
            });
    }
}
