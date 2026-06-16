<?php
namespace App\Http\Controllers\Employee;

use App\Exports\Employee\EmployeeImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Employee\PayScale;
use App\Services\Employee\EmployeeUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Employee bulk upload (AJAX, preview → confirm).
 *
 * Flow:
 *   1. preview() — parse + validate the uploaded file WITHOUT persisting,
 *      cache the normalised rows under a short-lived token, return a row-by-row
 *      preview with per-row validation + summary counts.
 *   2. commit()  — re-validate the cached rows (catches anything that changed
 *      since preview) and import the ones that pass, in one transaction.
 *
 * Reachable from the Settings hero "Employee Upload" tab (shown in local env).
 * Gated by employee.create so Admin and CPF Officer can use it.
 */
class EmployeeUploadController extends Controller
{
    /** Cache TTL for a previewed upload (minutes). */
    private const CACHE_TTL = 30;

    public function __construct(private EmployeeUploadService $service)
    {
    }

    /**
     * Upload page (pay scale selector + dropzone + preview area).
     */
    public function index(): View
    {
        $payScales = PayScale::active()
            ->orderByDesc('effective_year')
            ->get(['id', 'name', 'effective_year']);

        return view('settings.employee-upload', compact('payScales'));
    }

    /**
     * Download the .xlsx template.
     */
    public function template(): BinaryFileResponse
    {
        return Excel::download(new EmployeeImportTemplateExport(), 'employee-upload-template.xlsx');
    }

    /**
     * Parse + validate the uploaded file and return a preview (no persistence).
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file'         => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:4096'],
            'pay_scale_id' => ['required', 'integer', 'exists:pay_scales,id'],
        ]);

        $payScaleId = (int) $request->input('pay_scale_id');

        try {
            $result = $this->service->preview($request->file('file'), $payScaleId);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not read the file. Make sure it matches the template. (' . $e->getMessage() . ')',
            ], 422);
        }

        if (! empty($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        // Stash the normalised rows + pay scale under a token for the commit step.
        $token = (string) Str::uuid();
        Cache::put($this->cacheKey($token), [
            'pay_scale_id' => $payScaleId,
            'rows'         => $result['normalized'],
        ], now()->addMinutes(self::CACHE_TTL));

        return response()->json([
            'success' => true,
            'token'   => $token,
            'summary' => $result['summary'],
            'rows'    => $result['rows'],
        ]);
    }

    /**
     * Import the previously previewed rows that pass (re)validation.
     */
    public function commit(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $payload = Cache::get($this->cacheKey($request->input('token')));

        if (! $payload) {
            return response()->json([
                'success' => false,
                'message' => 'Your upload session expired. Please upload the file again.',
            ], 422);
        }

        $result = $this->service->commit(
            $payload['rows'],
            (int) $payload['pay_scale_id'],
            (int) $request->user()->id,
        );

        Cache::forget($this->cacheKey($request->input('token')));

        return response()->json([
            'success' => true,
            'message' => "{$result['imported']} employee(s) imported."
            . ($result['skipped'] > 0 ? " {$result['skipped']} row(s) skipped." : ''),
            'imported' => $result['imported'],
            'skipped'  => $result['skipped'],
        ]);
    }

    private function cacheKey(string $token): string
    {
        return 'employee_upload:' . $token;
    }
}
