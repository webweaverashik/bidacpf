{{-- Members behind on advance repayments. Rows come from
     DashboardService::irregularRecoveries(), already classified and sorted
     worst-first. 'defaulter' = 3+ missed installments; 'irregular' = 1-2 missed
     or a gap month in the payment history. --}}
@php
    $defaulterCount = $irregularRecoveries->where('flag', 'defaulter')->count();
    $irregularCount = $irregularRecoveries->where('flag', 'irregular')->count();
@endphp
<div class="card card-flush h-100">
    <div class="card-header pt-5">
        <div class="card-title d-flex flex-column">
            <span class="fs-4 fw-bold text-gray-900">Advance Recovery — Attention Needed</span>
            <span class="fs-7 text-muted mt-1">Members repaying irregularly or behind by more than two
                installments</span>
        </div>
        <div class="card-toolbar gap-2">
            <span class="badge badge-light-danger">{{ number_format($defaulterCount) }} defaulting</span>
            <span class="badge badge-light-warning">{{ number_format($irregularCount) }} irregular</span>
        </div>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table ashik-table align-middle table-row-dashed fs-7 gy-3 mb-0">
                <thead>
                    <tr class="text-muted fw-semibold text-uppercase fs-8">
                        <th>Member</th>
                        <th>Advance No</th>
                        <th class="text-end">Installment</th>
                        <th class="text-center">Paid / Due</th>
                        <th class="text-center">Missed</th>
                        <th class="text-end">Outstanding</th>
                        <th>Last Payment</th>
                        <th class="text-center">Flag</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($irregularRecoveries as $row)
                        <tr>
                            <td>
                                <span class="fw-semibold text-gray-800 d-block">{{ $row['employee_name'] }}</span>
                                <span class="text-muted fs-8">{{ $row['cpf_account_no'] }}</span>
                            </td>
                            <td class="fw-semibold text-gray-700 text-nowrap">{{ $row['advance_no'] }}</td>
                            <td class="text-end text-gray-700 text-nowrap">৳
                                {{ number_format($row['installment_amount']) }}</td>
                            <td class="text-center text-gray-700">{{ $row['paid'] }} / {{ $row['expected'] }}</td>
                            <td class="text-center">
                                <span class="fw-bold {{ $row['missed'] >= 3 ? 'text-danger' : 'text-warning' }}">
                                    {{ $row['missed'] }}
                                </span>
                            </td>
                            <td class="text-end fw-bold text-gray-800 text-nowrap">৳
                                {{ number_format($row['outstanding']) }}</td>
                            <td class="text-nowrap text-gray-700">
                                {{ $row['last_recovery_date']?->format('d M Y') ?? '— none —' }}
                            </td>
                            <td class="text-center">
                                @if ($row['flag'] === 'defaulter')
                                    <span class="badge badge-light-danger">Defaulter</span>
                                @else
                                    <span class="badge badge-light-warning">Irregular</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if (Route::has('cpf-advances.show'))
                                    <a href="{{ route('cpf-advances.show', $row['advance_id']) }}"
                                        class="btn btn-icon btn-sm btn-light-primary" title="Open advance">
                                        <i class="ki-outline ki-eye fs-5"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-10">
                                <i class="ki-outline ki-check-circle fs-2x text-success d-block mb-2"></i>
                                All advance recoveries are on track.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
