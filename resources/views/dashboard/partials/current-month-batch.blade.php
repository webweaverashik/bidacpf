{{-- Tells the officer at a glance whether this calendar month's contribution batch
     exists and where it stands. $currentMonthBatch is a CpfContributionBatch|null. --}}
@php
    $month = now()->format('F Y');
@endphp
<div class="card card-flush mb-5 mb-xl-8">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-4 py-6">
        <div class="d-flex align-items-center">
            <i class="ki-outline ki-calendar-8 fs-2x text-primary me-4"></i>
            <div class="d-flex flex-column">
                <span class="fs-5 fw-bold text-gray-900">Contribution batch — {{ $month }}</span>
                @if ($currentMonthBatch)
                    <span class="fs-7 text-muted mt-1">
                        Status:
                        <span class="{{ $currentMonthBatch->status->badgeClass() }}">
                            {{ $currentMonthBatch->status->label() }}
                        </span>
                        · {{ number_format($currentMonthBatch->employeeCount()) }} members
                        · ৳ {{ number_format($currentMonthBatch->totalContribution()) }}
                    </span>
                @else
                    <span class="fs-7 text-muted mt-1">No batch has been generated for this month yet.</span>
                @endif
            </div>
        </div>
        @can('cpf_contribution.view')
            <a href="{{ $currentMonthBatch ? route('cpf-contributions.show', $currentMonthBatch->id) : route('cpf-contributions.index') }}"
                class="btn btn-sm btn-primary">
                {{ $currentMonthBatch ? 'Open batch' : 'Go to contributions' }}
                <i class="ki-outline ki-arrow-right fs-6 ms-1"></i>
            </a>
        @endcan
    </div>
</div>
