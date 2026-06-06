<?php
namespace App\Http\Controllers;

use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = Activity::with('causer', 'subject')
            ->when(request('log_name'), fn($q) => $q->where('log_name', request('log_name')))
            ->when(request('causer_id'), fn($q) => $q->where('causer_id', request('causer_id')))
            ->when(request('from'), fn($q) => $q->whereDate('created_at', '>=', request('from')))
            ->when(request('to'), fn($q) => $q->whereDate('created_at', '<=', request('to')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $logNames = Activity::distinct()->orderBy('log_name')->pluck('log_name');

        return view('audit-logs.index', compact('logs', 'logNames'));
    }

    public function show(Activity $log): View
    {
        $log->load('causer', 'subject');

        return view('audit-logs.show', compact('log'));
    }
}
