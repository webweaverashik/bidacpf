<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * Per-user notification centre.
 *
 * Lists the signed-in user's own database notifications (written by
 * App\Notifications\SystemEventNotification) with an index shell + a
 * server-side DataTable feed, mirroring the Audit Log / Scheduled Task pages.
 * "Open" marks a row read and forwards to its deep link; users can also mark
 * everything read or delete individual rows.
 *
 * No permission gate — every authenticated user sees only their own
 * notifications (scoped by notifiable). auth + isLoggedIn are applied by the
 * module loader.
 */
class NotificationController extends Controller
{
    /** category key → friendly label, for the filter + the Type column. */
    private const CATEGORY_LABELS = [
        'contribution'   => 'Contribution',
        'advance'        => 'Advance',
        'recovery'       => 'Recovery',
        'settlement'     => 'Settlement',
        'scheduled_task' => 'Scheduled Task',
    ];

    /**
     * Index shell + filter options.
     */
    public function index(): View
    {
        $categories = self::CATEGORY_LABELS;

        return view('notifications.index', compact('categories'));
    }

    /**
     * Server-side DataTable feed (current user's notifications only).
     */
    public function data(Request $request): JsonResponse
    {
        $base = DatabaseNotification::query()
            ->where('notifiable_type', $request->user()->getMorphClass())
            ->where('notifiable_id', $request->user()->getKey());

        $recordsTotal = (clone $base)->count();

        // ── filters ──────────────────────────────────────────────────────
        if ($status = $request->input('status')) {
            if ($status === 'unread') {
                $base->whereNull('read_at');
            } elseif ($status === 'read') {
                $base->whereNotNull('read_at');
            }
        }

        if ($category = $request->input('category')) {
            $base->where('data->category', $category);
        }

        // ── global search (title / message live in the JSON payload) ───────
        $search = (string) $request->input('search.value', '');
        if ($search !== '') {
            $base->where('data', 'like', "%{$search}%");
        }

        $recordsFiltered = (clone $base)->count();

        // ── ordering (column index → DB column) ──────────────────────────
        $orderable = [
            null,             // 0  row #
            'data->category', // 1  Type
            null,             // 2  Notification
            'read_at',        // 3  Status
            'created_at',     // 4  Time (default)
            null,             // 5  actions
        ];

        $orderColIndex = (int) $request->input('order.0.column', 4);
        $orderDir      = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderColumn   = $orderable[$orderColIndex] ?? 'created_at';

        $base->orderBy($orderColumn ?: 'created_at', $orderColumn ? $orderDir : 'desc');

        // ── pagination ───────────────────────────────────────────────────
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $base->skip($start)->take($length);
        }

        $data = $base->get()->map(function (DatabaseNotification $n) {
            $payload  = $n->data;
            $category = $payload['category'] ?? 'general';
            $color    = $payload['color'] ?? 'primary';
            $icon     = $payload['icon'] ?? 'ki-notification-status';
            $title    = $payload['title'] ?? 'Notification';
            $message  = $payload['message'] ?? '';
            $isUnread = is_null($n->read_at);

            $open = '<a href="' . route('notifications.read', $n->id) . '" '
                . 'class="btn btn-sm btn-icon btn-light-primary w-30px h-30px me-1" title="Open">'
                . '<i class="ki-outline ki-eye fs-3"></i></a>';

            $delete = '<button type="button" '
            . 'class="btn btn-sm btn-icon btn-light-danger w-30px h-30px js-notification-delete" '
            . 'data-id="' . $n->id . '" title="Delete"><i class="ki-outline ki-trash fs-3"></i></button>';

            return [
                'type'         => '<div class="d-flex align-items-center">'
                . '<span class="symbol symbol-35px me-3"><span class="symbol-label bg-light-' . $color . '">'
                . '<i class="ki-outline ' . e($icon) . ' fs-4 text-' . $color . '"></i></span></span>'
                . '<span class="fw-semibold text-gray-700">'
                . e(self::CATEGORY_LABELS[$category] ?? ucfirst($category)) . '</span></div>',
                'notification' => '<div class="fw-bold text-gray-900">' . e($title) . '</div>'
                . '<div class="text-gray-600 fs-7">' . e($message) . '</div>',
                'status'       => $isUnread
                    ? '<span class="badge badge-light-warning">Unread</span>'
                    : '<span class="badge badge-light-success">Read</span>',
                'time'         => '<span class="text-gray-700" title="'
                . e(optional($n->created_at)->format('h:i A, d M Y')) . '">'
                . e(optional($n->created_at)->diffForHumans()) . '</span>',
                'actions'      => '<div class="d-flex justify-content-end">' . $open . $delete . '</div>',
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
     * Mark a single notification read and forward to its deep link.
     * GET, because it is the "open this notification" click from the header
     * and the listing — idempotent and non-destructive.
     */
    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return $url
            ? redirect($url)
            : redirect()->route('notifications.index');
    }

    /**
     * Mark every unread notification read. Dual-mode (JSON for AJAX, redirect
     * back otherwise).
     */
    public function readAll(Request $request): JsonResponse | RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'All notifications marked as read.']);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete one of the user's notifications. Dual-mode.
     */
    public function destroy(Request $request, string $id): JsonResponse | RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Notification deleted.']);
        }

        return back()->with('success', 'Notification deleted.');
    }
}
