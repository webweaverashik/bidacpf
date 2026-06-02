<?php
namespace App\Http\Controllers;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on user role
     */
    public function index()
    {
        $user = auth()->user();

        foreach (['Admin', 'CPF Officer', 'Auditor'] as $role) {
            $isAdmin = $user->isAdmin();

            return view("dashboard.admin.index", compact('isAdmin'));
        }

        abort(403, 'Unauthorized access');
    }
}
