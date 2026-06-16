<?php
namespace App\Http\Controllers\Cpf;

use App\Enums\EmployeeStatus;
use App\Enums\SettlementStatus;
use App\Enums\SettlementType;
use App\Exports\SettlementsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settlement\ApproveSettlementRequest;
use App\Http\Requests\Settlement\StoreSettlementRequest;
use App\Http\Requests\Settlement\UpdateSettlementRequest;
use App\Models\Cpf\CpfFinalSettlement;
use App\Models\Employee\Employee;
use App\Services\AttachmentService;
use App\Services\Cpf\SettlementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class SettlementController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService,
        protected AttachmentService $attachmentService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Pages (shells — rows arrive via the server-side data feed)
    |--------------------------------------------------------------------------
    */
    public function index(): View
    {
        $statuses = SettlementStatus::options();
        $types    = SettlementType::options();

        return view('cpf-settlements.index', compact('statuses', 'types'));
    }

    public function create(): View
    {
        $employees = Employee::active()->orderBy('name')->get(['id', 'name', 'cpf_account_no']);
        $types     = SettlementType::options();

        return view('cpf-settlements.create', compact('employees', 'types'));
    }

    public function store(StoreSettlementRequest $request)
    {
        try {
            $settlement = $this->settlementService->createDraft([
                ...$request->validated(),
                'created_by' => auth()->id(),
            ]);

            $this->attachmentService->store(
                $settlement,
                $request->file('document'),
                auth()->id(),
                'uploads/cpf-settlements/documents'
            );

            return $this->ok(
                'Settlement draft created. Review and submit it for approval.',
                route('cpf-settlements.show', $settlement)
            );
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function show(CpfFinalSettlement $settlement): View
    {
        $settlement->load(['employee', 'approver', 'submitter', 'rejecter', 'attachments']);

        // Live figures as of the settlement date (what approval would post now).
        $preview = $this->settlementService->preview($settlement->employee, $settlement->settlement_date);

        return view('cpf-settlements.show', compact('settlement', 'preview'));
    }

    public function edit(CpfFinalSettlement $settlement): View
    {
        abort_unless($settlement->isEditable(), 403, 'Only draft settlements can be edited.');

        $settlement->load('employee', 'attachments');
        $types = SettlementType::options();

        return view('cpf-settlements.edit', compact('settlement', 'types'));
    }

    public function update(UpdateSettlementRequest $request, CpfFinalSettlement $settlement)
    {
        try {
            $this->settlementService->updateDraft($settlement, $request->validated());

            if ($request->hasFile('document')) {
                $this->attachmentService->replace(
                    $settlement,
                    $request->file('document'),
                    auth()->id(),
                    'uploads/cpf-settlements/documents'
                );
            }

            return $this->ok('Draft updated.', route('cpf-settlements.show', $settlement));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow transitions (JSON for fetch callers, redirect otherwise)
    |--------------------------------------------------------------------------
    */
    public function submit(CpfFinalSettlement $settlement)
    {
        try {
            $this->settlementService->submit($settlement, auth()->id());

            return $this->ok('Settlement submitted for admin approval.');
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function approve(ApproveSettlementRequest $request, CpfFinalSettlement $settlement)
    {
        try {
            $this->settlementService->approve($settlement, auth()->id());

            return $this->ok('Settlement approved. The closing entry has been posted and the member settled.');
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function reject(Request $request, CpfFinalSettlement $settlement)
    {
        $request->validate(['reject_reason' => ['nullable', 'string', 'max:1000']]);

        try {
            $this->settlementService->reject($settlement, $request->input('reject_reason'), auth()->id());

            return $this->ok('Settlement rejected and returned to the officer.');
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function destroy(CpfFinalSettlement $settlement)
    {
        try {
            $this->settlementService->deleteDraft($settlement, $this->attachmentService);

            return $this->ok('Draft settlement deleted.', route('cpf-settlements.index'));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX preview — figures + eligibility for the create/edit form
    |--------------------------------------------------------------------------
    */
    public function preview(Employee $employee, Request $request): JsonResponse
    {
        $date    = $request->input('date') ?: now()->toDateString();
        $figures = $this->settlementService->preview($employee, $date);

        return response()->json([
            ...$figures,
            'is_active'                => $employee->status === EmployeeStatus::ACTIVE,
            'has_open_settlement'      => $this->settlementService->hasOpenOrApprovedSettlement($employee),
            'has_pending_advance_work' => $this->settlementService->hasPendingAdvanceWork($employee),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Server-side feed + export
    |--------------------------------------------------------------------------
    */
    public function data(Request $request): JsonResponse
    {
        $query = $this->settlementsQuery($request);

        $recordsTotal    = CpfFinalSettlement::count();
        $recordsFiltered = (clone $query)->count();

        $orderMap = [
            1 => 'cpf_final_settlements.settlement_no',
            2 => 'employees.name',
            3 => 'cpf_final_settlements.settlement_type',
            4 => 'cpf_final_settlements.settlement_date',
            5 => 'cpf_final_settlements.total_payable',
            6 => 'cpf_final_settlements.status',
        ];
        $colIdx = (int) $request->input('order.0.column', 4);
        $dir    = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderMap[$colIdx] ?? 'cpf_final_settlements.settlement_date', $dir)
            ->orderBy('cpf_final_settlements.id', $dir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $i    = $start;
        $data = $query->get()->map(function ($s) use (&$i) {
            $i++;
            $url    = route('cpf-settlements.show', $s->id);
            $status = $s->status;
            $type   = $s->settlement_type;

            return [
                'DT_RowIndex'   => $i,
                'settlement_no' => '<a href="' . $url . '" class="text-gray-800 text-hover-primary fw-bold">' . e($s->settlement_no) . '</a>',
                'employee'      => '<a href="' . $url . '" class="text-gray-800 text-hover-primary">' . e($s->emp_name)
                    . '<span class="text-muted fs-8 d-block">' . e($s->emp_acc) . '</span></a>',
                'type'          => '<span class="' . $type->badgeClass() . '">' . $type->label() . '</span>',
                'date'          => $s->settlement_date->format('d M Y'),
                'payable'       => number_format((int) $s->total_payable),
                'status'        => '<span class="' . $status->badgeClass() . '"><i class="' . $status->icon() . ' me-1"></i>' . $status->label() . '</span>',
                'actions'       => '<a href="' . $url . '" title="View" class="btn btn-icon text-hover-primary w-30px h-30px"><i class="ki-outline ki-eye fs-2"></i></a>',
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
        $rows     = $this->settlementsQuery($request)->orderBy('cpf_final_settlements.settlement_date', 'desc')->get();
        $filename = 'cpf-settlements-' . now()->format('Ymd-His');

        return match ($request->input('format', 'xlsx')) {
            'csv'   => Excel::download(new SettlementsExport($rows), "$filename.csv", ExcelFormat::CSV),
            'pdf'   => Pdf::loadView('exports.cpf-settlements.settlements-pdf', ['rows' => $rows, 'generatedAt' => now()])
                ->setPaper('a4', 'landscape')->download("$filename.pdf"),
            default => Excel::download(new SettlementsExport($rows), "$filename.xlsx"),
        };
    }

    private function settlementsQuery(Request $request)
    {
        $query = CpfFinalSettlement::query()
            ->join('employees', 'cpf_final_settlements.employee_id', '=', 'employees.id')
            ->select('cpf_final_settlements.*', 'employees.name as emp_name', 'employees.cpf_account_no as emp_acc');

        if ($status = $request->input('status')) {
            $query->where('cpf_final_settlements.status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('cpf_final_settlements.settlement_type', $type);
        }

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                    ->orWhere('employees.cpf_account_no', 'like', "%{$search}%")
                    ->orWhere('cpf_final_settlements.settlement_no', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Response helpers — JSON for fetch/AJAX, redirect for plain requests
    |--------------------------------------------------------------------------
    */
    private function ok(string $message, ?string $redirect = null)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => $message,
                'redirect' => $redirect,
            ]);
        }

        return $redirect
            ? redirect($redirect)->with('success', $message)
            : back()->with('success', $message);
    }

    private function fail(string $message, int $code = 422)
    {
        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $code);
        }

        return back()->with('error', $message);
    }
}
