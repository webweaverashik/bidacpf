<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Auth\User;
use App\Traits\FormatsActivityChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

class UserController extends Controller
{
    use FormatsActivityChanges;

    /**
     * Role badge classes keyed by exact role name.
     */
    private array $roleBadges = [
        'Admin'       => 'badge badge-light-danger rounded-pill fs-7 fw-bold',
        'CPF Officer' => 'badge badge-light-success rounded-pill fs-7 fw-bold',
        'Auditor'     => 'badge badge-light-info rounded-pill fs-7 fw-bold',
    ];

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $roles = ['Admin', 'CPF Officer', 'Auditor'];

        return view('users.index', compact('roles'));
    }

    /*
    |--------------------------------------------------------------------------
    | Server-side DataTable feed
    |--------------------------------------------------------------------------
    */
    public function data(Request $request)
    {
        $query = User::with(['latestLoginActivity', 'roles:id,name']);

        // Deleted-only toggle.
        if ($request->input('deleted_only') === 'true') {
            $query->onlyTrashed();
        }

        $totalRecords = (clone $query)->count();

        // Global search.
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        // Role filter.
        if ($request->filled('role')) {
            $role = $request->input('role');
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        $filteredRecords = (clone $query)->count();

        // Sorting (only simple columns are sortable).
        $columns        = [0 => null, 1 => 'name', 2 => 'email', 3 => 'mobile_number', 4 => null, 5 => null, 6 => 'is_active', 7 => null];
        $orderColumnIdx = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');
        $orderColumn    = $columns[$orderColumnIdx] ?? null;

        if ($orderColumn) {
            $query->orderBy($orderColumn, $orderDirection);
        } else {
            $query->latest('id');
        }

        // Pagination.
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $users = $query->skip($start)->take($length)->get();

        $isDeleted = $request->input('deleted_only') === 'true';
        $data      = [];
        $counter   = $start + 1;

        foreach ($users as $user) {
            $role       = $user->roles->first()?->name;
            $badgeClass = $this->roleBadges[$role] ?? 'badge badge-light-secondary fw-bold';
            $photoUrl   = $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png');

            // --- User info column ---
            if ($user->trashed()) {
                $userInfoHtml = '
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                            <div class="symbol-label">
                                <img src="' . $photoUrl . '" alt="' . e($user->name) . '" class="w-100" />
                            </div>
                        </div>
                        <div class="d-flex flex-column text-start">
                            <span class="text-gray-600 mb-1">' . e($user->name) . '</span>
                            <span class="fw-bold fs-base text-gray-500">' . e($user->email) . '</span>
                        </div>
                    </div>';
            } else {
                $showUrl      = route('users.show', $user->id);
                $userInfoHtml = '
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                            <div class="symbol-label">
                                <img src="' . $photoUrl . '" alt="' . e($user->name) . '" class="w-100" />
                            </div>
                        </div>
                        <div class="d-flex flex-column text-start">
                            <a href="' . $showUrl . '" class="text-gray-800 text-hover-primary mb-1">' . e($user->name) . '</a>
                            <span class="fw-bold fs-base text-muted">' . e($user->designation ?: '—') . '</span>
                        </div>
                    </div>';
            }

            $roleHtml = '<div class="' . $badgeClass . '">' . e($role ?? '—') . '</div>';

            // --- Email column ---
            $emailHtml = '<a href="mailto:' . e($user->email) . '" class="text-gray-700 text-hover-primary">'
            . e($user->email) . '</a>';

            // --- Last login column ---
            $lastLoginHtml = '<span class="text-muted">Never</span>';
            if ($user->latestLoginActivity) {
                $lastLoginHtml = '<span class="text-gray-800">' . $user->latestLoginActivity->created_at->format('d M, Y') . '</span>'
                . '<span class="text-gray-500 d-block fs-7">' . $user->latestLoginActivity->created_at->format('h:i A') . '</span>';
            }

            // --- Active toggle column ---
            $activeHtml = '';
            if ($isDeleted) {
                $activeHtml = '<span class="badge badge-light-danger">Deleted</span>';
            } elseif ($user->id !== auth()->id()) {
                $checked    = $user->is_active ? 'checked' : '';
                $activeHtml = '
                    <div class="form-check form-switch form-check-solid form-check-success d-flex justify-content-center">
                        <input class="form-check-input toggle-active" type="checkbox" value="' . $user->id . '" ' . $checked . '>
                    </div>';
            } else {
                $activeHtml = '<span class="badge badge-light-primary">You</span>';
            }

            // --- Actions column ---
            $actionsHtml = '<div class="d-flex justify-content-end flex-shrink-0">';
            if ($isDeleted) {
                $actionsHtml .= '
                    <button type="button" title="Recover User" data-bs-toggle="tooltip"
                        class="btn btn-icon btn-light-success w-30px h-30px recover-user-btn"
                        data-user-id="' . $user->id . '" data-user-name="' . e($user->name) . '">
                        <i class="ki-outline ki-arrow-circle-left fs-2"></i>
                    </button>';
            } else {
                $actionsHtml .= '
                    <a href="' . route('users.show', $user->id) . '" title="View Activity" data-bs-toggle="tooltip"
                        class="btn btn-icon btn-light btn-active-light-primary w-30px h-30px me-2">
                        <i class="ki-outline ki-eye fs-2"></i>
                    </a>
                    <a href="#" title="Edit User" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_user"
                        data-user-id="' . $user->id . '"
                        class="btn btn-icon btn-light btn-active-light-primary w-30px h-30px me-2 edit-user-btn">
                        <i class="ki-outline ki-pencil fs-2"></i>
                    </a>
                    <a href="#" title="Reset Password" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_password"
                        data-user-id="' . $user->id . '" data-user-name="' . e($user->name) . '"
                        class="btn btn-icon btn-light btn-active-light-warning w-30px h-30px me-2 change-password-btn">
                        <i class="ki-outline ki-key fs-2"></i>
                    </a>';

                if ($user->id !== auth()->id()) {
                    $actionsHtml .= '
                        <a href="#" title="Delete User" data-bs-toggle="tooltip"
                            class="btn btn-icon btn-light btn-active-light-danger w-30px h-30px delete-user"
                            data-user-id="' . $user->id . '"
                            data-user-name="' . e($user->name) . '"
                            data-user-email="' . e($user->email) . '"
                            data-user-photo="' . $photoUrl . '">
                            <i class="ki-outline ki-trash fs-2"></i>
                        </a>';
                }
            }
            $actionsHtml .= '</div>';

            $data[] = [
                'counter'    => $counter++,
                'user_info'  => $userInfoHtml,
                'email'      => $emailHtml,
                'mobile'     => e($user->mobile_number ?: '—'),
                'role'       => $roleHtml,
                'last_login' => $lastLoginHtml,
                'active'     => $activeHtml,
                'actions'    => $actionsHtml,
            ];
        }

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */
    public function store(StoreUserRequest $request)
    {
        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $this->storePhoto($request->file('photo'));
        }

        $user = User::create([
            'name'          => $request->name,
            'designation'   => $request->designation,
            'email'         => $request->email,
            'mobile_number' => $request->mobile_number,
            'password'      => Hash::make($request->password),
            'photo_url'     => $photoUrl,
            'is_active'     => true,
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show — full activity page
    |--------------------------------------------------------------------------
    */
    public function show(User $user)
    {
        $user->load('roles:id,name');

        $stats = [
            'total_logins' => $user->loginActivities()->count(),
            'last_login'   => $user->latestLoginActivity?->created_at,
            'member_since' => $user->created_at,
        ];

        return view('users.show', compact('user', 'stats'));
    }

    /*
    |--------------------------------------------------------------------------
    | JSON — feed the edit modal
    |--------------------------------------------------------------------------
    */
    public function json(User $user)
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $user->id,
                'name'          => $user->name,
                'designation'   => $user->designation,
                'email'         => $user->email,
                'mobile_number' => $user->mobile_number,
                'role'          => $user->getRoleNames()->first(),
                'photo_url'     => $user->photo_url ? asset($user->photo_url) : null,
                'is_active'     => $user->is_active,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */
    public function update(UpdateUserRequest $request, User $user)
    {
        $photoUrl = $user->photo_url;

        // Remove photo if requested.
        if ($request->input('remove_photo') === '1') {
            $this->deletePhoto($user->photo_url);
            $photoUrl = null;
        }

        // New photo upload.
        if ($request->hasFile('photo')) {
            $this->deletePhoto($user->photo_url);
            $photoUrl = $this->storePhoto($request->file('photo'), $user->id);
        }

        $payload = [
            'name'          => $request->name,
            'designation'   => $request->designation,
            'email'         => $request->email,
            'mobile_number' => $request->mobile_number,
            'photo_url'     => $photoUrl,
        ];

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->password);
        }

        $user->update($payload);
        $user->syncRoles($request->role);

        return response()->json([
            'success'   => true,
            'message'   => 'User updated successfully.',
            'photo_url' => $photoUrl ? asset($photoUrl) : asset('img/male-placeholder.png'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy (soft delete)
    |--------------------------------------------------------------------------
    */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User has been deleted. The account can be recovered by an administrator.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Recover
    |--------------------------------------------------------------------------
    */
    public function recover(Request $request)
    {
        $request->validate(['user_id' => 'required|integer']);

        $user = User::onlyTrashed()->find($request->user_id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found or already restored.',
            ], 404);
        }

        $user->restore();

        return response()->json([
            'success' => true,
            'message' => 'User "' . $user->name . '" has been recovered.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle active
    |--------------------------------------------------------------------------
    */
    public function toggleActive(Request $request)
    {
        $request->validate([
            'user_id'   => 'required|integer|exists:users,id',
            'is_active' => 'required|boolean',
        ]);

        $user = User::find($request->user_id);

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'You cannot change your own status.']);
        }

        $user->is_active = (bool) $request->is_active;
        $user->save();

        return response()->json(['success' => true, 'message' => 'User status updated.']);
    }

    /*
    |--------------------------------------------------------------------------
    | Reset password (admin action on another user)
    |--------------------------------------------------------------------------
    */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'new_password' => [
                'required', 'string', 'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/',
            ],
        ], [
            'new_password.regex' => 'Password must contain uppercase, lowercase, number, and special character.',
        ]);

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['success' => true, 'message' => 'Password updated successfully.']);
    }

    /*
    |--------------------------------------------------------------------------
    | Activity log feed (Spatie) — server-side DataTable
    |--------------------------------------------------------------------------
    */
    public function activities(Request $request, User $user)
    {
        return $this->activityFeed($request, $user->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Login activity feed — server-side DataTable
    |--------------------------------------------------------------------------
    */
    public function loginActivities(Request $request, User $user)
    {
        return $this->loginFeed($request, $user);
    }

    /*
    |--------------------------------------------------------------------------
    | Shared feed builders (re-used by ProfileController)
    |--------------------------------------------------------------------------
    */
    public function activityFeed(Request $request, int $causerId)
    {
        $query = Activity::query()
            ->where('causer_type', User::class)
            ->where('causer_id', $causerId);

        $totalRecords = (clone $query)->count();

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', Carbon::createFromFormat('d-m-Y', $request->start_date)->startOfDay());
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', Carbon::createFromFormat('d-m-Y', $request->end_date)->endOfDay());
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        $query->latest('id');

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $activities = $query->skip($start)->take($length)->get();

        $eventBadges = [
            'created'  => 'badge badge-light-success',
            'updated'  => 'badge badge-light-primary',
            'deleted'  => 'badge badge-light-danger',
            'restored' => 'badge badge-light-info',
        ];

        $data    = [];
        $counter = $start + 1;

        foreach ($activities as $activity) {
            $event      = $activity->event ?? '—';
            $eventClass = $eventBadges[$event] ?? 'badge badge-light-secondary';
            $module     = $activity->log_name ? \Illuminate\Support\Str::headline($activity->log_name) : '—';

            $data[] = [
                'counter'     => $counter++,
                'module'      => '<span class="badge badge-light-dark">' . e($module) . '</span>',
                'event'       => '<span class="' . $eventClass . ' text-capitalize">' . e($event) . '</span>',
                'description' => '<span class="text-gray-800">' . e($activity->description) . '</span>',
                'changes'     => $this->renderActivityChanges($activity),
                'time'        => '<span class="text-gray-800">' . $activity->created_at->format('d M, Y') . '</span>'
                . '<span class="text-gray-500 d-block fs-7">' . $activity->created_at->format('h:i A') . '</span>',
            ];
        }

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    public function loginFeed(Request $request, User $user)
    {
        $query = $user->loginActivities();

        $totalRecords = (clone $query)->count();

        if ($request->filled('device')) {
            $query->where('device', $request->input('device'));
        }

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', Carbon::createFromFormat('d-m-Y', $request->start_date)->startOfDay());
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', Carbon::createFromFormat('d-m-Y', $request->end_date)->endOfDay());
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%")
                    ->orWhere('device', 'like', "%{$search}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        $query->latest('id');

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $activities = $query->skip($start)->take($length)->get();

        $deviceBadges = [
            'Mobile'  => 'badge badge-light-warning',
            'Desktop' => 'badge badge-light-info',
            'Tablet'  => 'badge badge-light-primary',
        ];

        $data    = [];
        $counter = $start + 1;

        foreach ($activities as $activity) {
            $deviceClass = $deviceBadges[$activity->device] ?? 'badge badge-light-secondary';

            $data[] = [
                'counter'    => $counter++,
                'ip_address' => '<span class="text-gray-800 fw-semibold">' . e($activity->ip_address ?: '—') . '</span>',
                'user_agent' => '<span class="text-gray-600 text-wrap d-inline-block" style="max-width:420px">' . e($activity->user_agent ?: '—') . '</span>',
                'device'     => '<span class="' . $deviceClass . '">' . e($activity->device ?: 'Unknown') . '</span>',
                'time'       => '<span class="text-gray-800">' . $activity->created_at->diffForHumans() . '</span>'
                . '<span class="text-gray-500 d-block fs-7">' . $activity->created_at->format('d M, Y h:i A') . '</span>',
            ];
        }

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Photo helpers
    |--------------------------------------------------------------------------
    */
    private function storePhoto($photo, ?int $userId = null): string
    {
        $prefix   = $userId ? "user_{$userId}_" : 'user_';
        $filename = $prefix . time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
        $photo->move(public_path('uploads/users'), $filename);

        return 'uploads/users/' . $filename;
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}
