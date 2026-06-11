@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/cpf-contributions/show.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Batch — ' . $batch->month_label)

@php
    $canEdit = auth()->user()->can('cpf_contribution.create');
    $canSubmit = auth()->user()->can('cpf_contribution.submit');
    $canApprove = auth()->user()->can('cpf_contribution.approve');
    $canReverse = auth()->user()->can('cpf_contribution.reverse');

    // Use the rates snapshotted on the batch; fall back to current settings for legacy batches.
    $empRate = $batch->employee_rate ?? \App\Models\Setting::employeeContributionRate();
    $govRate = $batch->government_rate ?? \App\Models\Setting::governmentContributionRate();

    $empTotal = $batch->totalEmployeeContribution();
    $govTotal = $batch->totalGovernmentContribution();
    $grandTotal = $empTotal + $govTotal;
@endphp

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Contribution Batch
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('cpf-contributions.index') }}" class="text-muted text-hover-primary">Monthly
                    Contributions</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">{{ $batch->month_label }}</li>
        </ul>
    </div>
@endsection

@section('content')
    <!--begin::Summary card-->
    <div class="card mb-6">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2 class="fw-bold me-4">{{ $batch->month_label }}</h2>
                <span class="{{ $batch->status->badgeClass() }} fs-7">{{ $batch->status->label() }}</span>
            </div>
            <div class="card-toolbar">
                @if ($batch->isEditable() && $canEdit)
                    <button type="button" class="btn btn-light-warning me-3" data-batch-action="regenerate">
                        <i class="ki-outline ki-arrows-circle fs-3"></i>Regenerate
                    </button>
                @endif
                @if ($batch->canBeSubmitted() && $canSubmit)
                    <button type="button" class="btn btn-primary" data-batch-action="submit">
                        <i class="ki-outline ki-send fs-3"></i>Submit for Approval
                    </button>
                @endif
                @if ($batch->canBeApproved() && $canApprove)
                    <button type="button" class="btn btn-light-danger me-3" data-batch-action="reject">
                        <i class="ki-outline ki-arrow-circle-left fs-3"></i>Send Back
                    </button>
                    <button type="button" class="btn btn-success" data-batch-action="approve">
                        <i class="ki-outline ki-check-circle fs-3"></i>Approve &amp; Post Ledger
                    </button>
                @endif
                @if ($batch->canBeReversed() && $canReverse)
                    <button type="button" class="btn btn-light-danger" data-batch-action="reverse">
                        <i class="ki-outline ki-cross-circle fs-3"></i>Reverse Batch
                    </button>
                @endif
            </div>
        </div>

        <div class="card-body pt-0">
            <!--begin::Stat tiles-->
            <div class="d-flex flex-wrap">
                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                    <div class="fs-4 text-gray-800 fw-bold">{{ $batch->fiscal_year }}</div>
                    <div class="fw-semibold text-gray-500">Fiscal Year</div>
                </div>
                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                    <div class="fs-4 text-gray-800 fw-bold">{{ number_format($batch->employeeCount()) }}</div>
                    <div class="fw-semibold text-gray-500">Employees</div>
                </div>
                <div class="border border-gray-300 border-dashed rounded min-w-150px py-3 px-4 me-6 mb-3">
                    <div class="fs-4 text-gray-800 fw-bold" id="summary-employee-total">{{ number_format($empTotal) }}</div>
                    <div class="fw-semibold text-gray-500">Employee Contribution
                        ({{ rtrim(rtrim(number_format($empRate, 2), '0'), '.') }}%)</div>
                </div>
                <div class="border border-gray-300 border-dashed rounded min-w-150px py-3 px-4 me-6 mb-3">
                    <div class="fs-4 text-gray-800 fw-bold" id="summary-government-total">{{ number_format($govTotal) }}
                    </div>
                    <div class="fw-semibold text-gray-500">Govt. Contribution
                        ({{ rtrim(rtrim(number_format($govRate, 2), '0'), '.') }}%)</div>
                </div>
                <div class="border border-primary border-dashed rounded min-w-150px py-3 px-4 mb-3">
                    <div class="fs-4 text-primary fw-bold" id="summary-grand-total">{{ number_format($grandTotal) }}</div>
                    <div class="fw-semibold text-gray-500">Grand Total (Tk)</div>
                </div>
            </div>
            <!--end::Stat tiles-->

            <!--begin::Workflow trail-->
            @if ($batch->submitted_at || $batch->approved_at || $batch->reversed_at || $batch->remarks)
                <div class="separator separator-dashed my-5"></div>
                <div class="d-flex flex-column flex-wrap gap-2 fs-7 text-gray-600">
                    @if ($batch->submitted_at)
                        <div><i class="ki-outline ki-time fs-6 me-2 text-info"></i>
                            Submitted by <b>{{ $batch->submittedBy?->name ?? '—' }}</b> on
                            {{ $batch->submitted_at->format('d M Y, h:i A') }}</div>
                    @endif
                    @if ($batch->approved_at)
                        <div><i class="ki-outline ki-check-circle fs-6 me-2 text-success"></i>
                            Approved by <b>{{ $batch->approvedBy?->name ?? '—' }}</b> on
                            {{ $batch->approved_at->format('d M Y, h:i A') }}</div>
                    @endif
                    @if ($batch->reversed_at)
                        <div><i class="ki-outline ki-cross-circle fs-6 me-2 text-danger"></i>
                            Reversed by <b>{{ $batch->reversedBy?->name ?? '—' }}</b> on
                            {{ $batch->reversed_at->format('d M Y, h:i A') }}</div>
                    @endif
                    @if ($batch->remarks)
                        <div><i class="ki-outline ki-notepad fs-6 me-2 text-warning"></i>
                            Remarks: <span class="fst-italic">{{ $batch->remarks }}</span></div>
                    @endif
                </div>
            @endif
            <!--end::Workflow trail-->
        </div>
    </div>
    <!--end::Summary card-->

    <!--begin::Contributions card-->
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" data-contribution-rows-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search employees">
                </div>
                <div id="kt_hidden_export_buttons" class="d-none"></div>
            </div>
            <div class="card-toolbar">
                <div class="dropdown">
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-exit-up fs-2"></i>Export
                    </button>
                    <div id="kt_table_report_dropdown_menu"
                        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                        data-kt-menu="true">
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-row-export="copy">Copy to
                                clipboard</a></div>
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-row-export="excel">Export
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
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-4 ashik-table"
                id="bida_contribution_rows_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th>CPF A/C No.</th>
                        <th>Employee Name</th>
                        <th>Designation</th>
                        <th class="text-end">Basic Salary (Tk)</th>
                        <th class="text-end">Employee Contribution (Tk)</th>
                        <th class="text-end">Govt. Contribution (Tk)</th>
                        <th class="text-end">Total (Tk)</th>
                        <th>Remarks</th>
                        @if ($batch->isEditable() && $canEdit)
                            <th class="not-export text-end">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="text-gray-800 fw-semibold">
                    @foreach ($batch->contributions as $contribution)
                        @php $rowTotal = $contribution->totalContribution(); @endphp
                        <tr data-contribution-id="{{ $contribution->id }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <a href="{{ route('employees.show', $contribution->employee_id) }}" target="_blank"
                                    class="text-gray-800 text-hover-primary">
                                    {{ $contribution->employee?->cpf_account_no }}
                                </a>
                            </td>
                            <td class="contrib-name">{{ $contribution->employee?->name }}</td>
                            <td>{{ $contribution->employee?->designation }}</td>
                            <td class="text-end contrib-basic" data-order="{{ $contribution->basic_salary }}">
                                {{ number_format($contribution->basic_salary) }}</td>
                            <td class="text-end contrib-employee"
                                data-order="{{ $contribution->employee_contribution }}">
                                {{ number_format($contribution->employee_contribution) }}</td>
                            <td class="text-end contrib-government"
                                data-order="{{ $contribution->government_contribution }}">
                                {{ number_format($contribution->government_contribution) }}</td>
                            <td class="text-end fw-bold contrib-total" data-order="{{ $rowTotal }}">
                                {{ number_format($rowTotal) }}</td>
                            <td class="contrib-remarks text-muted">{{ $contribution->remarks }}</td>
                            @if ($batch->isEditable() && $canEdit)
                                <td class="text-end">
                                    <button type="button" title="Edit contribution"
                                        class="btn btn-icon text-hover-primary w-30px h-30px"
                                        data-contribution-edit="{{ $contribution->id }}">
                                        <i class="ki-outline ki-pencil fs-2"></i>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <!--end::Contributions card-->

    {{-- Hidden workflow forms (only rendered when the action is available) --}}
    @if ($batch->isEditable() && $canEdit)
        <form id="form-regenerate-batch" class="d-none" method="POST"
            action="{{ route('cpf-contributions.regenerate', $batch) }}">@csrf @method('PUT')</form>
    @endif
    @if ($batch->canBeSubmitted() && $canSubmit)
        <form id="form-submit-batch" class="d-none" method="POST"
            action="{{ route('cpf-contributions.submit', $batch) }}">@csrf @method('PUT')</form>
    @endif
    @if ($batch->canBeApproved() && $canApprove)
        <form id="form-approve-batch" class="d-none" method="POST"
            action="{{ route('cpf-contributions.approve', $batch) }}">@csrf @method('PUT')</form>
        <form id="form-reject-batch" class="d-none" method="POST"
            action="{{ route('cpf-contributions.reject', $batch) }}">
            @csrf @method('PUT')
            <input type="hidden" name="remarks" id="reject_remarks">
        </form>
    @endif
    @if ($batch->canBeReversed() && $canReverse)
        <form id="form-reverse-batch" class="d-none" method="POST"
            action="{{ route('cpf-contributions.reverse', $batch) }}">@csrf @method('PUT')</form>
    @endif

    @if ($batch->isEditable() && $canEdit)
        <!--begin::Edit contribution modal-->
        <div class="modal fade" id="kt_modal_edit_contribution" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-550px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold">Edit Contribution</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body py-8 px-lg-12">
                        <div class="mb-5">
                            <span class="text-muted fs-7">Employee</span>
                            <div class="fw-bold fs-5" id="edit_employee_name">—</div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="fs-7 fw-semibold mb-1">Basic Salary (Tk)</label>
                                <input type="number" min="0" class="form-control form-control-solid"
                                    id="edit_basic_salary">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="button" class="btn btn-light-primary w-100" id="edit_autocalc">
                                    <i class="ki-outline ki-calculator fs-3"></i>Auto-calc from salary
                                </button>
                            </div>
                            <div class="col-md-6">
                                <label class="fs-7 fw-semibold mb-1">Employee Contribution (Tk)</label>
                                <input type="number" min="0" class="form-control form-control-solid"
                                    id="edit_employee_contribution">
                            </div>
                            <div class="col-md-6">
                                <label class="fs-7 fw-semibold mb-1">Govt. Contribution (Tk)</label>
                                <input type="number" min="0" class="form-control form-control-solid"
                                    id="edit_government_contribution">
                            </div>
                            <div class="col-12">
                                <label class="fs-7 fw-semibold mb-1 required">Remarks</label>
                                <textarea class="form-control form-control-solid" rows="2" id="edit_remarks"
                                    placeholder="Required: reason for this adjustment"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="edit_contribution_save">
                            <span class="indicator-label">Save Changes</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Edit contribution modal-->
    @endif
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaContributionShowConfig = {
            csrf: "{{ csrf_token() }}",
            isEditable: @json($batch->isEditable() && $canEdit),
            hasActionsColumn: @json($batch->isEditable() && $canEdit),
            employeeRate: {{ $empRate }},
            governmentRate: {{ $govRate }},
            monthLabel: @json($batch->month_label),
            // Update URL with a replaceable placeholder for the contribution id.
            updateUrlTemplate: "{{ route('cpf-contributions.contributions.update', ['batch' => $batch->id, 'contribution' => '__CID__']) }}",
        };
    </script>
    <script src="{{ asset('js/cpf-contributions/show.js') }}"></script>
@endpush
