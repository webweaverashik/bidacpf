<?php
namespace App\Http\Controllers\Cpf;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advance\ApproveAdvanceRequest;
use App\Http\Requests\Advance\StoreAdvanceRequest;
use App\Models\Cpf\CpfAdvance;
use App\Models\Employee\Employee;
use App\Services\AdvanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CpfAdvanceController extends Controller
{
    public function __construct(protected AdvanceService $advanceService)
    {}

    public function index(): View
    {
        $advances = CpfAdvance::with('employee')
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->when(request('search'), fn($q) => $q->whereHas('employee', fn($eq) =>
                $eq->where('name', 'like', '%' . request('search') . '%')
                    ->orWhere('cpf_account_no', 'like', '%' . request('search') . '%')
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('cpf-advances.index', compact('advances'));
    }

    public function outstanding(): View
    {
        $advances = CpfAdvance::with('employee')
            ->approved()
            ->where('outstanding_amount', '>', 0)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('cpf-advances.outstanding', compact('advances'));
    }

    public function create(): View
    {
        $employees = Employee::active()->orderBy('name')->get(['id', 'name', 'cpf_account_no']);

        return view('cpf-advances.create', compact('employees'));
    }

    public function store(StoreAdvanceRequest $request): RedirectResponse
    {
        $advance = $this->advanceService->create($request->validated());

        return redirect()->route('cpf-advances.show', $advance)
            ->with('success', 'Advance application submitted successfully.');
    }

    public function show(CpfAdvance $advance): View
    {
        $advance->load('employee', 'recoveries', 'approver', 'attachments');

        return view('cpf-advances.show', compact('advance'));
    }

    public function approve(ApproveAdvanceRequest $request, CpfAdvance $advance): RedirectResponse
    {
        $this->advanceService->approve($advance, auth()->id());

        return redirect()->route('cpf-advances.show', $advance)
            ->with('success', 'Advance approved and disbursement posted to ledger.');
    }

    public function cancel(CpfAdvance $advance): RedirectResponse
    {
        abort_if(! $advance->status->value === 'pending', 403, 'Only pending advances can be cancelled.');

        $advance->update(['status' => \App\Enums\AdvanceStatus::CANCELLED]);

        return redirect()->route('cpf-advances.show', $advance)
            ->with('success', 'Advance cancelled successfully.');
    }
}
