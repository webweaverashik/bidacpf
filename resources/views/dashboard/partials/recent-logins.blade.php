<div class="card card-flush h-100">
    <div class="card-header pt-5">
        <div class="card-title fs-4 fw-bold text-gray-900">Recent Sign-ins</div>
    </div>
    <div class="card-body pt-3">
        @forelse ($recentLogins as $login)
            <div class="d-flex align-items-center mb-6">
                <div class="symbol symbol-40px me-4">
                    <span class="symbol-label bg-light-primary">
                        <i class="ki-outline ki-profile-circle fs-2 text-primary"></i>
                    </span>
                </div>
                <div class="d-flex flex-column flex-grow-1">
                    <span class="fw-semibold text-gray-800 fs-7">{{ $login->user?->name ?? 'Unknown user' }}</span>
                    <span class="text-muted fs-8">{{ $login->device ?: $login->user_agent }} ·
                        {{ $login->ip_address }}</span>
                </div>
                <span class="text-muted fs-8 text-nowrap">{{ $login->created_at?->diffForHumans() }}</span>
            </div>
        @empty
            <div class="text-center text-muted py-10">No sign-in activity yet.</div>
        @endforelse
    </div>
</div>
