<?php
namespace App\Http\Controllers;

use App\Models\Auth\User;
use App\Models\Cpf\CpfAdvance;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeSalaryHistory;
use App\Models\Employee\PayScaleStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    /** Spatie's activity table (default). Change here if you renamed it. */
    private string $table = 'activity_log';

    /** In-request cache for foreign-key → label lookups. */
    private array $refCache = [];

    /** Whether the current user may follow a causer link to a user. */
    private bool $canViewUsers = false;

    /**
     * Audit log index (server-side DataTable shell).
     */
    public function index(): View
    {
        $events = Activity::query()
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $logNames = Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        $subjectTypes = Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        return view('audit-logs.index', compact('events', 'logNames', 'subjectTypes'));
    }

    /**
     * Server-side DataTables endpoint for the audit log.
     */
    public function data(Request $request): JsonResponse
    {
        $this->canViewUsers = (bool) $request->user()?->can('user.view');

        $userMorph = (new User)->getMorphClass();

        $base = Activity::query()
            ->leftJoin('users', function ($join) use ($userMorph) {
                $join->on($this->table . '.causer_id', '=', 'users.id')
                    ->where($this->table . '.causer_type', '=', $userMorph);
            })
            ->select([
                $this->table . '.*',
                'users.name as causer_name',
            ]);

        $recordsTotal = (clone $base)->count();

        // ── filters ──────────────────────────────────────────────────────
        if ($event = $request->input('event')) {
            $base->where($this->table . '.event', $event);
        }

        if ($logName = $request->input('log_name')) {
            $base->where($this->table . '.log_name', $logName);
        }

        if ($subjectType = $request->input('subject_type')) {
            $base->where($this->table . '.subject_type', $subjectType);
        }

        // ── global search ────────────────────────────────────────────────
        $search = (string) $request->input('search.value', '');
        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where($this->table . '.description', 'like', "%{$search}%")
                    ->orWhere($this->table . '.event', 'like', "%{$search}%")
                    ->orWhere($this->table . '.log_name', 'like', "%{$search}%")
                    ->orWhere($this->table . '.subject_type', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        // ── ordering (column index → DB column) ──────────────────────────
        $orderable = [
            null,                           // 0  row #
            $this->table . '.description',  // 1  Description
            $this->table . '.event',        // 2  Event
            $this->table . '.log_name',     // 3  Log Name
            $this->table . '.subject_type', // 4  Subject
            null,                           // 5  Changes (not orderable)
            'users.name',                   // 6  Causer
            $this->table . '.created_at',   // 7  When
            null,                           // 8  Actions
        ];

        $orderColIndex = (int) $request->input('order.0.column', 7);
        $orderDir      = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderColumn   = $orderable[$orderColIndex] ?? ($this->table . '.created_at');

        $base->orderBy($orderColumn ?: ($this->table . '.created_at'), $orderColumn ? $orderDir : 'desc');

        // ── pagination ───────────────────────────────────────────────────
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $base->skip($start)->take($length);
        }

        $rows = $base->get();

        // Resolve subject display names for the whole page in one batch per type.
        $subjectNames = $this->resolveSubjectNames($rows);

        $data = $rows->map(function (Activity $activity) use ($subjectNames) {
            return [
                'description' => e(Str::ucfirst((string) ($activity->description ?: '—'))),
                'event'       => $this->renderEvent($activity->event),
                'log_name'    => $activity->log_name
                    ? '<span class="badge badge-light-primary">' . e(Str::headline($activity->log_name)) . '</span>'
                    : '<span class="text-muted">—</span>',
                'subject'     => $this->renderSubject($activity, $subjectNames),
                'changes'     => $this->renderChanges($activity),
                'causer'      => $this->renderCauser($activity),
                'when'        => optional($activity->created_at)->format('h:i A, d M Y') ?? '—',
                'actions'     => $this->renderActions($activity),
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
     * Single activity detail page.
     */
    public function show(Activity $log): View
    {
        $log->load('causer');

        $this->canViewUsers = (bool) request()->user()?->can('user.view');

        $subjectName = null;
        if ($log->subject_type && $log->subject_id) {
            $names       = $this->fetchSubjectNames($log->subject_type, [$log->subject_id]);
            $subjectName = $names[$log->subject_id] ?? null;
        }

        $isEmployeeSubject = $log->subject_type === (new Employee)->getMorphClass() && (bool) $log->subject_id;

        $changesHtml = $this->renderChanges($log);

        return view('audit-logs.show', compact('log', 'changesHtml', 'subjectName', 'isEmployeeSubject'));
    }

    /*
    |--------------------------------------------------------------------------
    | Subject name resolution (batched, per request)
    |--------------------------------------------------------------------------
    */

    /**
     * Build a [subject_type => [id => display name]] lookup for the page rows,
     * fetching each model type in a single query.
     */
    private function resolveSubjectNames(Collection $activities): array
    {
        $idsByType = [];
        foreach ($activities as $activity) {
            if ($activity->subject_type && $activity->subject_id) {
                $idsByType[$activity->subject_type][] = $activity->subject_id;
            }
        }

        $names = [];
        foreach ($idsByType as $type => $ids) {
            $names[$type] = $this->fetchSubjectNames($type, array_values(array_unique($ids)));
        }

        return $names;
    }

    /**
     * Resolve display names for a set of subject ids of one morph type.
     * Returns [id => name]; unknown types yield an empty map.
     */
    private function fetchSubjectNames(string $type, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return match ($type) {
            (new Employee)->getMorphClass()              => Employee::withTrashed()
                ->whereIn('id', $ids)->pluck('name', 'id')->all(),

            (new User)->getMorphClass()                  => User::withTrashed()
                ->whereIn('id', $ids)->pluck('name', 'id')->all(),

            (new CpfAdvance)->getMorphClass()            => CpfAdvance::withTrashed()
                ->whereIn('id', $ids)->pluck('advance_no', 'id')->all(),

            // Salary history isn't a person — surface the employee it belongs to.
            (new EmployeeSalaryHistory)->getMorphClass() => EmployeeSalaryHistory::with('employee:id,name')
                ->whereIn('id', $ids)->get()
                ->mapWithKeys(fn($h) => [$h->id => $h->employee?->name])->all(),

            default                                      => [],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Row renderers
    |--------------------------------------------------------------------------
    */

    private function renderEvent(?string $event): string
    {
        $class = match ($event) {
            'created'  => 'badge-light-success',
            'updated'  => 'badge-light-warning',
            'deleted'  => 'badge-light-danger',
            'restored' => 'badge-light-info',
            default    => 'badge-light',
        };

        return '<span class="badge ' . $class . '">' . e(Str::headline((string) $event)) . '</span>';
    }

    /**
     * Subject cell: the resolved record name on the primary line, with the
     * model type + id on a muted second line. Employees link to their profile.
     */
    private function renderSubject(Activity $activity, array $subjectNames): string
    {
        if (! $activity->subject_type) {
            return '<span class="text-muted">—</span>';
        }

        $typeLabel = e(class_basename($activity->subject_type));
        $idBadge   = $activity->subject_id
            ? '<span class="text-muted fs-8">' . $typeLabel . ' #' . $activity->subject_id . '</span>'
            : '';

        $name = $subjectNames[$activity->subject_type][$activity->subject_id] ?? null;

        if ($name) {
            // Primary = resolved name, secondary = model type + id.
            $primaryText = e($name);
            $secondary   = $idBadge ? '<div class="mt-1">' . $idBadge . '</div>' : '';
        } else {
            // No name available — fall back to the model type as the primary.
            $primaryText = '<span class="text-gray-700">' . $typeLabel . '</span>'
                . ($activity->subject_id ? ' <span class="text-muted fs-8">#' . $activity->subject_id . '</span>' : '');
            $secondary = '';
        }

        if ($activity->subject_type === (new Employee)->getMorphClass() && $activity->subject_id) {
            $url     = route('employees.show', $activity->subject_id);
            $primary = '<a href="' . $url . '" target="_blank" '
                . 'class="text-gray-800 text-hover-primary fw-semibold">' . $primaryText . '</a>';
        } else {
            $primary = '<span class="fw-semibold text-gray-800">' . $primaryText . '</span>';
        }

        return $primary . $secondary;
    }

    private function renderCauser(Activity $activity): string
    {
        if (! $activity->causer_name) {
            return '<span class="badge badge-light-dark">System</span>';
        }

        $name = e($activity->causer_name);

        if ($this->canViewUsers && $activity->causer_id) {
            $url = route('users.show', $activity->causer_id);

            return '<a href="' . $url . '" target="_blank" '
                . 'class="text-gray-800 text-hover-primary">' . $name . '</a>';
        }

        return $name;
    }

    private function renderActions(Activity $activity): string
    {
        return '<a href="' . route('audit-logs.show', $activity->id) . '" '
            . 'class="btn btn-icon btn-light btn-active-light-primary w-30px h-30px" title="View details">'
            . '<i class="ki-outline ki-eye fs-3"></i></a>';
    }

    /*
    |--------------------------------------------------------------------------
    | Change-diff rendering (shared by the table and the detail page)
    |--------------------------------------------------------------------------
    */

    private function renderChanges(Activity $activity): string
    {
        $attrs = data_get($activity->properties, 'attributes', []);
        $old   = data_get($activity->properties, 'old', []);

        if (empty($attrs)) {
            return '<span class="text-muted">—</span>';
        }

        $parts = [];
        foreach ($attrs as $key => $val) {
            $label = e($this->activityLabel($key));
            $new   = e($this->formatActivityValue($key, $val));

            if (array_key_exists($key, $old)) {
                $oldV    = e($this->formatActivityValue($key, $old[$key]));
                $parts[] = "<div class='fs-8 mb-1'><span class='fw-semibold text-gray-700'>{$label}:</span> "
                    . "<span class='text-danger'>{$oldV}</span> "
                    . "<i class='ki-outline ki-arrow-right fs-8 mx-1'></i>"
                    . "<span class='text-success'>{$new}</span></div>";
            } else {
                $parts[] = "<div class='fs-8 mb-1'><span class='fw-semibold text-gray-700'>{$label}:</span> "
                    . "<span class='text-success'>{$new}</span></div>";
            }
        }

        return "<div class='d-flex flex-column'>" . implode('', $parts) . '</div>';
    }

    private function activityLabel(string $key): string
    {
        return match ($key) {
            'employee_id'       => 'Employee',
            'pay_scale_step_id' => 'Basic Salary',
            'cpf_advance_id'    => 'Advance',
            'created_by'        => 'Created By',
            'approved_by'       => 'Approved By',
            'submitted_by'      => 'Submitted By',
            'updated_by'        => 'Updated By',
            default             => Str::headline($key),
        };
    }

    private function formatActivityValue(string $key, $value): string
    {
        if ($value === null || $value === '') {
            return '∅';
        }

        if ($key === 'is_active') {
            return $value ? 'Active' : 'Inactive';
        }

        if ($key === 'status') {
            return Str::headline((string) $value);
        }

        $resolved = $this->resolveReference($key, $value);
        if ($resolved !== null) {
            return $resolved;
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2})?/', $value)) {
            try {
                return Carbon::parse($value)
                    ->timezone(config('app.timezone'))
                    ->format('d-M-Y, h:i:s A');
            } catch (\Throwable $e) {
                // fall through to raw value
            }
        }

        return (string) $value;
    }

    private function resolveReference(string $key, $value): ?string
    {
        $cacheKey = $key . ':' . $value;
        if (array_key_exists($cacheKey, $this->refCache)) {
            return $this->refCache[$cacheKey];
        }

        $label = match ($key) {
            'employee_id'       => optional(Employee::withTrashed()->find($value))->name,
            'pay_scale_step_id' => $this->payScaleStepLabel($value),
            'cpf_advance_id'    => optional(CpfAdvance::withTrashed()->find($value))->advance_no,
            'created_by',
            'approved_by',
            'submitted_by',
            'updated_by'        => optional(User::find($value))->name,
            default             => null,
        };

        return $this->refCache[$cacheKey] = $label;
    }

    private function payScaleStepLabel($value): ?string
    {
        $step = PayScaleStep::find($value);

        if (! $step) {
            return null;
        }

        return '৳' . number_format($step->basic_salary)
        . ' (Grade ' . $step->grade . ', Step ' . $step->step . ')';
    }
}
