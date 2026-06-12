@push('page-css')
    <link href="{{ asset('css/cpf-settlements/cpf-settlements.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Settlement ' . $settlement->settlement_no)

@section('header-title')
    @include('cpf-settlements.partials.page-header', [
        'heading' => 'Settlement Detail',
        'crumbs' => ['Final Settlement', $settlement->settlement_no],
    ])
@endsection

@section('content')
    <div class="card mb-6">
        <div class="card-header border-0 pt-6">
            <div class="card-title flex-column">
                <h2 class="mb-1">{{ $settlement->settlement_no }}</h2>
                <div class="text-muted fs-7">
                    {{ $settlement->employee->name }} ({{ $settlement->employee->cpf_account_no }})
                    · <span
                        class="{{ $settlement->settlement_type->badgeClass() }}">{{ $settlement->settlement_type->label() }}</span>
                    · Settlement {{ $settlement->settlement_date->format('d M Y') }}
                </div>
            </div>
            <div class="card-toolbar">
                <span class="{{ $settlement->status->badgeClass() }} fs-6 px-4 py-2">
                    <i class="{{ $settlement->status->icon() }} me-1"></i>{{ $settlement->status->label() }}
                </span>
            </div>
        </div>

        <div class="card-body pt-2">
            {{-- ============================ Figure tiles ============================ --}}
            <div class="stl-summary mb-8">
                <div class="stl-tile">
                    <div class="stl-tile-label">Closing Balance</div>
                    <div class="stl-tile-value">৳ {{ number_format((int) $settlement->closing_balance) }}</div>
                </div>
                <div class="stl-tile">
                    <div class="stl-tile-label">Outstanding Advance</div>
                    <div class="stl-tile-value">৳ {{ number_format((int) $settlement->outstanding_advance) }}</div>
                </div>
                <div class="stl-tile">
                    <div class="stl-tile-label">Advance Adjustment</div>
                    <div class="stl-tile-value">৳ {{ number_format((int) $settlement->advance_adjustment) }}</div>
                </div>
                <div class="stl-tile is-payable">
                    <div class="stl-tile-label">Total Payable</div>
                    <div class="stl-tile-value">৳ {{ number_format((int) $settlement->total_payable) }}</div>
                </div>
            </div>

            {{-- Live note while still editable/pending: what approval would post now --}}
            @if (!$settlement->status->isLocked() || $settlement->canApprove())
                @if ((int) $preview['total_payable'] !== (int) $settlement->total_payable)
                    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mb-6">
                        <i class="ki-outline ki-information fs-2 text-warning me-3"></i>
                        <div class="fs-7">
                            The ledger balance has changed since this draft was saved. On approval the system will
                            recompute and post a payout of
                            <span class="fw-bold">৳ {{ number_format((int) $preview['total_payable']) }}</span>
                            (closing balance ৳ {{ number_format((int) $preview['closing_balance']) }}).
                        </div>
                    </div>
                @endif
            @endif

            {{-- ============================ Payee + meta ============================ --}}
            <div class="row g-5 mb-6">
                <div class="col-md-4">
                    <div class="text-muted fs-8 text-uppercase">Payee</div>
                    <div class="fw-semibold">{{ $settlement->payeeName() }}</div>
                    @if ($settlement->payee_relation)
                        <div class="text-muted fs-8">{{ $settlement->payee_relation }}</div>
                    @endif
                    @if ($settlement->payee_detail)
                        <div class="text-muted fs-8">{{ $settlement->payee_detail }}</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-8 text-uppercase">Application Date</div>
                    <div class="fw-semibold">{{ $settlement->application_date->format('d M Y') }}</div>
                </div>
                @if ($settlement->firstAttachment())
                    <div class="col-md-4">
                        <div class="text-muted fs-8 text-uppercase">Supporting Document</div>
                        <a href="{{ $settlement->firstAttachment()->url }}" target="_blank"
                            class="fw-semibold text-hover-primary">
                            <i
                                class="ki-outline ki-file-down fs-4 me-1"></i>{{ $settlement->firstAttachment()->file_name }}
                        </a>
                        <div class="text-muted fs-8">{{ $settlement->firstAttachment()->formatted_size }}</div>
                    </div>
                @endif
            </div>

            {{-- ============================ Audit trail ============================ --}}
            <div class="row g-5 mb-6">
                <div class="col-md-3">
                    <div class="text-muted fs-8 text-uppercase">Created By</div>
                    <div class="fw-semibold">{{ $settlement->creator?->name ?? 'System' }}</div>
                    <div class="text-muted fs-8">{{ $settlement->created_at?->format('d M Y, h:i A') }}</div>
                </div>
                @if ($settlement->submitter)
                    <div class="col-md-3">
                        <div class="text-muted fs-8 text-uppercase">Submitted By</div>
                        <div class="fw-semibold">{{ $settlement->submitter->name }}</div>
                        <div class="text-muted fs-8">{{ $settlement->submitted_at?->format('d M Y, h:i A') }}</div>
                    </div>
                @endif
                @if ($settlement->approver)
                    <div class="col-md-3">
                        <div class="text-muted fs-8 text-uppercase">Approved By</div>
                        <div class="fw-semibold">{{ $settlement->approver->name }}</div>
                        <div class="text-muted fs-8">{{ $settlement->approval_date?->format('d M Y') }}</div>
                    </div>
                @endif
                @if ($settlement->rejecter)
                    <div class="col-md-3">
                        <div class="text-muted fs-8 text-uppercase">Rejected By</div>
                        <div class="fw-semibold">{{ $settlement->rejecter->name }}</div>
                        <div class="text-muted fs-8">{{ $settlement->rejected_at?->format('d M Y, h:i A') }}</div>
                    </div>
                @endif
            </div>

            @if ($settlement->remarks)
                <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-4">
                    <i class="ki-outline ki-information fs-2 text-primary me-3"></i>
                    <div class="fs-7"><span class="fw-bold">Remarks:</span> {{ $settlement->remarks }}</div>
                </div>
            @endif

            @if ($settlement->reject_reason)
                <div class="notice d-flex bg-light-danger rounded border-danger border border-dashed p-4 mb-4">
                    <i class="ki-outline ki-information fs-2 text-danger me-3"></i>
                    <div class="fs-7"><span class="fw-bold">Rejection reason:</span> {{ $settlement->reject_reason }}
                    </div>
                </div>
            @endif

            {{-- ===================== Workflow action bar ===================== --}}
            <div class="d-flex flex-wrap gap-3 mt-6">
                <a href="{{ route('cpf-settlements.index') }}" class="btn btn-light btn-active-light-primary">
                    <i class="ki-duotone ki-arrow-left fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>Back to list
                </a>

                @can('cpf_settlement.create')
                    @if ($settlement->isEditable())
                        <a href="{{ route('cpf-settlements.edit', $settlement) }}" class="btn btn-light-primary">
                            <i class="ki-duotone ki-pencil fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Edit
                        </a>
                    @endif
                @endcan

                @can('cpf_settlement.submit')
                    @if ($settlement->canSubmit())
                        <button type="button" class="btn btn-primary" data-stl-action="submit">
                            <i class="ki-duotone ki-send fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Submit for Approval
                        </button>
                    @endif
                @endcan

                @can('cpf_settlement.approve')
                    @if ($settlement->canApprove())
                        <button type="button" class="btn btn-success" data-stl-action="approve">
                            <i class="ki-duotone ki-check-circle fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Approve &amp; Post Closing Entry
                        </button>
                        <button type="button" class="btn btn-light-danger" data-bs-toggle="modal"
                            data-bs-target="#stl_reject_modal">
                            <i class="ki-duotone ki-cross-circle fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Reject
                        </button>
                    @endif
                @endcan

                @can('cpf_settlement.create')
                    @if ($settlement->canDelete())
                        <button type="button" class="btn btn-light-danger ms-auto" data-stl-action="delete">
                            <i class="ki-duotone ki-trash fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>Delete Draft
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    {{-- =========================== Reject modal ============================ --}}
    @can('cpf_settlement.approve')
        @if ($settlement->canReject())
            <div class="modal fade" id="stl_reject_modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="fw-bold">Reject Settlement</h2>
                            <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                                <i class="ki-duotone ki-cross fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted">
                                This returns the settlement to the CPF Officer as a draft for correction. No ledger
                                entry is posted and the member stays active.
                            </p>
                            <label class="form-label fw-semibold">Reason (optional)</label>
                            <textarea class="form-control form-control-solid" rows="3" id="stl_reject_reason"
                                placeholder="Tell the officer what needs fixing"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" data-stl-action="reject-confirm">
                                <i class="ki-dutone ki-cross-circle fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>Send Back
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcan
@endsection

@push('page-js')
    <script>
        var BidaSettlementShowConfig = {
            csrf: "{{ csrf_token() }}",
            actions: {
                submit: {
                    url: "{{ route('cpf-settlements.submit', $settlement) }}",
                    confirm: 'Submit this settlement for admin approval? It will be locked from edits.',
                    label: 'Submit'
                },
                approve: {
                    url: "{{ route('cpf-settlements.approve', $settlement) }}",
                    confirm: 'Approve this settlement? The closing entry will be posted, any open advance written off, and the member settled. This cannot be undone.',
                    label: 'Approve'
                },
                delete: {
                    url: "{{ route('cpf-settlements.destroy', $settlement) }}",
                    confirm: 'Delete this draft settlement? This cannot be undone.',
                    label: 'Delete'
                },
                reject: {
                    url: "{{ route('cpf-settlements.reject', $settlement) }}"
                }
            },
            rejectReasonId: 'stl_reject_reason'
        };
    </script>
    <script src="{{ asset('js/cpf-settlements/bida-settlement-show.js') }}"></script>
@endpush
