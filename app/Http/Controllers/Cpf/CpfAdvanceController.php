<?php
namespace App\Http\Controllers\Cpf;

use App\Enums\AdvanceStatus;
use App\Exports\AdvancesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Advance\ApproveAdvanceRequest;
use App\Http\Requests\Advance\StoreAdvanceRequest;
use App\Http\Requests\Advance\UpdateAdvanceRequest;
use App\Models\Cpf\CpfAdvance;
use App\Models\Employee\Employee;
use App\Models\Setting;
use App\Services\AdvanceService;
use App\Services\AttachmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class CpfAdvanceController extends Controller
{
    public function __construct(
        protected AdvanceService $advanceService,
        protected AttachmentService $attachmentService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Pages (shells — rows arrive via the server-side *Data feeds)
    |--------------------------------------------------------------------------
    */
    public function index(): View
    {
        $statuses = AdvanceStatus::options();

        return view('cpf-advances.index', compact('statuses'));
    }

    public function outstanding(): View
    {
        return view('cpf-advances.outstanding');
    }

    public function create(): View
    {
        $employees = Employee::active()->orderBy('name')->get(['id', 'name', 'cpf_account_no']);

        $defaults = [
            'interest_rate'     => Setting::advanceInterestRate(),
            'installment_count' => Setting::maxInstallments(),
            'limit_percentage'  => Setting::advanceLimitPercentage(),
        ];

        return view('cpf-advances.create', compact('employees', 'defaults'));
    }

    public function store(StoreAdvanceRequest $request): RedirectResponse
    {
        $advance = $this->advanceService->createDraft([
             ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        $this->attachmentService->store(
            $advance,
            $request->file('application'),
            auth()->id(),
            'uploads/cpf-advances/applications'
        );

        return redirect()->route('cpf-advances.show', $advance)
            ->with('success', 'Advance draft created. Review and submit it for approval.');
    }

    public function show(CpfAdvance $advance): View
    {
        $advance->load([
            'employee', 'approver', 'submitter', 'rejecter', 'attachments',
            'recoveries' => fn($q) => $q->latest(),
            'recoveries.attachments',
        ]);

        $eligibleAmount = $this->advanceService->eligibleAmount($advance->employee);

        return view('cpf-advances.show', compact('advance', 'eligibleAmount'));
    }

    public function edit(CpfAdvance $advance): View
    {
        abort_unless($advance->isEditable(), 403, 'Only draft advances can be edited.');

        $advance->load('employee', 'attachments');

        $defaults = [
            'limit_percentage' => Setting::advanceLimitPercentage(),
            'max_installments' => Setting::maxInstallments(),
            'eligible_amount'  => $this->advanceService->eligibleAmount($advance->employee),
        ];

        return view('cpf-advances.edit', compact('advance', 'defaults'));
    }

    public function update(UpdateAdvanceRequest $request, CpfAdvance $advance): RedirectResponse
    {
        $this->advanceService->updateDraft($advance, $request->validated());

        if ($request->hasFile('application')) {
            $this->attachmentService->replace(
                $advance,
                $request->file('application'),
                auth()->id(),
                'uploads/cpf-advances/applications'
            );
        }

        return redirect()->route('cpf-advances.show', $advance)
            ->with('success', 'Draft updated.');
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow transitions
    |--------------------------------------------------------------------------
    */
    public function submit(CpfAdvance $advance): RedirectResponse
    {
        try {
            $this->advanceService->submit($advance, auth()->id());

            return back()->with('success', 'Advance submitted for admin approval.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(ApproveAdvanceRequest $request, CpfAdvance $advance): RedirectResponse
    {
        try {
            $this->advanceService->approve($advance, $request->validated(), auth()->id());

            return back()->with('success', 'Advance approved and disbursement posted to the ledger.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, CpfAdvance $advance): RedirectResponse
    {
        $request->validate(['reject_reason' => ['nullable', 'string', 'max:1000']]);

        try {
            $this->advanceService->reject($advance, $request->input('reject_reason'), auth()->id());

            return back()->with('warning', 'Advance request rejected.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reschedule(Request $request, CpfAdvance $advance): RedirectResponse
    {
        $data = $request->validate([
            'installment_count' => ['required', 'integer', 'min:1', 'max:' . Setting::maxInstallments()],
        ]);

        try {
            $this->advanceService->recalcInstallments($advance, (int) $data['installment_count']);

            return back()->with('success', 'Repayment schedule recalculated.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(CpfAdvance $advance): RedirectResponse
    {
        try {
            $this->advanceService->deleteDraft($advance, $this->attachmentService);

            return redirect()->route('cpf-advances.index')
                ->with('success', 'Draft advance deleted.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function eligibility(Employee $employee): JsonResponse
    {
        return response()->json([
            'balance'          => $employee->currentBalance(),
            'eligible_amount'  => $this->advanceService->eligibleAmount($employee),
            'limit_percentage' => Setting::advanceLimitPercentage(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Advance applications — server-side feed + export
    |--------------------------------------------------------------------------
    */
    public function data(Request $request): JsonResponse
    {
        $query = $this->advancesQuery($request);

        $recordsTotal    = CpfAdvance::count();
        $recordsFiltered = (clone $query)->count();

        $orderMap = [
            1 => 'cpf_advances.advance_no',
            2 => 'employees.name',
            3 => 'cpf_advances.application_date',
            4 => 'cpf_advances.approved_amount',
            5 => 'cpf_advances.interest_rate',
            6 => 'cpf_advances.installment_count',
            7 => 'cpf_advances.outstanding_amount',
            8 => 'cpf_advances.status',
        ];
        $colIdx = (int) $request->input('order.0.column', 3);
        $dir    = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderMap[$colIdx] ?? 'cpf_advances.application_date', $dir)->orderBy('cpf_advances.id', $dir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $i    = $start;
        $data = $query->get()->map(function ($a) use (&$i) {
            $i++;
            $url    = route('cpf-advances.show', $a->id);
            $status = $a->status;

            return [
                'DT_RowIndex'  => $i,
                'advance_no'   => '<a href="' . $url . '" class="text-gray-800 text-hover-primary fw-bold">' . e($a->advance_no) . '</a>',
                'employee'     => '<a href="' . $url . '" class="text-gray-800 text-hover-primary">' . e($a->emp_name)
                . '<span class="text-muted fs-8 d-block">' . e($a->emp_acc) . '</span></a>',
                'date'         => $a->application_date->format('d M Y'),
                'amount'       => number_format((int) ($a->approved_amount ?? $a->requested_amount)),
                'rate'         => rtrim(rtrim(number_format($a->interest_rate, 2), '0'), '.') . '%',
                'installments' => $a->installment_count,
                'outstanding'  => number_format((int) $a->outstanding_amount),
                'status'       => '<span class="' . $status->badgeClass() . '"><i class="' . $status->icon() . ' me-1"></i>' . $status->label() . '</span>',
                'actions'      => '<a href="' . $url . '" title="View" class="btn btn-icon text-hover-primary w-30px h-30px"><i class="ki-outline ki-eye fs-2"></i></a>',
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function export(Request $request)
    {
        $rows     = $this->advancesQuery($request)->orderBy('cpf_advances.application_date', 'desc')->get();
        $filename = 'cpf-advances-' . now()->format('Ymd-His');

        return match ($request->input('format', 'xlsx')) {
            'csv'   => Excel::download(new AdvancesExport($rows), "$filename.csv", ExcelFormat::CSV),
            'pdf'   => Pdf::loadView('exports.cpf-advances.advances-pdf', ['rows' => $rows, 'generatedAt' => now()])
                ->setPaper('a4', 'landscape')->download("$filename.pdf"),
            default => Excel::download(new AdvancesExport($rows), "$filename.xlsx"),
        };
    }

    private function advancesQuery(Request $request)
    {
        $query = CpfAdvance::query()
            ->join('employees', 'cpf_advances.employee_id', '=', 'employees.id')
            ->select('cpf_advances.*', 'employees.name as emp_name', 'employees.cpf_account_no as emp_acc');

        if ($scope = $request->input('scope')) {
            if ($scope === 'outstanding') {
                $query->where('cpf_advances.status', AdvanceStatus::APPROVED)
                    ->where('cpf_advances.outstanding_amount', '>', 0);
            }
        }

        if ($status = $request->input('status')) {
            $query->where('cpf_advances.status', $status);
        }

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                    ->orWhere('employees.cpf_account_no', 'like', "%{$search}%")
                    ->orWhere('cpf_advances.advance_no', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Outstanding advances — server-side feed + export
    |--------------------------------------------------------------------------
    */
    public function outstandingData(Request $request): JsonResponse
    {
        $request->merge(['scope' => 'outstanding']);
        $query = $this->advancesQuery($request);

        $recordsTotal    = (clone $query)->count();
        $recordsFiltered = $recordsTotal;

        $orderMap = [
            1 => 'cpf_advances.advance_no',
            2 => 'employees.name',
            3 => 'cpf_advances.approved_amount',
            4 => 'cpf_advances.outstanding_amount',
            5 => 'cpf_advances.installment_amount',
        ];
        $colIdx = (int) $request->input('order.0.column', 4);
        $dir    = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderMap[$colIdx] ?? 'cpf_advances.outstanding_amount', $dir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $i    = $start;
        $data = $query->get()->map(function ($a) use (&$i) {
            $i++;
            $url      = route('cpf-advances.show', $a->id);
            $recover  = route('cpf-advances.recovery.create', $a->id);
            $progress = $a->progressPercent();
            $bar      = '<div class="adv-progress"><span style="width:' . min(100, $progress) . '%"></span></div>'
                . '<span class="text-muted fs-8">' . $progress . '%</span>';

            return [
                'DT_RowIndex' => $i,
                'advance_no'  => '<a href="' . $url . '" class="text-gray-800 text-hover-primary fw-bold">' . e($a->advance_no) . '</a>',
                'employee'    => '<a href="' . $url . '" class="text-gray-800 text-hover-primary">' . e($a->emp_name)
                . '<span class="text-muted fs-8 d-block">' . e($a->emp_acc) . '</span></a>',
                'approved'    => number_format((int) $a->approved_amount),
                'outstanding' => '<span class="text-danger fw-bold">' . number_format((int) $a->outstanding_amount) . '</span>',
                'installment' => number_format((int) $a->installment_amount),
                'progress'    => $bar,
                'actions'     => '<a href="' . $url . '" class="btn btn-sm btn-light btn-active-light-primary">View</a> '
                . '<a href="' . $recover . '" class="btn btn-sm btn-light-primary">Recover</a>',
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function outstandingExport(Request $request)
    {
        $request->merge(['scope' => 'outstanding']);
        $rows     = $this->advancesQuery($request)->orderBy('cpf_advances.outstanding_amount', 'desc')->get();
        $filename = 'cpf-outstanding-advances-' . now()->format('Ymd-His');

        return match ($request->input('format', 'xlsx')) {
            'csv'   => Excel::download(new AdvancesExport($rows, true), "$filename.csv", ExcelFormat::CSV),
            'pdf'   => Pdf::loadView('exports.cpf-advances.advances-pdf', ['rows' => $rows, 'generatedAt' => now(), 'outstanding' => true])
                ->setPaper('a4', 'landscape')->download("$filename.pdf"),
            default => Excel::download(new AdvancesExport($rows, true), "$filename.xlsx"),
        };
    }
}
