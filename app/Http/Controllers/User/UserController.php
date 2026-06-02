<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('users.manage')) {
            return redirect()->back()->with('error', 'No permission to manage users.');
        }

        $branches = Branch::all();

        return view('settings.users.index', compact('branches'));
    }

    /**
     * Get users data for DataTable via AJAX.
     */
    public function getUsers(Request $request)
    {
        $query = User::with(['branch:id,branch_name', 'latestLoginActivity', 'roles:id,name']);

        // Handle deleted only filter
        if ($request->deleted_only === 'true') {
            $query->onlyTrashed();
        }

        // Get total count before filtering
        $totalRecords = $query->count();

        // Search filter
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        // Branch filter
        if ($request->filled('branch') && $request->branch !== '') {
            $query->whereHas('branch', function ($q) use ($request) {
                $q->where('branch_name', $request->branch);
            });
        }

        // Role filter
        if ($request->filled('role') && $request->role !== '') {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', strtolower($request->role));
            });
        }

        $filteredRecords = $query->count();

        // Sorting
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');

        $columns = ['id', 'name', 'mobile_number', 'branch_id', 'roles', 'created_at', 'is_active', 'id'];

        if (isset($columns[$orderColumnIndex])) {
            $orderColumn = $columns[$orderColumnIndex];
            if (!in_array($orderColumn, ['roles'])) {
                $query->orderBy($orderColumn, $orderDirection);
            }
        } else {
            $query->latest('id');
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        $users = $query->skip($start)->take($length)->get();

        // Format data for DataTable
        $data = [];
        $counter = $start + 1;

        foreach ($users as $user) {
            $role = $user->roles->first()?->name;
            $badgeClasses = [
                'admin' => 'badge badge-light-danger rounded-pill fs-7 fw-bold',
                'manager' => 'badge badge-light-success rounded-pill fs-7 fw-bold',
                'accountant' => 'badge badge-light-info rounded-pill fs-7 fw-bold',
            ];
            $badgeClass = $badgeClasses[$role] ?? 'badge badge-light-secondary fw-bold';

            $photoUrl = $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png');
            $isDeleted = $request->deleted_only === 'true';

            // User info column - link to settlements only for non-deleted users
            if ($user->trashed()) {
                // Deleted user - no link
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
                // Active user - with settlements link
                $settlementsUrl = route('settlements.show', $user->id);
                $userInfoHtml = '
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                            <div class="symbol-label">
                                <img src="' . $photoUrl . '" alt="' . e($user->name) . '" class="w-100" />
                            </div>
                        </div>
                        <div class="d-flex flex-column text-start">
                            <a href="' . $settlementsUrl . '" class="text-gray-800 text-hover-primary mb-1">' . e($user->name) . '</a>
                            <span class="fw-bold fs-base">' . e($user->email) . '</span>
                        </div>
                    </div>';
            }

            // Branch column
            $branchHtml = $user->branch ? e($user->branch->branch_name) : '<span class="text-muted">-</span>';

            // Role column
            $roleHtml = '<div class="' . $badgeClass . '">' . ucfirst($role ?? '-') . '</div>';

            // Last login column
            $lastLoginHtml = '-';
            if ($user->latestLoginActivity) {
                $lastLoginHtml = $user->latestLoginActivity->created_at->format('d-M-Y') . '<br>' .
                    $user->latestLoginActivity->created_at->format('h:i:s A');
            }

            // Active toggle column
            $activeHtml = '';
            if (!$isDeleted && $user->id != auth()->id()) {
                $checked = $user->is_active ? 'checked' : '';
                $activeHtml = '
                    <div class="form-check form-switch form-check-solid form-check-success d-flex justify-content-center">
                        <input class="form-check-input toggle-active" type="checkbox" value="' . $user->id . '" ' . $checked . '>
                    </div>';
            } elseif ($isDeleted) {
                $activeHtml = '<span class="badge badge-light-danger">Deleted</span>';
            }

            // Actions column
            $actionsHtml = '';
            if ($isDeleted) {
                // Show recover button for deleted users
                $actionsHtml = '
                    <button type="button" title="Recover User" data-bs-toggle="tooltip"
                        class="btn btn-icon btn-light-success w-30px h-30px recover-user-btn"
                        data-user-id="' . $user->id . '"
                        data-user-name="' . e($user->name) . '">
                        <i class="ki-outline ki-arrow-circle-left fs-2"></i>
                    </button>';
            } else {
                if ($user->id == auth()->id()) {
                    $actionsHtml = '
                        <a href="' . route('users.profile') . '" title="My Profile" data-bs-toggle="tooltip"
                            class="btn btn-icon text-hover-success w-30px h-30px me-3">
                            <i class="ki-outline ki-eye fs-2"></i>
                        </a>';
                } else {
                    $actionsHtml = '
                        <a href="#" title="Edit User" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_user"
                            data-user-id="' . $user->id . '" class="btn btn-icon text-hover-primary w-30px h-30px">
                            <i class="ki-outline ki-pencil fs-2"></i>
                        </a>
                        <a href="#" title="Reset Password" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_password"
                            data-user-id="' . $user->id . '" data-user-name="' . e($user->name) . '"
                            class="btn btn-icon text-hover-primary w-30px h-30px change-password-btn">
                            <i class="ki-outline ki-key fs-2"></i>
                        </a>
                        <a href="#" title="Delete User" data-bs-toggle="tooltip"
                            class="btn btn-icon text-hover-danger w-30px h-30px delete-user"
                            data-user-id="' . $user->id . '"
                            data-user-name="' . e($user->name) . '"
                            data-user-email="' . e($user->email) . '"
                            data-user-photo="' . $photoUrl . '">
                            <i class="ki-outline ki-trash fs-2"></i>
                        </a>';
                }
            }

            $data[] = [
                'counter' => $counter++,
                'user_info' => $userInfoHtml,
                'mobile' => e($user->mobile_number),
                'branch' => $branchHtml,
                'role' => $roleHtml,
                'last_login' => $lastLoginHtml,
                'active' => $activeHtml,
                'actions' => $actionsHtml,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->back();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $commonRules = [
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|string|email|max:255|unique:users,email',
            'user_mobile' => 'required|string|size:11',
            'user_role' => 'required|string|in:admin,manager,accountant',
            'user_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:100',
        ];

        // Only validate branch if role is NOT admin
        if ($request->user_role !== 'admin') {
            $commonRules['user_branch'] = 'required|integer|exists:branches,id';
        }

        $request->validate($commonRules);

        $branch_id = $request->user_role === 'admin' ? 0 : $request->user_branch;

        // Handle photo upload
        $photoUrl = null;
        if ($request->hasFile('user_photo')) {
            $photo = $request->file('user_photo');
            $filename = 'user_' . time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('uploads/users'), $filename);
            $photoUrl = 'uploads/users/' . $filename;
        }

        $user = User::create([
            'name' => $request->user_name,
            'email' => $request->user_email,
            'mobile_number' => $request->user_mobile,
            'password' => Hash::make('ucms@123'),
            'branch_id' => $branch_id,
            'photo_url' => $photoUrl,
        ]);

        $user->assignRole($request->user_role);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile_number' => $user->mobile_number,
                'branch_id' => $user->branch_id,
                'role' => $user->getRoleNames()->first(),
                'photo_url' => $user->photo_url ? asset($user->photo_url) : null,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $commonRules = [
            'user_name_edit' => 'required|string|max:255',
            'user_email_edit' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'user_mobile_edit' => 'required|string|size:11',
            'user_role_edit' => 'required|string|in:admin,manager,accountant',
            'user_photo_edit' => 'nullable|image|mimes:jpg,jpeg,png|max:100',
        ];

        // Only validate branch if role is NOT admin
        if ($request->user_role_edit !== 'admin') {
            $commonRules['user_branch_edit'] = 'required|integer|exists:branches,id';
        }

        $request->validate($commonRules);

        $branch_id = $request->user_role_edit === 'admin' ? 0 : $request->user_branch_edit;

        // Handle photo upload
        $photoUrl = $user->photo_url;

        // Check if photo should be removed
        if ($request->has('remove_photo') && $request->remove_photo === '1') {
            // Delete old photo file
            if ($user->photo_url && file_exists(public_path($user->photo_url))) {
                unlink(public_path($user->photo_url));
            }
            $photoUrl = null;
        }

        // Handle new photo upload
        if ($request->hasFile('user_photo_edit')) {
            // Delete old photo file
            if ($user->photo_url && file_exists(public_path($user->photo_url))) {
                unlink(public_path($user->photo_url));
            }

            $photo = $request->file('user_photo_edit');
            $filename = 'user_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('uploads/users'), $filename);
            $photoUrl = 'uploads/users/' . $filename;
        }

        // Update the user record
        $user->update([
            'name' => $request->user_name_edit,
            'email' => $request->user_email_edit,
            'mobile_number' => $request->user_mobile_edit,
            'branch_id' => $branch_id,
            'photo_url' => $photoUrl,
        ]);

        $user->syncRoles($request->user_role_edit);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'photo_url' => $photoUrl ? asset($photoUrl) : asset('img/male-placeholder.png'),
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     * Note: Profile photo is preserved for data recovery purposes.
     */
    public function destroy(User $user)
    {
        // Soft delete the user - photo is preserved for recovery
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User has been deleted successfully. The account can be recovered by an administrator.',
        ]);
    }

    /**
     * Recover a soft-deleted user.
     */
    public function recover(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $user = User::onlyTrashed()->find($request->user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found or already restored.',
            ], 404);
        }

        $user->restore();

        return response()->json([
            'success' => true,
            'message' => 'User "' . $user->name . '" has been successfully recovered.',
        ]);
    }

    /**
     * Toggle active and inactive users
     */
    public function toggleActive(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Error. Please, contact support.']);
        }

        $user->is_active = $request->is_active;
        $user->save();

        return response()->json(['success' => true, 'message' => 'User activation status updated.']);
    }

    /**
     * Reset user password
     */
    public function userPasswordReset(Request $request, User $user)
    {
        $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found.']);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['success' => true, 'message' => 'Password updated successfully.']);
    }
}
