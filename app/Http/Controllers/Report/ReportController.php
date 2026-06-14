<?php
namespace App\Http\Controllers\Report;

use App\Exports\Reports\ReportExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Support\FiscalYearService;
use App\Support\ReportRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Single-page Reporting hub.
 *
 *   index()    → the page: a grouped report <select> (role-filtered) + an
 *                empty parameter panel and preview pane.
 *   params()   → AJAX: parameter inputs (with resolved option lists) for a
 *                chosen report, plus its kind + supported export formats.
 *   preview()  → AJAX: the on-screen result — a data table for summary reports,
 *                or an isolated certificate preview for certificates.
 *   generate() → streams the chosen report as PDF / Excel / CSV.
 *
 * Access is layered: the routes require report.view / report.export, and each
 * individual report additionally enforces its own gate through ReportRegistry
 * (so e.g. the audit reports stay Admin-only and module reports follow the
 * viewer's module permissions).
 */
class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService)
    {}

    /*
    |--------------------------------------------------------------------------
    | Page
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): View
    {
        $grouped = ReportRegistry::groupedFor($request->user());

        return view('reports.index', [
            'grouped'   => $grouped,
            'canExport' => (bool) $request->user()?->can('report.export'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX — parameter panel for a chosen report
    |--------------------------------------------------------------------------
    */
    public function params(Request $request): JsonResponse
    {
        $def = $this->authorizeReport($request, $request->input('report'));

        // Resolve each declared parameter and attach its option list (if any).
        $params = ReportRegistry::paramsFor($def['key'])->map(function (array $param) {
            if (is_string($param['options'] ?? null)) {
                $param['options'] = $this->reportService->options($param['options']);
            }

            return $param;
        });

        $html = view('reports.partials.param-panel', [
            'report'      => $def,
            'params'      => $params,
            'currentFy'   => FiscalYearService::current(),
            'defaultFrom' => Carbon::now()->startOfYear()->toDateString(),
            'defaultTo'   => Carbon::now()->toDateString(),
        ])->render();

        return response()->json([
            'html'    => $html,
            'kind'    => $def['kind'],
            'formats' => $def['formats'] ?? ['pdf'],
            'desc'    => $def['desc'] ?? '',
            'label'   => $def['label'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX — on-screen preview
    |--------------------------------------------------------------------------
    */
    public function preview(Request $request): JsonResponse
    {
        $def    = $this->authorizeReport($request, $request->input('report'));
        $params = $this->collectParams($request, $def);

        try {
            if ($def['kind'] === ReportRegistry::KIND_CERTIFICATE) {
                $this->assertRequired($def, $params);
                $payload = $this->reportService->certificate($def['key'], $params);
                $doc     = view($payload['view'], $payload['data'])->render();

                $html = view('reports.previews.certificate', [
                    'doc'    => $doc,
                    'report' => $def,
                ])->render();
            } else {
                $report = $this->reportService->build($def['key'], $params);

                // Paginate the preview (the full data set always ships in the
                // PDF/Excel/CSV download). Serial numbers are baked into the
                // rows by ReportService, so they stay continuous across pages.
                $perPage  = 20;
                $total    = count($report['rows'] ?? []);
                $lastPage = max(1, (int) ceil($total / $perPage));
                $page     = min(max(1, (int) $request->input('page', 1)), $lastPage);
                $offset   = ($page - 1) * $perPage;

                $html = view('reports.previews.table', [
                    'report'   => $report,
                    'pageRows' => array_slice($report['rows'] ?? [], $offset, $perPage),
                    'page'     => $page,
                    'perPage'  => $perPage,
                    'total'    => $total,
                    'lastPage' => $lastPage,
                    'offset'   => $offset,
                ])->render();
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['html' => $html]);
    }

    /*
    |--------------------------------------------------------------------------
    | Download — PDF / Excel / CSV
    |--------------------------------------------------------------------------
    */
    public function generate(Request $request)
    {
        $def    = $this->authorizeReport($request, $request->input('report'), 'report.export');
        $params = $this->collectParams($request, $def);
        $format = $request->input('format', 'pdf');

        if (! in_array($format, $def['formats'] ?? ['pdf'], true)) {
            abort(422, 'This report does not support the requested format.');
        }

        $this->assertRequired($def, $params);

        return $def['kind'] === ReportRegistry::KIND_CERTIFICATE
            ? $this->generateCertificate($def, $params, $format)
            : $this->generateSummary($def, $params, $format);
    }

    /*
    |--------------------------------------------------------------------------
    | Generators
    |--------------------------------------------------------------------------
    */
    private function generateSummary(array $def, array $params, string $format)
    {
        $report   = $this->reportService->build($def['key'], $params);
        $filename = $this->safeFilename($def['key'] . '-' . now()->format('Ymd-His'));
        $orient   = $def['orient'] ?? 'landscape';

        return match ($format) {
            'csv'   => Excel::download(new ReportExport($report), "$filename.csv", ExcelFormat::CSV),
            'xlsx'  => Excel::download(new ReportExport($report), "$filename.xlsx"),
            'pdf'   => Pdf::loadView('exports.reports.report-table', [
                'report'      => $report,
                'generatedAt' => now(),
            ])->setPaper('a4', $orient)->download("$filename.pdf"),
            default => abort(422, 'Unsupported format.'),
        };
    }

    private function generateCertificate(array $def, array $params, string $format)
    {
        $payload  = $this->reportService->certificate($def['key'], $params);
        $orient   = $def['orient'] ?? 'portrait';
        $filename = $this->safeFilename($payload['filename']);

        if ($format === 'pdf') {
            return Pdf::loadView($payload['view'], $payload['data'])
                ->setPaper('a4', $orient)
                ->download($filename . '.pdf');
        }

        // Excel fallback for certificates that opt in (currently the annual
        // statement): emit the underlying ledger lines as a styled workbook.
        if ($format === 'xlsx' && $def['key'] === 'cert_annual_statement') {
            $report = $this->annualStatementEnvelope($payload['data']);

            return Excel::download(new ReportExport($report), $filename . '.xlsx');
        }

        abort(422, 'This certificate is only available as PDF.');
    }

    /**
     * Build a tabular envelope from the annual-statement payload so it can be
     * exported to Excel through the generic ReportExport.
     */
    private function annualStatementEnvelope(array $data): array
    {
        $employee = $data['employee'];
        $i        = 0;

        $rows = [[
            '', $employee->name . ' — Opening Balance', '', '', '',
            number_format((int) $data['opening']),
        ]];

        foreach ($data['entries'] as $e) {
            $rows[] = [
                ++$i,
                $e->transaction_date->format('d-M-Y'),
                $e->transaction_type->label(),
                $e->debit > 0 ? number_format((int) $e->debit) : '',
                $e->credit > 0 ? number_format((int) $e->credit) : '',
                number_format((int) $e->balance),
            ];
        }

        return [
            'title'    => 'Annual CPF Statement',
            'subtitle' => $employee->name . ' (' . $employee->cpf_account_no . ') · FY ' . $data['fiscalYear'],
            'meta'     => [
                ['label' => 'Designation', 'value' => $employee->designation],
                ['label' => 'Opening Balance', 'value' => 'Tk ' . number_format((int) $data['opening'])],
                ['label' => 'Closing Balance', 'value' => 'Tk ' . number_format((int) $data['closing'])],
            ],
            'headings' => ['#', 'Date', 'Type', 'Debit (Tk)', 'Credit (Tk)', 'Balance (Tk)'],
            'aligns'   => ['center', 'center', 'left', 'num', 'num', 'num'],
            'rows'     => $rows,
            'summary'  => [['label' => 'Closing Balance (Tk)', 'value' => number_format((int) $data['closing']), 'span' => 5]],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Strip characters that are illegal in a download filename — notably the
     * "/" and "\" that appear in document numbers like STL/2024/00001 or in
     * CPF account / reference numbers, which would otherwise make DomPDF throw.
     */
    private function safeFilename(string $name): string
    {
        $name = str_replace(['/', '\\'], '-', $name);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name);
        $name = trim((string) $name, '-_.');

        return $name !== '' ? $name : 'document';
    }

    /**
     * Resolve the report definition for $key and enforce both its registry gate
     * and (optionally) an extra permission such as report.export.
     */
    private function authorizeReport(Request $request, ?string $key, ?string $extra = null): array
    {
        $def = $key ? ReportRegistry::find($key) : null;

        abort_if($def === null, 404, 'Unknown report.');
        abort_unless(ReportRegistry::allows($request->user(), $def), 403, 'You are not authorised to run this report.');

        if ($extra) {
            abort_unless($request->user()?->can($extra), 403, 'You are not authorised to export reports.');
        }

        return $def;
    }

    /**
     * Pull only the parameters this report declares from the request, so no
     * unexpected filters can be injected.
     */
    private function collectParams(Request $request, array $def): array
    {
        $out = [];
        foreach ($def['params'] ?? [] as $paramKey) {
            $value = $request->input($paramKey);
            if ($value !== null && $value !== '') {
                $out[$paramKey] = $value;
            }
        }

        return $out;
    }

    /**
     * Ensure all parameters flagged "required" are present.
     */
    private function assertRequired(array $def, array $params): void
    {
        $meta = ReportRegistry::paramMeta();

        foreach ($def['params'] ?? [] as $paramKey) {
            $required = $meta[$paramKey]['required'] ?? false;
            if ($required && empty($params[$paramKey])) {
                $label = $meta[$paramKey]['label'] ?? $paramKey;
                abort(422, "Please select {$label} to run this report.");
            }
        }
    }
}
