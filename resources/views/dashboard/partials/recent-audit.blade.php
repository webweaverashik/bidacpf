<div class="card card-flush h-100">
    <div class="card-header pt-5">
        <div class="card-title fs-4 fw-bold text-gray-900">Recent Activity</div>
        @if (Route::has('audit-logs.index'))
            <div class="card-toolbar">
                <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-light">Audit logs</a>
            </div>
        @endif
    </div>
    <div class="card-body pt-3">
        @forelse ($recentAuditActivity as $activity)
            <div class="d-flex align-items-start mb-6">
                <span class="bullet bullet-vertical h-40px bg-primary me-4 mt-1"></span>
                <div class="d-flex flex-column flex-grow-1">
                    <span class="fw-semibold text-gray-800 fs-7">{{ ucfirst($activity->description ?? 'Activity') }}</span>
                    <span class="text-muted fs-8">
                        {{ $activity->causer?->name ?? 'System' }}
                        @if ($activity->event)
                            · {{ ucfirst($activity->event) }}
                        @endif
                    </span>
                </div>
                <span class="text-muted fs-8 text-nowrap">{{ $activity->created_at?->diffForHumans() }}</span>
            </div>
        @empty
            <div class="text-center text-muted py-10">No recorded activity yet.</div>
        @endforelse
    </div>
</div>
