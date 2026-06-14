<?php
namespace App\Http\Controllers\Setting;

use App\Exports\PayScaleImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\PayScaleUploadRequest;
use App\Models\Employee\PayScale;
use App\Models\Employee\PayScaleStep;
use App\Services\PayScaleUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pay scale management (Admin only) — redesigned around bulk upload.
 *
 *   - index()        : list every pay scale (read-only) + activate/deactivate.
 *   - create()       : upload page (meta form + grade/step spreadsheet).
 *   - preview()      : AJAX parse + validate (no persistence).
 *   - store()        : AJAX commit — create the pay scale + steps from the
 *                      previewed data (all-or-nothing).
 *   - show()         : read-only grade x step grid.
 *   - toggleActive() : activate / deactivate, enforcing "at most one active".
 *
 * Pay scales are immutable after creation (no edit / no per-step edit) and are
 * never deleted - only deactivated.
 */
class PayScaleController extends Controller
{
    private const CACHE_TTL = 30; // minutes

    public function __construct(private PayScaleUploadService $service)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        $payScales = PayScale::query()
            ->select('pay_scales.*')
            ->selectSub(
                PayScaleStep::query()
                    ->whereColumn('pay_scale_steps.pay_scale_id', 'pay_scales.id')
                    ->selectRaw('COUNT(*)'),
                'steps_count'
            )
            ->selectSub(
                PayScaleStep::query()
                    ->whereColumn('pay_scale_steps.pay_scale_id', 'pay_scales.id')
                    ->selectRaw('COUNT(DISTINCT grade)'),
                'grades_count'
            )
            ->orderByDesc('is_active')
            ->orderByDesc('effective_year')
            ->get();

        return view('settings.payscale.index', compact('payScales'));
    }

    /*
    |--------------------------------------------------------------------------
    | Upload -> preview -> commit
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        return view('settings.payscale.upload');
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new PayScaleImportTemplateExport(), 'pay-scale-template.xlsx');
    }

    public function preview(PayScaleUploadRequest $request): JsonResponse
    {
        $meta = [
            'name'           => $request->input('name'),
            'effective_year' => (int) $request->input('effective_year'),
            'effective_from' => $request->input('effective_from'),
            'effective_to'   => $request->input('effective_to'),
            'is_active'      => (bool) $request->boolean('is_active'),
        ];

        try {
            $result = $this->service->preview($request->file('file'));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not read the file. Make sure it matches the template. (' . $e->getMessage() . ')',
            ], 422);
        }

        if (! empty($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        $token = (string) Str::uuid();
        Cache::put($this->cacheKey($token), [
            'meta' => $meta,
            'rows' => $result['normalized'],
        ], now()->addMinutes(self::CACHE_TTL));

        return response()->json([
            'success' => true,
            'token'   => $token,
            'meta'    => $meta,
            'summary' => $result['summary'],
            'grid'    => $result['grid'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $payload = Cache::get($this->cacheKey($request->input('token')));

        if (! $payload) {
            return response()->json([
                'success' => false,
                'message' => 'Your upload session expired. Please upload the file again.',
            ], 422);
        }

        try {
            $payScale = $this->service->commit($payload['rows'], $payload['meta']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        Cache::forget($this->cacheKey($request->input('token')));

        return response()->json([
            'success'  => true,
            'message'  => 'Pay scale "' . $payScale->name . '" created successfully.',
            'show_url' => route('payscale.show', $payScale),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Read-only grid + activation
    |--------------------------------------------------------------------------
    */

    public function show(PayScale $payScale): View
    {
        $steps = $payScale->steps()->orderBy('grade')->orderBy('step')->get();

        $maxStep = (int) ($steps->max('step') ?? 0);

        // grade => [step => basic_salary]
        $matrix = $steps->groupBy('grade')->map(
            fn($group) => $group->keyBy('step')->map->basic_salary
        )->sortKeys();

        return view('settings.payscale.show', compact('payScale', 'matrix', 'maxStep'));
    }

    public function toggleActive(PayScale $payScale): JsonResponse
    {
        DB::transaction(function () use ($payScale) {
            if ($payScale->is_active) {
                $payScale->update(['is_active' => false]);
            } else {
                // At most one active pay scale.
                PayScale::where('id', '!=', $payScale->id)->where('is_active', true)->update(['is_active' => false]);
                $payScale->update(['is_active' => true]);
            }
        });

        return response()->json([
            'success'   => true,
            'is_active' => $payScale->is_active,
            'message'   => $payScale->is_active ? 'Pay scale activated.' : 'Pay scale deactivated.',
        ]);
    }

    private function cacheKey(string $token): string
    {
        return 'payscale_upload:' . $token;
    }
}
