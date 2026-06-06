<?php
namespace App\Http\Controllers;

use App\Http\Requests\Contribution\StoreContributionBatchRequest;
use App\Models\CpfContributionBatch;
use App\Services\ContributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CpfContributionController extends Controller
{
    public function __construct(protected ContributionService $contributionService)
    {}

    public function index(): View
    {
        $batches = CpfContributionBatch::latest('contribution_month')
            ->paginate(20)
            ->withQueryString();

        return view('cpf-contributions.index', compact('batches'));
    }

    public function store(StoreContributionBatchRequest $request): RedirectResponse
    {
        $date  = \Carbon\Carbon::parse($request->validated('contribution_month'));
        $batch = $this->contributionService->generateBatch(
            month: $date->month,
            year: $date->year,
            createdBy: auth()->id(),
        );

        return redirect()->route('cpf-contributions.show', $batch)
            ->with('success', 'Contribution batch generated successfully.');
    }

    public function show(CpfContributionBatch $batch): View
    {
        $batch->load('contributions.employee');

        return view('cpf-contributions.show', compact('batch'));
    }

    public function submit(CpfContributionBatch $batch): RedirectResponse
    {
        $this->contributionService->submitBatch($batch, auth()->id());

        return redirect()->route('cpf-contributions.show', $batch)
            ->with('success', 'Batch submitted and ledger entries posted.');
    }

    public function reverse(CpfContributionBatch $batch): RedirectResponse
    {
        $this->contributionService->reverseBatch($batch);

        return redirect()->route('cpf-contributions.show', $batch)
            ->with('success', 'Batch reversed successfully.');
    }
}
