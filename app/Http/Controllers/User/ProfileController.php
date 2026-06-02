<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Cost\CostType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // User profile page
    public function profile()
    {
        $user = User::find(auth()->id());

        /* ---- For Cost Records setting - starts */
        $isAdmin  = $user->isAdmin();
        $branches = Branch::when(! $isAdmin, function ($q) use ($user) {
            $q->where('id', $user->branch_id);
        })
            ->select('id', 'branch_name', 'branch_prefix')
            ->get();

        // Get cost types
        $costTypes = CostType::active()
            ->orderBy('name')
            ->select('id', 'name', 'description')
            ->get();
        /* ---- For Cost Records setting - ends */

        // Get today's collection for the user
        $todayCollection = $user->getTodayCollection();

        return view('settings.users.profile', compact('user', 'todayCollection', 'isAdmin', 'branches', 'costTypes'));
    }

    /**
     * Get wallet logs via AJAX for DataTable.
     */
    public function getWalletLogs(Request $request)
    {
        $user = User::find(auth()->id());

        $query = $user->walletLogs()
            ->with(['creator:id,name', 'user:id,name', 'paymentTransaction:id,payment_invoice_id']);

        // Get total count before filtering
        $totalRecords = $query->count();

        // Type filter
        if ($request->filled('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        // Date range filter (format: d-m-Y from Flatpickr)
        if ($request->filled('start_date') && $request->start_date !== '') {
            $startDate = Carbon::createFromFormat('d-m-Y', $request->start_date)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }

        if ($request->filled('end_date') && $request->end_date !== '') {
            $endDate = Carbon::createFromFormat('d-m-Y', $request->end_date)->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }

        // Search filter
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        $filteredRecords = $query->count();

        // Sorting
        $orderColumnIndex = $request->input('order.0.column', 1);
        $orderDirection   = $request->input('order.0.dir', 'desc');

        $columns = ['id', 'created_at', 'type', 'description', 'amount', 'old_balance', 'new_balance', 'created_by'];

        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        } else {
            $query->latest('created_at');
        }

        // Pagination
        $start  = $request->input('start', 0);
        $length = $request->input('length', 10);

        $logs = $query->skip($start)->take($length)->get();

        // Format data for DataTable
        $data    = [];
        $counter = $start + 1;

        foreach ($logs as $log) {
            // Type badge
            $typeBadge = match ($log->type) {
                'collection' => '<span class="badge badge-success">Collection</span>',
                'settlement' => '<span class="badge badge-info">Settlement</span>',
                default      => '<span class="badge badge-warning">Adjustment</span>',
            };

            // Amount formatting
            $amountHtml = $log->amount >= 0
                ? '<span class="text-success fw-bold">৳ +' . number_format($log->amount, 0) . '</span>'
                : '<span class="text-danger fw-bold">৳ ' . number_format($log->amount, 0) . '</span>';

            // Description with link if applicable
            $descriptionHtml = $log->description ?? '-';
            if ($log->paymentTransaction) {
                $descriptionHtml = '<a href="' . route('invoices.show', $log->paymentTransaction->payment_invoice_id) . '" class="text-gray-800 text-hover-primary text-wrap" target="_blank">' . e($log->description) . '</a>';
            }

            $data[] = [
                'counter'     => $counter++,
                'date'        => '<span class="text-gray-800">' . $log->created_at->format('d M, Y') . '</span><span class="text-gray-500 d-block fs-7">' . $log->created_at->format('h:i A') . '</span>',
                'type'        => $typeBadge,
                'description' => $descriptionHtml,
                'amount'      => $amountHtml,
                'old_balance' => '<span class="text-gray-600">৳' . number_format($log->old_balance, 0) . '</span>',
                'new_balance' => '<span class="text-gray-800 fw-bold">৳' . number_format($log->new_balance, 0) . '</span>',
                'created_by'  => '<span class="text-gray-700">' . ($log->creator->name ?? 'System') . '</span>',
                'date_raw'    => $log->created_at->timestamp,
                'amount_raw'  => $log->amount,
            ];
        }

        return response()->json([
            'draw'            => intval($request->input('draw', 1)),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Get login activities via AJAX for DataTable.
     */
    public function getLoginActivities(Request $request)
    {
        $user = User::find(auth()->id());

        $query = $user->loginActivities();

        // Get total count before filtering
        $totalRecords = $query->count();

        // Device filter
        if ($request->filled('device') && $request->device !== '') {
            $query->where('device', $request->device);
        }

        // Date range filter (format: d-m-Y from Flatpickr)
        if ($request->filled('start_date') && $request->start_date !== '') {
            $startDate = Carbon::createFromFormat('d-m-Y', $request->start_date)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }

        if ($request->filled('end_date') && $request->end_date !== '') {
            $endDate = Carbon::createFromFormat('d-m-Y', $request->end_date)->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }

        // Search filter
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%")
                    ->orWhere('device', 'like', "%{$search}%");
            });
        }

        $filteredRecords = $query->count();

        // Sorting
        $orderColumnIndex = $request->input('order.0.column', 4);
        $orderDirection   = $request->input('order.0.dir', 'desc');

        $columns = ['id', 'ip_address', 'user_agent', 'device', 'created_at'];

        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        } else {
            $query->latest('created_at');
        }

        // Pagination
        $start  = $request->input('start', 0);
        $length = $request->input('length', 10);

        $activities = $query->skip($start)->take($length)->get();

        // Format data for DataTable
        $data    = [];
        $counter = $start + 1;

        foreach ($activities as $activity) {
            // Device badge
            $deviceBadge = match ($activity->device) {
                'Mobile'  => '<span class="badge badge-warning">Mobile</span>',
                'Desktop' => '<span class="badge badge-info">Desktop</span>',
                default   => '<span class="badge badge-secondary">' . e($activity->device) . '</span>',
            };

            $data[] = [
                'counter'    => $counter++,
                'ip_address' => e($activity->ip_address),
                'user_agent' => '<span class="text-gray-800">' . e($activity->user_agent) . '</span>',
                'device'     => $deviceBadge,
                'time'       => '<span class="text-gray-800">' . $activity->created_at->diffForHumans() . '</span><span class="text-gray-500 d-block fs-7">' . $activity->created_at->format('d M, Y h:i A') . '</span>',
                'time_raw'   => $activity->created_at->timestamp,
            ];
        }

        return response()->json([
            'draw'            => intval($request->input('draw', 1)),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    // Update user profile
    public function updateProfile(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->isAdmin();

        // Handle photo upload for all users
        if ($request->hasFile('photo') || $request->has('remove_photo')) {
            $request->validate([
                'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:100',
            ]);

            // Handle photo removal
            if ($request->has('remove_photo') && $request->remove_photo === '1') {
                // Delete old photo file
                if ($user->photo_url && file_exists(public_path($user->photo_url))) {
                    unlink(public_path($user->photo_url));
                }
                $user->photo_url = null;
                $user->save();

                return response()->json([
                    'success'   => true,
                    'message'   => 'Profile photo removed successfully',
                    'photo_url' => asset('img/male-placeholder.png'),
                ]);
            }

            // Handle new photo upload
            if ($request->hasFile('photo')) {
                // Delete old photo file
                if ($user->photo_url && file_exists(public_path($user->photo_url))) {
                    unlink(public_path($user->photo_url));
                }

                $photo    = $request->file('photo');
                $filename = 'user_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
                $photo->move(public_path('uploads/users'), $filename);
                $user->photo_url = 'uploads/users/' . $filename;
                $user->save();

                // If non-admin user, just return success for photo upload
                if (! $isAdmin) {
                    return response()->json([
                        'success'   => true,
                        'message'   => 'Profile photo updated successfully',
                        'photo_url' => asset($user->photo_url),
                    ]);
                }
            }
        }

        // Only admin can update profile fields
        if (! $isAdmin) {
            // For non-admin users without photo upload, return error
            if (! $request->hasFile('photo') && ! $request->has('remove_photo')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update profile fields.',
                ], 403);
            }

            return response()->json([
                'success'   => true,
                'message'   => 'Profile photo updated successfully',
                'photo_url' => $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png'),
            ]);
        }

        // Admin user - validate and update all fields
        $rules = [
            'name'          => 'required|string|max:255',
            'email'         => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile_number' => ['required', 'regex:/^01[3-9]\d{8}$/'],
        ];

        // Remove uniqueness rule if value didn't change
        if ($request->email === $user->email) {
            unset($rules['email']);
        }

        if ($request->mobile_number === $user->mobile_number) {
            unset($rules['mobile_number']);
        }

        $validated = $request->validate($rules);

        $user->update($validated);

        return response()->json([
            'success'   => true,
            'message'   => 'Profile updated successfully',
            'photo_url' => $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png'),
        ]);
    }
}
