<?php
namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\LoginActivity;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Show login form
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login')->with('warning', 'Please login first.');
    }

    // Handle login
    public function login(Request $request)
    {
        /**
         * ------------------------------------
         * 1. Basic validation (AJAX friendly)
         * ------------------------------------
         */
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'message' => $validator->errors()->first(),
                ],
                422,
            );
        }

        /**
         * ------------------------------------
         * 2. Detect login field
         * ------------------------------------
         * If valid email → use email
         * Otherwise → treat as BP number
         */
        // $loginValue = trim($request->login);

        // $loginField = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile_number';

        /**
         * ------------------------------------
         * 3. Fetch user (including soft deleted)
         * ------------------------------------
         */
        $user = User::withTrashed()->where('email', $request->email)->first();

        if (! $user) {
            return response()->json(
                [
                    'message' => 'No user found!',
                ],
                401,
            );
        }

        /**
         * ------------------------------------
         * 4. Account state checks
         * ------------------------------------
         */
        if ($user->trashed()) {
            return response()->json(
                [
                    'message' => 'This account is invalid or deleted.',
                ],
                403,
            );
        }

        if (! $user->is_active) {
            return response()->json(
                [
                    'message' => 'Account is inactive. Please contact admin.',
                ],
                403,
            );
        }

        /**
         * ------------------------------------
         * 5. Attempt authentication
         * ------------------------------------
         */
        if (
            ! Auth::attempt([
                'email' => $request->email,
                'password'  => $request->password,
            ])
        ) {
            return response()->json(
                [
                    'message' => 'User or password is incorrect.',
                ],
                401,
            );
        }

        /**
         * ------------------------------------
         * 6. Login success → store activity
         * ------------------------------------
         */
        $user = Auth::user();

        LoginActivity::create([
            'user_id'    => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'device'     => $this->detectDevice($request->header('User-Agent')),
        ]);

        /**
         * ------------------------------------
         * 7. Success response
         * ------------------------------------
         */
        return response()->json([
            'message'  => 'Login successful!',
            // 'redirect' => $user->role->name === 'Operator' ? route('dashboard') : route('reports.index'),
            'redirect' => route('dashboard'),
        ]);
    }

    // Helper function to detect device type
    private function detectDevice($userAgent)
    {
        if (strpos($userAgent, 'Mobile') !== false) {
            return 'Mobile';
        } elseif (strpos($userAgent, 'Tablet') !== false) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }

    // Logout
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
