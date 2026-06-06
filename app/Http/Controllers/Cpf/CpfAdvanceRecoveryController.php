<?php
namespace App\Http\Controllers\Cpf;

use App\Http\Requests\Advance\StoreRecoveryRequest;
use App\Models\CpfAdvance;
use App\Models\CpfAdvanceRecovery;
use App\Services\AdvanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CpfAdvanceRecoveryController extends Controller
{
    public function __construct(protected AdvanceService $advanceService)
    {}

    public function index(): View
    {
        $recoveries = CpfAdvanceRecovery::with('advance.employee')
            ->when(request('search'), fn($q) => $q->whereHas('advance.employee', fn($eq) =>
                $eq->where('name', 'like', '%' . request('search') . '%')
                    ->orWhere('cpf_account_no', 'like', '%' . request('search') . '%')
            ))
            ->latest('recovery_date')
            ->paginate(20)
            ->withQueryString();

        return view('cpf-advances.recovery.index', compact('recoveries'));
    }

    public function create(CpfAdvance $advance): View
    {
        abort_if($advance->outstanding_amount <= 0, 403, 'This advance is already fully recovered.');

        $advance->load('employee', 'recoveries');

        return view('cpf-advances.recovery.create', compact('advance'));
    }

    public function store(StoreRecoveryRequest $request, CpfAdvance $advance): RedirectResponse
    {
        abort_if($advance->outstanding_amount <= 0, 403, 'This advance is already fully recovered.');

        $this->advanceService->recovery(
            advance: $advance,
            amount: $request->validated('amount'),
            remarks: $request->validated('remarks'),
            createdBy: auth()->id(),
        );

        return redirect()->route('cpf-advances.show', $advance)
            ->with('success', 'Recovery posted and ledger updated.');
    }
}
