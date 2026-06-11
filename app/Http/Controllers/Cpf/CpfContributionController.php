<?php
namespace App\Http\Controllers\Cpf;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contribution\StoreContributionBatchRequest;
use App\Http\Requests\Contribution\UpdateContributionRequest;
use App\Models\Cpf\CpfContribution;
use App\Models\Cpf\CpfContributionBatch;
use App\Services\ContributionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CpfContributionController extends Controller
{
    public function __construct(protected ContributionService $contributionService)
    {}

    public function index(): View
    {
        $batches = CpfContributionBatch::query()
            ->with('creator')
            ->withCount('contributions')
            ->withSum('contributions as employee_total', 'employee_contribution')
            ->withSum('contributions as government_total', 'government_contribution')
            ->latest('contribution_month')
            ->get();

        return view('cpf-contributions.index', compact('batches'));
    }

    public function store(StoreContributionBatchRequest $request): JsonResponse | RedirectResponse
    {
        $date  = Carbon::parse($request->validated('contribution_month'));
        $batch = $this->contributionService->generateBatch(
            month: $date->month,
            year: $date->year,
            createdBy: auth()->id(),
        );

        $message  = 'Draft contribution batch generated for ' . $batch->month_label . '.';
        $redirect = route('cpf-contributions.show', $batch);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'redirect' => $redirect]);
        }

        return redirect($redirect)->with('success', $message);
    }

    public function show(CpfContributionBatch $batch): View
    {
        $batch->load(['contributions.employee', 'submittedBy', 'approvedBy', 'reversedBy']);

        return view('cpf-contributions.show', compact('batch'));
    }

    public function regenerate(CpfContributionBatch $batch): RedirectResponse
    {
        try {
            $this->contributionService->regenerateBatch($batch);

            return back()->with('success', 'Batch regenerated from current salaries. Manual edits were discarded.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * AJAX inline edit of a single draft contribution row.
     */
    public function updateContribution(
        UpdateContributionRequest $request,
        CpfContributionBatch $batch,
        CpfContribution $contribution
    ): JsonResponse {
        abort_if($contribution->cpf_contribution_batch_id !== $batch->id, 404);

        try {
            $updated = $this->contributionService->updateContribution($contribution, $request->validated());

            return response()->json([
                'success'      => true,
                'message'      => 'Contribution updated.',
                'contribution' => [
                    'id'                      => $updated->id,
                    'basic_salary'            => $updated->basic_salary,
                    'employee_contribution'   => $updated->employee_contribution,
                    'government_contribution' => $updated->government_contribution,
                    'total'                   => $updated->totalContribution(),
                    'remarks'                 => $updated->remarks,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function submit(CpfContributionBatch $batch): RedirectResponse
    {
        try {
            $this->contributionService->submitBatch($batch, auth()->id());

            return back()->with('success', 'Batch submitted for admin approval.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(CpfContributionBatch $batch): RedirectResponse
    {
        try {
            $this->contributionService->approveBatch($batch, auth()->id());

            return back()->with('success', 'Batch approved. CPF ledger entries posted for ' . $batch->month_label . '.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, CpfContributionBatch $batch): RedirectResponse
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        try {
            $this->contributionService->rejectBatch($batch, $request->input('remarks'));

            return back()->with('warning', 'Batch sent back to the CPF Officer for correction.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reverse(CpfContributionBatch $batch): RedirectResponse
    {
        try {
            $this->contributionService->reverseBatch($batch, auth()->id());

            return back()->with('success', 'Batch reversed. Reversal ledger entries posted.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
