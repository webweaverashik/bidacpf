<?php
namespace App\Http\Controllers\Cpf;

use App\Enums\RecoveryStatus;
use App\Exports\RecoveriesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Advance\StoreRecoveryRequest;
use App\Http\Requests\Advance\UpdateRecoveryRequest;
use App\Models\Cpf\CpfAdvance;
use App\Models\Cpf\CpfAdvanceRecovery;
use App\Services\AdvanceService;
use App\Services\AttachmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class CpfAdvanceRecoveryController extends Controller
{
    public function __construct(
        protected AdvanceService $advanceService,
        protected AttachmentService $attachmentService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */
    public function index(): View
    {
        $statuses = RecoveryStatus::options();

        return view('cpf-advances.recovery.index', compact('statuses'));
    }

    public function create(CpfAdvance $advance): View
    {
        abort_unless($advance->canRecover(), 403, 'This advance is not in a recoverable state.');

        $advance->load('employee', 'recoveries');

        return view('cpf-advances.recovery.create', compact('advance'));
    }

    public function store(StoreRecoveryRequest $request, CpfAdvance $advance): RedirectResponse
    {
        abort_unless($advance->canRecover(), 403, 'This advance is not in a recoverable state.');

        try {
            $recovery = $this->advanceService->createRecoveryDraft($advance, [
                 ...$request->validated(),
                'created_by' => auth()->id(),
            ]);

            $this->attachmentService->store(
                $recovery,
                $request->file('deposit_slip'),
                auth()->id(),
                'uploads/cpf-advances/deposit-slips'
            );

            return redirect()->route('cpf-advances.recovery.show', [$advance, $recovery])
                ->with('success', 'Recovery draft created. Review and submit it for approval.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(CpfAdvance $advance, CpfAdvanceRecovery $recovery): View
    {
        abort_if($recovery->cpf_advance_id !== $advance->id, 404);

        $recovery->load('advance.employee', 'approver', 'submitter', 'rejecter', 'attachments');

        return view('cpf-advances.recovery.show', compact('advance', 'recovery'));
    }

    public function edit(CpfAdvance $advance, CpfAdvanceRecovery $recovery): View
    {
        abort_if($recovery->cpf_advance_id !== $advance->id, 404);
        abort_unless($recovery->isEditable(), 403, 'Only draft recoveries can be edited.');

        $recovery->load('advance.employee', 'attachments');

        return view('cpf-advances.recovery.edit', compact('advance', 'recovery'));
    }

    public function update(UpdateRecoveryRequest $request, CpfAdvance $advance, CpfAdvanceRecovery $recovery): RedirectResponse
    {
        abort_if($recovery->cpf_advance_id !== $advance->id, 404);

        $this->advanceService->updateRecoveryDraft($recovery, $request->validated());

        if ($request->hasFile('deposit_slip')) {
            $this->attachmentService->replace(
                $recovery,
                $request->file('deposit_slip'),
                auth()->id(),
                'uploads/cpf-advances/deposit-slips'
            );
        }

        return redirect()->route('cpf-advances.recovery.show', [$advance, $recovery])
            ->with('success', 'Recovery draft updated.');
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow transitions
    |--------------------------------------------------------------------------
    */
    public function submit(CpfAdvance $advance, CpfAdvanceRecovery $recovery): RedirectResponse
    {
        abort_if($recovery->cpf_advance_id !== $advance->id, 404);

        try {
            $this->advanceService->submitRecovery($recovery, auth()->id());

            return back()->with('success', 'Recovery submitted for admin approval.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(CpfAdvance $advance, CpfAdvanceRecovery $recovery): RedirectResponse
    {
        abort_if($recovery->cpf_advance_id !== $advance->id, 404);

        try {
            $this->advanceService->approveRecovery($recovery, auth()->id());

            return back()->with('success', 'Recovery approved. Credit posted and outstanding balance updated.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, CpfAdvance $advance, CpfAdvanceRecovery $recovery): RedirectResponse
    {
        abort_if($recovery->cpf_advance_id !== $advance->id, 404);

        $request->validate(['reject_reason' => ['nullable', 'string', 'max:1000']]);

        try {
            $this->advanceService->rejectRecovery($recovery, $request->input('reject_reason'), auth()->id());

            return back()->with('warning', 'Recovery rejected.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(CpfAdvance $advance, CpfAdvanceRecovery $recovery): RedirectResponse
    {
        abort_if($recovery->cpf_advance_id !== $advance->id, 404);

        try {
            $this->advanceService->deleteRecoveryDraft($recovery, $this->attachmentService);

            return redirect()->route('cpf-advances.show', $advance)
                ->with('success', 'Draft recovery deleted.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Recovery postings — server-side feed + export
    |--------------------------------------------------------------------------
    */
    public function data(Request $request): JsonResponse
    {
        $query = $this->recoveriesQuery($request);

        $recordsTotal    = CpfAdvanceRecovery::count();
        $recordsFiltered = (clone $query)->count();

        $orderMap = [
            1 => 'cpf_advance_recoveries.recovery_no',
            2 => 'cpf_advances.advance_no',
            3 => 'employees.name',
            4 => 'cpf_advance_recoveries.recovery_date',
            5 => 'cpf_advance_recoveries.amount',
            6 => 'cpf_advance_recoveries.status',
        ];
        $colIdx = (int) $request->input('order.0.column', 4);
        $dir    = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderMap[$colIdx] ?? 'cpf_advance_recoveries.recovery_date', $dir)
            ->orderBy('cpf_advance_recoveries.id', $dir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $i    = $start;
        $data = $query->get()->map(function ($r) use (&$i) {
            $i++;
            $url    = route('cpf-advances.recovery.show', [$r->cpf_advance_id, $r->id]);
            $status = $r->status;

            return [
                'DT_RowIndex' => $i,
                'recovery_no' => '<a href="' . $url . '" class="text-gray-800 text-hover-primary fw-bold">' . e($r->recovery_no) . '</a>',
                'advance_no'  => e($r->adv_no),
                'employee'    => '<span class="text-gray-800">' . e($r->emp_name)
                . '<span class="text-muted fs-8 d-block">' . e($r->emp_acc) . '</span></span>',
                'date'        => $r->recovery_date->format('d M Y'),
                'amount'      => number_format((int) $r->amount),
                'status'      => '<span class="' . $status->badgeClass() . '"><i class="' . $status->icon() . ' me-1"></i>' . $status->label() . '</span>',
                'actions'     => '<a href="' . $url . '" class="btn btn-sm btn-light btn-active-light-primary">View</a>',
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
        $rows     = $this->recoveriesQuery($request)->orderBy('cpf_advance_recoveries.recovery_date', 'desc')->get();
        $filename = 'cpf-recoveries-' . now()->format('Ymd-His');

        return match ($request->input('format', 'xlsx')) {
            'csv'   => Excel::download(new RecoveriesExport($rows), "$filename.csv", ExcelFormat::CSV),
            'pdf'   => Pdf::loadView('exports.cpf-advances.recoveries-pdf', ['rows' => $rows, 'generatedAt' => now()])
                ->setPaper('a4', 'landscape')->download("$filename.pdf"),
            default => Excel::download(new RecoveriesExport($rows), "$filename.xlsx"),
        };
    }

    private function recoveriesQuery(Request $request)
    {
        $query = CpfAdvanceRecovery::query()
            ->join('cpf_advances', 'cpf_advance_recoveries.cpf_advance_id', '=', 'cpf_advances.id')
            ->join('employees', 'cpf_advances.employee_id', '=', 'employees.id')
            ->select(
                'cpf_advance_recoveries.*',
                'cpf_advances.advance_no as adv_no',
                'employees.name as emp_name',
                'employees.cpf_account_no as emp_acc'
            );

        if ($status = $request->input('status')) {
            $query->where('cpf_advance_recoveries.status', $status);
        }

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                    ->orWhere('employees.cpf_account_no', 'like', "%{$search}%")
                    ->orWhere('cpf_advance_recoveries.recovery_no', 'like', "%{$search}%")
                    ->orWhere('cpf_advances.advance_no', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
