<?php
namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\FiscalYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboard)
    {
    }

    /**
     * Role-aware dashboard. Each role gets its own view but they share a common
     * partial library; the heavy lifting is done in DashboardService so the views
     * stay presentation-only.
     */
    public function index()
    {
        $user  = auth()->user();
        $fy    = FiscalYearService::current();
        $chart = $this->dashboard->chartData($fy);

        // Shared payload — every role sees these.
        $common = [
            'fiscalYears'         => $this->dashboard->availableFiscalYears(),
            'currentFy'           => $fy,
            // Point-in-time "as of today" figures (do not change with the FY switch).
            'stats'               => [
                'members'     => $this->dashboard->totalActiveMembers(),
                'fund'        => $this->dashboard->totalFundBalance(),
                'outstanding' => $this->dashboard->totalOutstandingAdvances(),
            ],
            // FY-scoped chart series + FY stat figures (repaint on the FY switch).
            'chart'               => $chart,
            'advancePortfolio'    => $this->dashboard->advancePortfolio(),
            'membersByGrade'      => $this->dashboard->membersByGrade(),
            'recentTransactions'  => $this->dashboard->recentTransactions(),
            'irregularRecoveries' => $this->dashboard->irregularRecoveries(),
        ];

        if ($user->isAdmin()) {
            return view('dashboard.admin.index', array_merge($common, [
                'pendingApprovals'    => $this->dashboard->pendingApprovals(),
                'recentLogins'        => $this->dashboard->recentLogins(),
                'recentAuditActivity' => $this->dashboard->recentAuditActivity(),
            ]));
        }

        if ($user->isCpfOfficer()) {
            return view('dashboard.officer.index', array_merge($common, [
                'worklist'          => $this->dashboard->officerWorklist($user->id),
                'currentMonthBatch' => $this->dashboard->currentMonthBatch(),
            ]));
        }

        if ($user->isAuditor()) {
            return view('dashboard.auditor.index', array_merge($common, [
                'recentAdvances' => $this->dashboard->recentAdvances(),
            ]));
        }

        abort(403, 'Unauthorized access');
    }

    /**
     * AJAX endpoint backing the fiscal-year selector. Returns the FY-scoped chart
     * series as JSON so BidaDashboard can repaint the charts without a reload.
     */
    public function charts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fy' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        // Keep the selection within the real, available range.
        $available = $this->dashboard->availableFiscalYears();
        $fy        = in_array($validated['fy'], $available, true)
            ? $validated['fy']
            : FiscalYearService::current();

        return response()->json($this->dashboard->chartData($fy));
    }
}
