@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/bank-interest/bank-interest.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Distribution ' . $batch->reference_no)

@section('header-title')
    @include('bank-interest.partials.page-header', [
        'heading' => 'Distribution Detail',
        'crumbs' => ['CPF Operation', 'Bank Interest', $batch->reference_no],
    ])
@endsection

@section('content')
    {{-- ============================ Summary card ============================ --}}
    <div class="card mb-6">
        <div class="card-header border-0 pt-6">
            <div class="card-title flex-column">
                <h2 class="mb-1">{{ $batch->reference_no }}</h2>
                <div class="text-muted fs-7">
                    Cut-off {{ $batch->distribution_date->format('d M Y') }} · FY {{ $batch->fiscal_year }} · Batch
                    #{{ $batch->id }}
                </div>
            </div>
            <div class="card-toolbar">
                <span class="{{ $batch->status->badgeClass() }} fs-6 px-4 py-2">
                    <i class="{{ $batch->status->icon() }} me-1"></i>{{ $batch->status->label() }}
                </span>
            </div>
        </div>

        <div class="card-body pt-2">
            <div class="int-summary mb-8">
                <div class="int-tile">
                    <div class="int-tile-label">Interest Received</div>
                    <div class="int-tile-value">৳ {{ number_format((int) $batch->total_interest_amount) }}</div>
                </div>
                <div class="int-tile">
                    <div class="int-tile-label">Eligible Fund Balance</div>
                    <div class="int-tile-value">৳ {{ number_format((int) $batch->total_eligible_balance) }}</div>
                </div>
                <div class="int-tile">
                    <div class="int-tile-label">Members</div>
                    <div class="int-tile-value">{{ number_format($batch->distributionCount()) }}</div>
                </div>
                <div class="int-tile">
                    <div class="int-tile-label">Total Distributed</div>
                    <div class="int-tile-value">৳ {{ number_format($batch->totalDistributed()) }}</div>
                </div>
                <div class="int-tile">
                    <div class="int-tile-label">Rounding Residual</div>
                    <div class="int-tile-value">৳ {{ number_format($batch->roundingResidual()) }}</div>
                </div>
            </div>

            {{-- Meta / audit trail --}}
            <div class="row g-5 mb-6">
                <div class="col-md-4">
                    <div class="text-muted fs-8 text-uppercase">Created By</div>
                    <div class="fw-semibold">{{ $batch->creator?->name ?? 'System' }}</div>
                    <div class="text-muted fs-8">{{ $batch->created_at?->format('d M Y, h:i A') }}</div>
                </div>
                @if ($batch->submittedBy)
                    <div class="col-md-4">
                        <div class="text-muted fs-8 text-uppercase">Submitted By</div>
                        <div class="fw-semibold">{{ $batch->submittedBy->name }}</div>
                        <div class="text-muted fs-8">{{ $batch->submitted_at?->format('d M Y, h:i A') }}</div>
                    </div>
                @endif
                @if ($batch->approvedBy)
                    <div class="col-md-4">
                        <div class="text-muted fs-8 text-uppercase">Approved By</div>
                        <div class="fw-semibold">{{ $batch->approvedBy->name }}</div>
                        <div class="text-muted fs-8">{{ $batch->approved_at?->format('d M Y, h:i A') }}</div>
                    </div>
                @endif
                @if ($batch->reversedBy)
                    <div class="col-md-4">
                        <div class="text-muted fs-8 text-uppercase">Reversed By</div>
                        <div class="fw-semibold">{{ $batch->reversedBy->name }}</div>
                        <div class="text-muted fs-8">{{ $batch->reversed_at?->format('d M Y, h:i A') }}</div>
                    </div>
                @endif
            </div>

            @if ($batch->remarks)
                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mb-6">
                    <i class="ki-duotone ki-information fs-2 text-warning me-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <div class="fs-7"><span class="fw-bold">Remarks:</span> {{ $batch->remarks }}</div>
                </div>
            @endif

            {{-- ===================== Workflow action bar ===================== --}}
            <div class="d-flex flex-wrap gap-3">
                <a href="{{ route('bank-interest.index') }}" class="btn btn-light btn-active-light-primary">
                    <i class="ki-duotone ki-arrow-left fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span></i>Back to list
                </a>

                @can('bank_interest.create')
                    @if ($batch->isEditable())
                        <button type="button" class="btn btn-light-primary" data-bi-action="regenerate">
                            <i class="ki-duotone ki-arrows-circle fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span></i>Recalculate
                        </button>
                    @endif
                @endcan

                @can('bank_interest.submit')
                    @if ($batch->canBeSubmitted())
                        <button type="button" class="btn btn-primary" data-bi-action="submit">
                            <i class="ki-duotone ki-send fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span></i>Submit for Approval
                        </button>
                    @endif
                @endcan

                @can('bank_interest.approve')
                    @if ($batch->canBeApproved())
                        <button type="button" class="btn btn-success" data-bi-action="approve">
                            <i class="ki-duotone ki-check-circle fs-3"> <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Approve &amp; Post Ledger
                        </button>
                    @endif
                    @if ($batch->canBeRejected())
                        <button type="button" class="btn btn-light-danger" data-bs-toggle="modal"
                            data-bs-target="#bi_reject_modal">
                            <i class="ki-duotone ki-cross-circle fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Reject
                        </button>
                    @endif
                @endcan

                @can('bank_interest.reverse')
                    @if ($batch->canBeReversed())
                        <button type="button" class="btn btn-light-danger" data-bi-action="reverse">
                            <i class="ki-duotone ki-arrow-circle-left fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Reverse
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    {{-- ========================= Distribution table ======================== --}}
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" id="bi_dist_search" class="form-control form-control-solid w-md-300px ps-12"
                        placeholder="Search members">
                </div>
            </div>
            <div class="card-toolbar">
                <span class="text-muted fs-7 me-4 d-none d-md-inline">
                    Formula: (Employee CPF Balance ÷ Total CPF Balance) × Bank Interest
                </span>
                <div class="dropdown">
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-exit-up fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>Export
                    </button>
                    <div id="kt_table_report_dropdown_menu"
                        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                        data-kt-menu="true">
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3"
                                data-row-export="xlsx">Export
                                as Excel</a></div>
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3"
                                data-row-export="csv">Export as CSV</a></div>
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3"
                                data-row-export="pdf">Export as PDF</a></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body py-4">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-4 ashik-table" id="bida_dist_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th>CPF A/C No</th>
                        <th>Member</th>
                        <th class="text-end">Balance @ Cut-off (Tk)</th>
                        <th class="text-end">Ratio (%)</th>
                        <th class="text-end">Calculated (Tk)</th>
                        <th class="text-end">Allocated (Tk)</th>
                    </tr>
                </thead>
                <tbody></tbody>
                @if ($batch->distributionCount() > 0)
                    <tfoot>
                        <tr class="fw-bold border-top">
                            <td colspan="3" class="text-end">Total</td>
                            <td class="text-end amount-cell">{{ number_format((int) $batch->total_eligible_balance) }}
                            </td>
                            <td class="text-end">100.0000</td>
                            <td></td>
                            <td class="text-end amount-cell">{{ number_format($batch->totalDistributed()) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- =========================== Reject modal ============================ --}}
    @can('bank_interest.approve')
        @if ($batch->canBeRejected())
            <div class="modal fade" id="bi_reject_modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="fw-bold">Reject Distribution</h2>
                            <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                                <i class="ki-duotone ki-cross fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted">
                                This sends the batch back to the CPF Officer as a draft for correction. No ledger
                                entries are posted.
                            </p>
                            <label class="form-label fw-semibold">Reason (optional)</label>
                            <textarea class="form-control form-control-solid" rows="3" id="bi_reject_remarks"
                                placeholder="Tell the officer what needs fixing"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" data-bi-action="reject-confirm">
                                <i class="ki-duotone ki-cross-circle fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span></i>Send Back
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcan
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaInterestShowConfig = {
            tableId: 'bida_dist_table',
            searchId: 'bi_dist_search',
            distUrl: "{{ route('bank-interest.distributions', $batch) }}",
            exportUrl: "{{ route('bank-interest.distributions.export', $batch) }}",
            csrf: "{{ csrf_token() }}",
            actions: {
                regenerate: {
                    url: "{{ route('bank-interest.regenerate', $batch) }}",
                    confirm: 'Recalculate this distribution from the current cut-off balances? Any existing draft figures will be replaced.',
                    label: 'Recalculate'
                },
                submit: {
                    url: "{{ route('bank-interest.submit', $batch) }}",
                    confirm: 'Submit this distribution for admin approval? It will be locked from edits.',
                    label: 'Submit'
                },
                approve: {
                    url: "{{ route('bank-interest.approve', $batch) }}",
                    confirm: 'Approve this distribution? CPF ledger credits will be posted to every member.',
                    label: 'Approve'
                },
                reverse: {
                    url: "{{ route('bank-interest.reverse', $batch) }}",
                    confirm: 'Reverse this distribution? Mirror debit entries will be posted to unwind every member balance.',
                    label: 'Reverse'
                },
                reject: {
                    url: "{{ route('bank-interest.reject', $batch) }}"
                }
            },
            rejectRemarksId: 'bi_reject_remarks',
            rejectModalId: 'bi_reject_modal'
        };
    </script>
    <script src="{{ asset('js/bank-interest/bida-interest-show.js') }}"></script>
@endpush
