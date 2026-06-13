<div class="card card-flush h-100">
    <div class="card-header pt-5">
        <div class="card-title fs-4 fw-bold text-gray-900">Recent Advances</div>
        @if (Route::has('cpf-advances.index'))
            <div class="card-toolbar">
                <a href="{{ route('cpf-advances.index') }}" class="btn btn-sm btn-light">View all</a>
            </div>
        @endif
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table ashik-table align-middle table-row-dashed fs-7 gy-3 mb-0">
                <thead>
                    <tr class="text-muted fw-semibold text-uppercase fs-8">
                        <th>Advance No</th>
                        <th>Member</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentAdvances as $a)
                        <tr>
                            <td class="fw-semibold text-gray-800 text-nowrap">{{ $a->advance_no }}</td>
                            <td>
                                <span class="fw-semibold text-gray-800 d-block">{{ $a->employee?->name ?? '—' }}</span>
                                <span class="text-muted fs-8">{{ $a->employee?->cpf_account_no }}</span>
                            </td>
                            <td class="text-end fw-bold text-gray-800 text-nowrap">৳
                                {{ number_format((int) ($a->approved_amount ?? $a->requested_amount)) }}</td>
                            <td class="text-end"><span
                                    class="{{ $a->status->badgeClass() }}">{{ $a->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-10">No advances recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
