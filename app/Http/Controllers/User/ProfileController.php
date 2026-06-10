<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use App\Traits\FormatsActivityChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

class ProfileController extends Controller
{
    use FormatsActivityChanges;

    /*
    |--------------------------------------------------------------------------
    | Profile page
    |--------------------------------------------------------------------------
    */
    public function profile()
    {
        $user = auth()->user()->load('roles:id,name');

        $stats = [
            'total_logins' => $user->loginActivities()->count(),
            'last_login'   => $user->latestLoginActivity?->created_at,
            'member_since' => $user->created_at,
        ];

        return view('users.profile', compact('user', 'stats'));
    }

    /*
    |--------------------------------------------------------------------------
    | Activity log feed (Spatie) for the authenticated user
    |--------------------------------------------------------------------------
    */
    public function activities(Request $request)
    {
        $userId = auth()->id();

        $query = Activity::query()
            ->where('causer_type', User::class)
            ->where('causer_id', $userId);

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

        $start      = (int) $request->input('start', 0);
        $length     = (int) $request->input('length', 10);
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
            $module     = $activity->log_name ? Str::headline($activity->log_name) : '—';

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

    /*
    |--------------------------------------------------------------------------
    | Login activity feed for the authenticated user
    |--------------------------------------------------------------------------
    */
    public function loginActivities(Request $request)
    {
        $user  = auth()->user();
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

        $start      = (int) $request->input('start', 0);
        $length     = (int) $request->input('length', 10);
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
    | Update profile (photo for everyone; fields for admin)
    |--------------------------------------------------------------------------
    */
    public function updateProfile(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->isAdmin();

        // ---- Photo handling (all users) ----
        if ($request->hasFile('photo') || $request->input('remove_photo') === '1') {
            $request->validate(['photo' => 'nullable|image|mimes:jpg,jpeg,png|max:100']);

            if ($request->input('remove_photo') === '1') {
                $this->deletePhoto($user->photo_url);
                $user->photo_url = null;
                $user->save();

                if (! $isAdmin) {
                    return response()->json([
                        'success'   => true,
                        'message'   => 'Profile photo removed successfully.',
                        'photo_url' => asset('img/male-placeholder.png'),
                    ]);
                }
            }

            if ($request->hasFile('photo')) {
                $this->deletePhoto($user->photo_url);
                $photo    = $request->file('photo');
                $filename = 'user_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
                $photo->move(public_path('uploads/users'), $filename);
                $user->photo_url = 'uploads/users/' . $filename;
                $user->save();
            }
        }

        // ---- Non-admin can only change photo ----
        if (! $isAdmin) {
            if (! $request->hasFile('photo') && $request->input('remove_photo') !== '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update profile fields.',
                ], 403);
            }

            return response()->json([
                'success'   => true,
                'message'   => 'Profile photo updated successfully.',
                'photo_url' => $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png'),
            ]);
        }

        // ---- Admin: update fields ----
        $rules = [
            'name'          => 'required|string|max:255',
            'designation'   => 'nullable|string|max:255',
            'email'         => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile_number' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
        ];

        $validated = $request->validate($rules, [
            'mobile_number.regex' => 'Please enter a valid 11-digit Bangladeshi mobile number.',
        ]);

        $user->update($validated);

        return response()->json([
            'success'   => true,
            'message'   => 'Profile updated successfully.',
            'photo_url' => $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Reset own password
    |--------------------------------------------------------------------------
    */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'new_password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/',
            ],
        ], [
            'new_password.regex' => 'Password must contain uppercase, lowercase, number, and special character.',
        ]);

        $user           = auth()->user();
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['success' => true, 'message' => 'Password updated successfully.']);
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}
