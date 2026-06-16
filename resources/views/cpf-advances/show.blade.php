@extends('layouts.app')

@section('title', $advance->advance_no)

@section('header-title')
    @include('cpf-advances.partials.page-header', ['heading' => $advance->advance_no, 'crumbs' => ['CPF Operation', 'CPF Advance/Loan', 'Advance Applications', 'Detail']])
@endsection

@section('content')
    @php
        $s = $advance->status->value;
        $progress = $advance->progressPercent();
    @endphp

    <!--begin::Header card-->
    <div class="card mb-6">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <h2 class="mb-0">{{ $advance->advance_no }}</h2>
                    <span class="{{ $advance->status->badgeClass() }}">
                        <i class="{{ $advance->status->icon() }} me-1"></i>{{ $advance->status->label() }}
                    </span>
                </div>
                <div class="text-gray-700 fw-semibold">
                    {{ $advance->employee->name }}
                    <span class="text-muted">({{ $advance->employee->cpf_account_no }})</span>
                </div>
                <div class="text-muted fs-7 mt-1">Applied on {{ $advance->application_date?->format('d M Y') }}</div>
            </div>

            <!--begin::Actions-->
            <div class="d-flex flex-wrap gap-2">
                {{-- Officer: draft actions --}}
                @can('cpf_advance.create')
                    @if ($advance->isEditable())
                        <a href="{{ route('cpf-advances.edit', $advance) }}" class="btn btn-light-primary">
                            <i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>Edit
                        </a>
                        <form action="{{ route('cpf-advances.destroy', $advance) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-light-danger"
                                data-kt-confirm="This draft advance will be permanently deleted."
                                data-kt-confirm-icon="warning">
                                <i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>Delete
                            </button>
                        </form>
                    @endif
                @endcan

                @can('cpf_advance.submit')
                    @if ($advance->canSubmit())
                        <form action="{{ route('cpf-advances.submit', $advance) }}" method="POST" class="d-inline">
                            @csrf @method('PUT')
                            <button type="button" class="btn btn-primary"
                                data-kt-confirm="Submit this advance for admin approval? You won't be able to edit it afterwards."
                                data-kt-confirm-icon="question" data-kt-confirm-title="Submit for approval">
                                <i class="ki-duotone ki-send fs-3"><span class="path1"></span><span class="path2"></span></i>Submit
                            </button>
                        </form>
                    @endif
                @endcan

                {{-- Admin: review actions --}}
                @can('cpf_advance.approve')
                    @if ($advance->canApprove())
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#adv_approve_modal">
                            <i class="ki-duotone ki-check-circle fs-3"><span class="path1"></span><span class="path2"></span></i>Approve
                        </button>
                        <button type="button" class="btn btn-light-danger" data-bs-toggle="modal" data-bs-target="#adv_reject_modal">
                            <i class="ki-duotone ki-cross-circle fs-3"><span class="path1"></span><span class="path2"></span></i>Reject
                        </button>
                    @endif
                    @if ($advance->canRecover())
                        <button type="button" class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#adv_reschedule_modal">
                            <i class="ki-duotone ki-time fs-3"><span class="path1"></span><span class="path2"></span></i>Reschedule
                        </button>
                    @endif
                @endcan

                {{-- Officer: record recovery --}}
                @can('cpf_advance.recovery')
                    @if ($advance->canRecover())
                        <a href="{{ route('cpf-advances.recovery.create', $advance) }}" class="btn btn-primary">
                            <i class="ki-duotone ki-plus fs-2"></i>Record Recovery
                        </a>
                    @endif
                @endcan
            </div>
            <!--end::Actions-->
        </div>
    </div>
    <!--end::Header card-->

    @if ($s === 'rejected' && $advance->reject_reason)
        <div class="alert alert-light-danger border border-danger border-dashed mb-6">
            <span class="fw-bold">Rejected:</span> {{ $advance->reject_reason }}
        </div>
    @endif

    <!--begin::Summary tiles-->
    <div class="adv-summary mb-6">
        <div class="adv-tile">
            <div class="adv-tile-label">{{ $advance->approved_amount ? 'Approved Amount' : 'Requested Amount' }}</div>
            <div class="adv-tile-value">{{ number_format($advance->approved_amount ?? $advance->requested_amount) }}</div>
        </div>
        <div class="adv-tile">
            <div class="adv-tile-label">Outstanding (Principal + Interest)</div>
            <div class="adv-tile-value text-danger">
                {{ $advance->approved_amount ? number_format($advance->outstanding_amount) : '—' }}
            </div>
        </div>
        <div class="adv-tile">
            <div class="adv-tile-label">Interest ({{ rtrim(rtrim(number_format($advance->interest_rate, 2), '0'), '.') }}%)</div>
            <div class="adv-tile-value text-success">
                {{ number_format($advance->projectedInterest()) }}
                @if ($advance->interest_credited)
                    <span class="badge badge-light-success fs-8 align-middle">Repaid</span>
                @elseif (!$advance->approved_amount)
                    <span class="badge badge-light-warning fs-8 align-middle">Projected</span>
                @endif
            </div>
        </div>
        <div class="adv-tile">
            <div class="adv-tile-label">Per Installment × {{ $advance->installment_count }}</div>
            <div class="adv-tile-value">{{ number_format($advance->projectedInstallment()) }}</div>
        </div>
    </div>
    <!--end::Summary tiles-->

    <!--begin::Repayment schedule-->
    <div class="card mb-6">
        <div class="card-body py-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Repayment Schedule</h3>
                @if (!$advance->approved_amount)
                    <span class="badge badge-light-warning">Projected — finalised on approval</span>
                @endif
            </div>
            <div class="row gy-4">
                <div class="col-md-3 col-6">
                    <span class="text-muted fs-7 text-uppercase d-block">Principal</span>
                    <span class="fs-4 fw-bold text-gray-900">{{ number_format($advance->effectiveAmount()) }}</span>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-muted fs-7 text-uppercase d-block">Interest</span>
                    <span class="fs-4 fw-bold text-success">{{ number_format($advance->projectedInterest()) }}</span>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-muted fs-7 text-uppercase d-block">Total Repayable</span>
                    <span class="fs-4 fw-bold text-primary">{{ number_format($advance->totalPayable()) }}</span>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-muted fs-7 text-uppercase d-block">Per Installment</span>
                    <span class="fs-4 fw-bold text-gray-900">{{ number_format($advance->projectedInstallment()) }}</span>
                    <span class="text-muted fs-8">× {{ $advance->installment_count }} months</span>
                </div>
            </div>

            @if ($advance->approved_amount)
                <div class="separator separator-dashed my-4"></div>
                <div class="row gy-4">
                    <div class="col-md-3 col-6">
                        <span class="text-muted fs-7 text-uppercase d-block">Principal Outstanding</span>
                        <span class="fs-5 fw-bold text-gray-900">{{ number_format($advance->principal_outstanding) }}</span>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="text-muted fs-7 text-uppercase d-block">Interest Outstanding</span>
                        <span class="fs-5 fw-bold text-gray-900">{{ number_format($advance->interest_outstanding) }}</span>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="text-muted fs-7 text-uppercase d-block">Installments Paid</span>
                        <span class="fs-5 fw-bold text-gray-900">{{ $advance->installmentsPaid() }} / {{ $advance->installment_count }}</span>
                    </div>
                    <div class="col-md-3 col-6">
                        <span class="text-muted fs-7 text-uppercase d-block">Recovered</span>
                        <span class="fs-5 fw-bold text-success">{{ number_format($advance->totalRecovered()) }}</span>
                    </div>
                </div>
            @endif

            <div class="text-muted fs-8 mt-3">
                Total repayable = principal + interest. Each installment clears the principal first; once the
                principal is cleared the remainder reduces the interest. The remaining per-installment is
                recalculated after every approved recovery.
            </div>
        </div>
    </div>
    <!--end::Repayment schedule-->

    @if ($advance->approved_amount)
        <div class="card mb-6"><div class="card-body py-5">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-semibold text-gray-700">Repayment progress</span>
                <span class="fw-bold">{{ $progress }}% — {{ number_format($advance->totalRecovered()) }} of {{ number_format($advance->approved_amount) }}</span>
            </div>
            <div class="adv-progress"><span style="width: {{ min(100, $progress) }}%"></span></div>
        </div></div>
    @endif

    <!--begin::Tabs-->
    <div class="card">
        <div class="card-header card-header-stretch">
            <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 align-self-end" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#adv_tab_details" role="tab">Details</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#adv_tab_recoveries" role="tab">Recoveries ({{ $advance->recoveries->count() }})</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#adv_tab_docs" role="tab">Documents</a></li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!--begin::Details-->
                <div class="tab-pane fade show active" id="adv_tab_details" role="tabpanel">
                    <div class="row gy-4">
                        <div class="col-md-6"><span class="text-muted">Created by</span><div class="fw-bold">{{ $advance->creator?->name ?? '—' }}</div></div>
                        <div class="col-md-6"><span class="text-muted">Submitted</span><div class="fw-bold">{{ $advance->submitter?->name ?? '—' }} @if($advance->submitted_at)<span class="text-muted fs-7">· {{ $advance->submitted_at->format('d M Y') }}</span>@endif</div></div>
                        <div class="col-md-6"><span class="text-muted">Approved by</span><div class="fw-bold">{{ $advance->approver?->name ?? '—' }} @if($advance->approval_date)<span class="text-muted fs-7">· {{ $advance->approval_date->format('d M Y') }}</span>@endif</div></div>
                        <div class="col-md-6"><span class="text-muted">Interest credited</span><div class="fw-bold">{{ $advance->interest_credited_at?->format('d M Y') ?? 'Not yet' }}</div></div>
                        <div class="col-12"><span class="text-muted">Remarks</span><div class="fw-semibold">{{ $advance->remarks ?? '—' }}</div></div>
                    </div>
                </div>
                <!--end::Details-->

                <!--begin::Recoveries-->
                <div class="tab-pane fade" id="adv_tab_recoveries" role="tabpanel">
                    <table class="table align-middle table-row-dashed fs-6 gy-3 ashik-table">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Recovery No</th><th>Date</th><th class="text-end">Amount</th>
                                <th>Deposit Ref</th><th>Status</th><th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($advance->recoveries as $rec)
                                <tr>
                                    <td class="fw-bold text-gray-800">{{ $rec->recovery_no }}</td>
                                    <td>{{ $rec->recovery_date?->format('d M Y') }}</td>
                                    <td class="text-end amount-cell">{{ number_format($rec->amount) }}</td>
                                    <td>{{ $rec->deposit_reference ?? '—' }}</td>
                                    <td><span class="{{ $rec->status->badgeClass() }}"><i class="{{ $rec->status->icon() }} me-1"></i>{{ $rec->status->label() }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('cpf-advances.recovery.show', [$advance, $rec]) }}" class="btn btn-sm btn-light btn-active-light-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-6">No recoveries recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!--end::Recoveries-->

                <!--begin::Documents-->
                <div class="tab-pane fade" id="adv_tab_docs" role="tabpanel">
                    @forelse ($advance->attachments as $file)
                        <a href="{{ $file->url }}" target="_blank"
                            class="d-flex align-items-center border border-gray-300 border-dashed rounded p-4 mb-3">
                            <i class="ki-duotone ki-file-down fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span></i>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-gray-800">{{ $file->file_name }}</span>
                                <span class="text-muted fs-7">{{ $file->formatted_size }} · {{ $file->created_at?->format('d M Y') }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="text-muted text-center py-6">No documents attached.</div>
                    @endforelse
                </div>
                <!--end::Documents-->
            </div>
        </div>
    </div>
    <!--end::Tabs-->

    @can('cpf_advance.approve')
        @if ($advance->canApprove())
            @include('cpf-advances.partials.approve-modal')
            @include('cpf-advances.partials.reject-modal')
        @endif
        @if ($advance->canRecover())
            @include('cpf-advances.partials.reschedule-modal')
        @endif
    @endcan
@endsection

@push('page-css')
    <link href="{{ asset('css/cpf-advances/cpf-advance.css') }}" rel="stylesheet" type="text/css" />
@endpush

@push('page-js')
    <script src="{{ asset('js/cpf-advances/bida-advance-show.js') }}"></script>
@endpush
