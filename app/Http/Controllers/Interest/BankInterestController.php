<?php
namespace App\Http\Controllers\Interest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interest\StoreInterestBatchRequest;
use App\Models\BankInterestBatch;
use App\Services\InterestDistributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BankInterestController extends Controller
{
    public function __construct(protected InterestDistributionService $interestService)
    {}

    public function index(): View
    {
        $batches = BankInterestBatch::latest('distribution_date')
            ->paginate(20)
            ->withQueryString();

        return view('bank-interest.index', compact('batches'));
    }

    public function distribute(): View
    {
        return view('bank-interest.distribute');
    }

    public function store(StoreInterestBatchRequest $request): RedirectResponse
    {
        $batch = BankInterestBatch::create([
             ...$request->validated(),
            'status'     => \App\Enums\BatchStatus::DRAFT,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('bank-interest.show', $batch)
            ->with('success', 'Interest batch created. Review then generate distributions.');
    }

    public function show(BankInterestBatch $batch): View
    {
        $batch->load('distributions.employee');

        return view('bank-interest.show', compact('batch'));
    }

    public function generate(BankInterestBatch $batch): RedirectResponse
    {
        abort_if(! $batch->canBeSubmitted(), 403, 'Only draft batches can generate distributions.');

        $this->interestService->generate($batch, auth()->id());

        return redirect()->route('bank-interest.show', $batch)
            ->with('success', 'Distributions generated. Review before submitting.');
    }

    public function submit(BankInterestBatch $batch): RedirectResponse
    {
        $this->interestService->submit($batch, auth()->id());

        return redirect()->route('bank-interest.show', $batch)
            ->with('success', 'Interest batch submitted and ledger entries posted.');
    }

    public function reverse(BankInterestBatch $batch): RedirectResponse
    {
        $this->interestService->reverse($batch);

        return redirect()->route('bank-interest.show', $batch)
            ->with('success', 'Interest batch reversed successfully.');
    }
}
