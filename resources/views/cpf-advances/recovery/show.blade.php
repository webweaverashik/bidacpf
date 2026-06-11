@extends('layouts.app')

@section('title', $recovery->recovery_no)

@section('header-title')
    @include('cpf-advances.partials.page-header', ['heading' => $recovery->recovery_no, 'crumbs' => ['CPF Operation', 'CPF Advance/Loan', 'Recovery Posting', 'Detail']])
@endsection

@section('content')
    @php $s = $recovery->status->value; @endphp

    <div class="card mb-6">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <h2 class="mb-0">{{ $recovery->recovery_no }}</h2>
                    <span class="{{ $recovery->status->badgeClass() }}">
                        <i class="{{ $recovery->status->icon() }} me-1"></i>{{ $recovery->status->label() }}
                    </span>
                </div>
                <div class="text-gray-700 fw-semibold">
                    Against <a href="{{ route('cpf-advances.show', $advance) }}">{{ $advance->advance_no }}</a>
                    · {{ $advance->employee->name }} <span class="text-muted">({{ $advance->employee->cpf_account_no }})</span>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @can('cpf_advance.recovery')
                    @if ($recovery->isEditable())
                        <a href="{{ route('cpf-advances.recovery.edit', [$advance, $recovery]) }}" class="btn btn-light-primary">
                            <i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>Edit
                        </a>
                        <form action="{{ route('cpf-advances.recovery.destroy', [$advance, $recovery]) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-light-danger"
                                data-kt-confirm="This draft recovery will be permanently deleted.">
                                <i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>Delete
                            </button>
                        </form>
                    @endif
                @endcan

                @can('cpf_advance.submit')
                    @if ($recovery->canSubmit())
                        <form action="{{ route('cpf-advances.recovery.submit', [$advance, $recovery]) }}" method="POST" class="d-inline">
                            @csrf @method('PUT')
                            <button type="button" class="btn btn-primary"
                                data-kt-confirm="Submit this recovery for admin approval?"
                                data-kt-confirm-icon="question" data-kt-confirm-title="Submit recovery">
                                <i class="ki-duotone ki-send fs-3"><span class="path1"></span><span class="path2"></span></i>Submit
                            </button>
                        </form>
                    @endif
                @endcan

                @can('cpf_advance.approve')
                    @if ($recovery->canApprove())
                        <form action="{{ route('cpf-advances.recovery.approve', [$advance, $recovery]) }}" method="POST" class="d-inline">
                            @csrf @method('PUT')
                            <button type="button" class="btn btn-success"
                                data-kt-confirm="Approve this recovery? A credit will be posted to the ledger and the outstanding balance reduced."
                                data-kt-confirm-icon="question" data-kt-confirm-title="Approve recovery">
                                <i class="ki-duotone ki-check-circle fs-3"><span class="path1"></span><span class="path2"></span></i>Approve
                            </button>
                        </form>
                        <button type="button" class="btn btn-light-danger" data-bs-toggle="modal" data-bs-target="#rec_reject_modal">
                            <i class="ki-duotone ki-cross-circle fs-3"><span class="path1"></span><span class="path2"></span></i>Reject
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    @if ($s === 'rejected' && $recovery->reject_reason)
        <div class="alert alert-light-danger border border-danger border-dashed mb-6">
            <span class="fw-bold">Rejected:</span> {{ $recovery->reject_reason }}
        </div>
    @endif

    <div class="row g-6">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><div class="card-title"><h3>Recovery Details</h3></div></div>
                <div class="card-body">
                    <div class="row gy-4">
                        <div class="col-md-6"><span class="text-muted">Amount</span><div class="fs-3 fw-bold text-success">BDT {{ number_format($recovery->amount) }}</div></div>
                        <div class="col-md-6"><span class="text-muted">Recovery Date</span><div class="fw-bold">{{ $recovery->recovery_date?->format('d M Y') }}</div></div>
                        @if ($recovery->status->value === 'approved')
                            <div class="col-md-6"><span class="text-muted">Applied to Principal</span><div class="fw-bold">{{ number_format($recovery->principal_applied) }}</div></div>
                            <div class="col-md-6"><span class="text-muted">Applied to Interest</span><div class="fw-bold text-success">{{ number_format($recovery->interest_applied) }}</div></div>
                        @endif
                        <div class="col-md-6"><span class="text-muted">Deposit Date</span><div class="fw-bold">{{ $recovery->deposit_date?->format('d M Y') ?? '—' }}</div></div>
                        <div class="col-md-6"><span class="text-muted">Deposit Reference</span><div class="fw-bold">{{ $recovery->deposit_reference ?? '—' }}</div></div>
                        <div class="col-md-6"><span class="text-muted">Bank</span><div class="fw-bold">{{ $recovery->bank_name ?? '—' }}</div></div>
                        <div class="col-12"><span class="text-muted">Remarks</span><div class="fw-semibold">{{ $recovery->remarks ?? '—' }}</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-6">
                <div class="card-header"><div class="card-title"><h3>Deposit Slip</h3></div></div>
                <div class="card-body">
                    @if ($recovery->firstAttachment())
                        <a href="{{ asset($recovery->firstAttachment()->file_path) }}" target="_blank"
                            class="d-flex align-items-center border border-gray-300 border-dashed rounded p-4">
                            <i class="ki-duotone ki-file-down fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span></i>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-gray-800">{{ $recovery->firstAttachment()->file_name }}</span>
                                <span class="text-muted fs-7">{{ $recovery->firstAttachment()->formatted_size }}</span>
                            </div>
                        </a>
                    @else
                        <div class="text-muted text-center py-4">No slip attached.</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title"><h3>Audit</h3></div></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Created by</span><span class="fw-bold">{{ $recovery->creator?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Submitted by</span><span class="fw-bold">{{ $recovery->submitter?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Approved by</span><span class="fw-bold">{{ $recovery->approver?->name ?? '—' }}</span></div>
                </div>
            </div>
        </div>
    </div>

    @can('cpf_advance.approve')
        @if ($recovery->canApprove())
            <div class="modal fade" id="rec_reject_modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('cpf-advances.recovery.reject', [$advance, $recovery]) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="modal-header">
                                <h3 class="modal-title">Reject Recovery</h3>
                                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label">Reason (optional)</label>
                                <textarea name="reject_reason" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Reject</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan
@endsection

@push('page-css')
    <link href="{{ asset('css/cpf-advances/cpf-advance.css') }}" rel="stylesheet" type="text/css" />
@endpush

@push('page-js')
    <script src="{{ asset('js/cpf-advances/bida-advance-show.js') }}"></script>
@endpush
