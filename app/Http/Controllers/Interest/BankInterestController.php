<?php
namespace App\Http\Controllers\Interest;

use App\Enums\BatchStatus;
use App\Exports\InterestBatchesExport;
use App\Exports\InterestDistributionsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Interest\StoreInterestBatchRequest;
use App\Models\Interest\BankInterestBatch;
use App\Services\InterestDistributionService;
use App\Support\FiscalYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class BankInterestController extends Controller
{
    public function __construct(protected InterestDistributionService $interestService)
    {}

    /*
    |--------------------------------------------------------------------------
    | Pages (the list shell is fed by the server-side data() endpoint)
    |--------------------------------------------------------------------------
    */
    public function index(): View
    {
        $statuses = BatchStatus::options();

        return view('bank-interest.index', compact('statuses'));
    }

    public function distribute(): View
    {
        // Dates that already have a batch (one batch per cut-off date).
        $taken = BankInterestBatch::pluck('distribution_date')
            ->map(fn($d) => $d->toDateString())
            ->all();

        // Bi-annual cut-offs (31 Dec / 30 Jun) from the start of FY 2025-26
        // (01 Jul 2025) up to today, newest first. Future dates excluded.
        $minDate = \Carbon\Carbon::create(2025, 7, 1);
        $today   = today();
        $cutoffs = [];

        for ($y = (int) $today->year + 1; $y >= 2025; $y--) {
            foreach ([['m' => 12, 'd' => 31, 'lbl' => '31 December'], ['m' => 6, 'd' => 30, 'lbl' => '30 June']] as $cd) {
                $date = \Carbon\Carbon::create($y, $cd['m'], $cd['d']);
                if ($date->lt($minDate) || $date->gt($today)) {
                    continue;
                }

                $value     = $date->toDateString();
                $cutoffs[] = [
                    'value' => $value,
                    'label' => $cd['lbl'] . ' ' . $y,
                    'fy'    => FiscalYearService::fromDate($date),
                    'taken' => in_array($value, $taken, true),
                ];
            }
        }

        // Newest first.
        usort($cutoffs, fn($a, $b) => strcmp($b['value'], $a['value']));

        return view('bank-interest.distribute', compact('cutoffs'));
    }

    public function store(StoreInterestBatchRequest $request): JsonResponse | RedirectResponse
    {
        try {
            $batch = $this->interestService->createBatch($request->validated(), auth()->id());
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        $message  = 'Draft distribution generated for cut-off ' . $batch->cut_off_label . '. Review then submit.';
        $redirect = route('bank-interest.show', $batch);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'redirect' => $redirect]);
        }

        return redirect($redirect)->with('success', $message);
    }

    public function show(BankInterestBatch $batch): View
    {
        $batch->load(['submittedBy', 'approvedBy', 'reversedBy']);

        return view('bank-interest.show', compact('batch'));
    }

    /**
     * Server-side feed for a single batch's per-member distribution table.
     */
    public function distributions(Request $request, BankInterestBatch $batch): JsonResponse
    {
        $recordsTotal = $batch->distributions()->count();

        $query = $batch->distributions()
            ->join('employees', 'bank_interest_distributions.employee_id', '=', 'employees.id')
            ->select(
                'bank_interest_distributions.*',
                'employees.name as emp_name',
                'employees.designation as emp_designation',
                'employees.cpf_account_no as emp_acc'
            );

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                    ->orWhere('employees.cpf_account_no', 'like', "%{$search}%")
                    ->orWhere('employees.designation', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderMap = [
            1 => 'employees.cpf_account_no',
            2 => 'employees.name',
            3 => 'bank_interest_distributions.eligible_balance',
            4 => 'bank_interest_distributions.eligible_balance', // ratio is monotonic with balance
            5 => 'bank_interest_distributions.interest_amount',
            6 => 'bank_interest_distributions.interest_amount',
        ];
        $colIdx = (int) $request->input('order.0.column', 6);
        $dir    = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderMap[$colIdx] ?? 'bank_interest_distributions.interest_amount', $dir)
            ->orderBy('bank_interest_distributions.id', $dir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $i    = $start;
        $data = $query->get()->map(function ($d) use (&$i) {
            $i++;

            return [
                'DT_RowIndex' => $i,
                'cpf_acc'     => '<span class="fw-bold text-gray-800">' . e($d->emp_acc) . '</span>',
                'member'      => '<span class="fw-bold text-gray-800">' . e($d->emp_name) . '</span>'
                . '<span class="text-muted fs-8 d-block">' . e($d->emp_designation ?? '—') . '</span>',
                'balance'     => number_format((int) $d->eligible_balance),
                'ratio'       => number_format($d->ratio * 100, 4),
                'calculated'  => number_format($d->calculated_interest, 2),
                'allocated'   => number_format((int) $d->interest_amount),
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /**
     * Export a single batch's per-member distribution (xlsx / csv / pdf),
     * honouring the table's current search term.
     */
    public function exportDistributions(Request $request, BankInterestBatch $batch)
    {
        $query = $batch->distributions()
            ->join('employees', 'bank_interest_distributions.employee_id', '=', 'employees.id')
            ->select(
                'bank_interest_distributions.*',
                'employees.name as emp_name',
                'employees.designation as emp_designation',
                'employees.cpf_account_no as emp_acc'
            );

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                    ->orWhere('employees.cpf_account_no', 'like', "%{$search}%")
                    ->orWhere('employees.designation', 'like', "%{$search}%");
            });
        }

        $rows = $query
            ->orderByDesc('bank_interest_distributions.interest_amount')
            ->orderBy('bank_interest_distributions.id')
            ->get();

        $filename = 'interest-distribution-' . $batch->distribution_date->format('Ymd') . '-' . now()->format('His');

        return match ($request->input('format', 'xlsx')) {
            'csv'   => Excel::download(new InterestDistributionsExport($batch, $rows), "$filename.csv", ExcelFormat::CSV),
            'pdf'   => Pdf::loadView('exports.bank-interest.distributions-pdf', [
                'batch'       => $batch,
                'rows'        => $rows,
                'generatedAt' => now(),
            ])->setPaper('a4', 'landscape')->download("$filename.pdf"),
            default => Excel::download(new InterestDistributionsExport($batch, $rows), "$filename.xlsx"),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow transitions (AJAX — JSON, with redirect fallback)
    |--------------------------------------------------------------------------
    */
    public function regenerate(Request $request, BankInterestBatch $batch): JsonResponse | RedirectResponse
    {
        return $this->run($request, fn() => $this->interestService->regenerate($batch),
            'Distribution recalculated from current cut-off balances.');
    }

    public function submit(Request $request, BankInterestBatch $batch): JsonResponse | RedirectResponse
    {
        return $this->run($request, fn() => $this->interestService->submitBatch($batch, auth()->id()),
            'Distribution submitted for admin approval.');
    }

    public function approve(Request $request, BankInterestBatch $batch): JsonResponse | RedirectResponse
    {
        return $this->run($request, fn() => $this->interestService->approveBatch($batch, auth()->id()),
            'Distribution approved. CPF ledger credits posted for cut-off ' . $batch->cut_off_label . '.');
    }

    public function reject(Request $request, BankInterestBatch $batch): JsonResponse | RedirectResponse
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        // Strip any HTML and trim before storing/displaying.
        $remarks = $request->filled('remarks')
            ? trim(strip_tags((string) $request->input('remarks')))
            : null;
        $remarks = $remarks === '' ? null : $remarks;

        return $this->run($request, fn() => $this->interestService->rejectBatch($batch, $remarks),
            'Distribution sent back to the CPF Officer for correction.', 'warning');
    }

    public function reverse(Request $request, BankInterestBatch $batch): JsonResponse | RedirectResponse
    {
        return $this->run($request, fn() => $this->interestService->reverseBatch($batch, auth()->id()),
            'Distribution reversed. Reversal ledger entries posted.');
    }

    /**
     * Run a workflow action and respond as JSON (AJAX) or a redirect-back.
     */
    private function run(Request $request, \Closure $action, string $message, string $level = 'success'): JsonResponse | RedirectResponse
    {
        try {
            $action();
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'reload' => true]);
        }

        return back()->with($level, $message);
    }

    /*
    |--------------------------------------------------------------------------
    | Batch list — server-side feed + export
    |--------------------------------------------------------------------------
    */
    public function data(Request $request): JsonResponse
    {
        $query = $this->batchesQuery($request);

        $recordsTotal    = BankInterestBatch::count();
        $recordsFiltered = (clone $query)->count();

        $orderMap = [
            1 => 'distribution_date',
            2 => 'fiscal_year',
            3 => 'total_interest_amount',
            4 => 'distributions_count',
            5 => 'distributed_total',
            6 => 'status',
        ];
        $colIdx = (int) $request->input('order.0.column', 1);
        $dir    = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderMap[$colIdx] ?? 'distribution_date', $dir)->orderBy('id', $dir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $i    = $start;
        $data = $query->get()->map(function (BankInterestBatch $b) use (&$i) {
            $i++;
            $url    = route('bank-interest.show', $b->id);
            $status = $b->status;

            return [
                'DT_RowIndex' => $i,
                'reference'   => '<a href="' . $url . '" class="text-gray-800 text-hover-primary fw-bold">' . e($b->reference_no) . '</a>'
                . '<span class="text-muted fs-8 d-block">Batch #' . $b->id . '</span>',
                'cut_off'     => $b->distribution_date->format('d M Y'),
                'fiscal_year' => e($b->fiscal_year),
                'interest'    => number_format((int) $b->total_interest_amount),
                'members'     => (int) ($b->distributions_count ?? 0),
                'distributed' => number_format((int) ($b->distributed_total ?? 0)),
                'status'      => '<span class="' . $status->badgeClass() . '"><i class="' . $status->icon() . ' me-1"></i>' . $status->label() . '</span>',
                'actions'     => '<a href="' . $url . '" title="View" class="btn btn-icon text-hover-primary w-30px h-30px"><i class="ki-outline ki-eye fs-2"></i></a>',
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
        $rows     = $this->batchesQuery($request)->orderBy('distribution_date', 'desc')->get();
        $filename = 'bank-interest-batches-' . now()->format('Ymd-His');

        return match ($request->input('format', 'xlsx')) {
            'csv'   => Excel::download(new InterestBatchesExport($rows), "$filename.csv", ExcelFormat::CSV),
            'pdf'   => Pdf::loadView('exports.bank-interest.batches-pdf', ['rows' => $rows, 'generatedAt' => now()])
                ->setPaper('a4', 'landscape')->download("$filename.pdf"),
            default => Excel::download(new InterestBatchesExport($rows), "$filename.xlsx"),
        };
    }

    /**
     * Shared filtered/aggregated query for the list feed and exports.
     */
    private function batchesQuery(Request $request)
    {
        $query = BankInterestBatch::query()
            ->withCount('distributions')
            ->withSum('distributions as distributed_total', 'interest_amount');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($fiscalYear = $request->input('fiscal_year')) {
            $query->where('fiscal_year', $fiscalYear);
        }

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('fiscal_year', 'like', "%{$search}%")
                    ->orWhereRaw("DATE_FORMAT(distribution_date, '%Y%m%d') like ?", ["%{$search}%"])
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
