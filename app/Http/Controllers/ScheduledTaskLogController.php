<?php
namespace App\Http\Controllers;

use App\Models\ScheduledTaskLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Read-only browser for scheduled (cron) task runs (Admin only).
 *
 * Mirrors the Audit Log page: an index shell + a server-side DataTable feed,
 * plus a JSON detail endpoint for the per-run modal. Rows are written by
 * App\Listeners\ScheduledTaskLogger.
 */
class ScheduledTaskLogController extends Controller
{
    /**
     * Index shell + filter options.
     */
    public function index(): View
    {
        // Distinct commands → friendly label, for the filter dropdown.
        $commands = ScheduledTaskLog::query()
            ->select('command')
            ->distinct()
            ->orderBy('command')
            ->pluck('command')
            ->mapWithKeys(fn($command) => [$command => ScheduledTaskLog::labelFor($command)]);

        $statuses = [
            ScheduledTaskLog::STATUS_COMPLETED,
            ScheduledTaskLog::STATUS_FAILED,
            ScheduledTaskLog::STATUS_SKIPPED,
            ScheduledTaskLog::STATUS_RUNNING,
        ];

        return view('scheduled-tasks.index', compact('commands', 'statuses'));
    }

    /**
     * Server-side DataTable feed.
     */
    public function data(Request $request): JsonResponse
    {
        $base = ScheduledTaskLog::query();

        $recordsTotal = (clone $base)->count();

        // ── filters ──────────────────────────────────────────────────────
        if ($status = $request->input('status')) {
            $base->where('status', $status);
        }

        if ($command = $request->input('command')) {
            $base->where('command', $command);
        }

        if ($from = $request->input('date_from')) {
            try {
                $base->where('started_at', '>=', Carbon::parse($from)->startOfDay());
            } catch (\Throwable $e) {
            }
        }

        if ($to = $request->input('date_to')) {
            try {
                $base->where('started_at', '<=', Carbon::parse($to)->endOfDay());
            } catch (\Throwable $e) {
            }
        }

        // ── global search ────────────────────────────────────────────────
        $search = (string) $request->input('search.value', '');
        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%")
                    ->orWhere('command', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('output', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        // ── ordering (column index → DB column) ──────────────────────────
        $orderable = [
            null,          // 0  row #
            'label',       // 1  Task (friendly name)
            'status',      // 2
            'exit_code',   // 3
            'runtime',     // 4
            'started_at',  // 5  (default)
            'finished_at', // 6
            null,          // 7  actions
        ];

        $orderColIndex = (int) $request->input('order.0.column', 5);
        $orderDir      = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderColumn   = $orderable[$orderColIndex] ?? 'started_at';

        $base->orderBy($orderColumn ?: 'started_at', $orderColumn ? $orderDir : 'desc');

        // ── pagination ───────────────────────────────────────────────────
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $base->skip($start)->take($length);
        }

        $data = $base->get()->map(function (ScheduledTaskLog $log) {
            return [
                'id'        => $log->id,
                'task'      => '<span class="fw-bold text-gray-900">' . e($log->displayName()) . '</span>',
                'status'    => '<span class="badge ' . $log->statusBadgeClass() . ' text-capitalize">' . e($log->status) . '</span>',
                'exit_code' => $log->exit_code !== null ? e((string) $log->exit_code) : '<span class="text-muted">—</span>',
                'runtime'   => e($log->runtimeForHumans()),
                'started'   => optional($log->started_at)->format('h:i A, d M Y') ?? '—',
                'finished'  => optional($log->finished_at)->format('h:i A, d M Y') ?? '<span class="text-muted">—</span>',
                'actions'   => '<button type="button" class="btn btn-sm btn-icon btn-light-primary w-30px h-30px js-task-details" '
                . 'data-id="' . $log->id . '" title="View details"><i class="ki-outline ki-eye fs-3"></i></button>',
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
     * JSON detail for the per-run modal.
     */
    public function show(ScheduledTaskLog $taskLog): JsonResponse
    {
        return response()->json([
            'success' => true,
            'log'     => [
                'task'        => $taskLog->displayName(),
                'command'     => $taskLog->command,
                'description' => $taskLog->description,
                'status'      => $taskLog->status,
                'exit_code'   => $taskLog->exit_code,
                'runtime'     => $taskLog->runtimeForHumans(),
                'started_at'  => optional($taskLog->started_at)->format('h:i:s A, d M Y'),
                'finished_at' => optional($taskLog->finished_at)->format('h:i:s A, d M Y'),
                'output'      => $taskLog->output,
            ],
        ]);
    }
}
