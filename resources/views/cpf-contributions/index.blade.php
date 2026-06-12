@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/cpf-contributions/index.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Monthly Contributions')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">Monthly Contributions</h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">CPF Operation</a>
            </li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">Monthly Contributions</li>
        </ul>
    </div>
@endsection

@section('content')
    @php
        $statusOptions = \App\Enums\BatchStatus::options();
        $fiscalYears = $batches->pluck('fiscal_year')->unique()->sort()->values();
        $canViewUser = auth()->user()->can('user.view');
    @endphp

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" data-contributions-table-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search batches">
                </div>
                <div id="kt_hidden_export_buttons" class="d-none"></div>
            </div>

            <div class="card-toolbar">
                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-duotone ki-filter fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>Filter</button>
                <div class="menu menu-sub menu-sub-dropdown w-350px" data-kt-menu="true">
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <div class="separator border-gray-200"></div>
                    <div class="px-7 py-5" data-contributions-table-filter="form">
                        <div class="row">
                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Status:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="false" data-hide-search="true">
                                    <option></option>
                                    @foreach ($statusOptions as $value => $label)
                                        <option value="{{ $label }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Fiscal Year:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="false" data-hide-search="true">
                                    <option></option>
                                    @foreach ($fiscalYears as $fy)
                                        <option value="{{ $fy }}">{{ $fy }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true" data-contributions-table-filter="reset">Reset</button>
                            <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                data-contributions-table-filter="filter">Apply</button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <div class="dropdown">
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end">
                            <i class="ki-duotone ki-exit-up fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Export
                        </button>
                        <div id="kt_table_report_dropdown_menu"
                            class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                            data-kt-menu="true">
                            <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-row-export="copy">Copy
                                    to clipboard</a></div>
                            <div class="menu-item px-3"><a href="#" class="menu-link px-3"
                                    data-row-export="excel">Export as Excel</a></div>
                            <div class="menu-item px-3"><a href="#" class="menu-link px-3"
                                    data-row-export="csv">Export as CSV</a></div>
                            <div class="menu-item px-3"><a href="#" class="menu-link px-3"
                                    data-row-export="pdf">Export as PDF</a></div>
                        </div>
                    </div>

                    @can('cpf_contribution.create')
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_generate_batch">
                            <i class="ki-duotone ki-plus fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Generate Batch
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card-body py-4">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table"
                id="bida_contribution_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th>Month</th>
                        <th>Fiscal Year</th>
                        <th>Employees</th>
                        <th>Total Contribution (Tk)</th>
                        <th>Status</th>
                        <th>Generated On</th>
                        <th>Created By</th>
                        <th class="not-export text-end">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 fw-semibold">
                    @foreach ($batches as $batch)
                        @php $total = (int) $batch->employee_total + (int) $batch->government_total; @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <a href="{{ route('cpf-contributions.show', $batch->id) }}"
                                    class="text-gray-800 text-hover-primary fw-bold">{{ $batch->month_label }}</a>
                            </td>
                            <td>{{ $batch->fiscal_year }}</td>
                            <td data-order="{{ $batch->contributions_count }}">
                                {{ number_format($batch->contributions_count) }}</td>
                            <td data-order="{{ $total }}">{{ number_format($total) }}</td>
                            <td><span class="{{ $batch->status->badgeClass() }}">{{ $batch->status->label() }}</span>
                            </td>
                            <td data-order="{{ $batch->created_at?->timestamp }}">
                                {{ $batch->created_at?->format('d M Y, h:i A') }}
                            </td>
                            <td>
                                @if ($batch->creator)
                                    @if ($canViewUser)
                                        <a href="{{ route('users.show', $batch->creator->id) }}"
                                            class="text-gray-800 text-hover-primary">{{ $batch->creator->name }}</a>
                                    @else
                                        {{ $batch->creator->name }}
                                    @endif
                                @else
                                    <span class="text-muted">System</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('cpf-contributions.show', $batch->id) }}" title="View / Preview Batch"
                                    class="btn btn-icon text-hover-primary w-30px h-30px">
                                    <i class="ki-outline ki-eye fs-2"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @can('cpf_contribution.create')
        <!--begin::Generate Batch Modal (AJAX, current month only)-->
        <div class="modal fade" id="kt_modal_generate_batch" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-500px">
                <div class="modal-content">
                    <form id="kt_generate_batch_form">
                        <div class="modal-header">
                            <h2 class="fw-bold">Generate Contribution Batch</h2>
                            <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                                <i class="ki-duotone ki-cross fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>

                        <div class="modal-body py-10 px-lg-17">
                            <div class="text-muted fs-7 mb-5">
                                A draft batch will be created for every active employee using their current basic
                                salary and the configured contribution rates. You can review and edit it before
                                submitting for approval.
                            </div>
                            <div class="d-flex align-items-center bg-light-primary rounded p-4">
                                <i class="ki-duotone ki-calendar-8 fs-2x text-primary me-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                    <span class="path6"></span>
                                </i>
                                <div>
                                    <div class="fs-5 fw-bold text-gray-900">{{ now()->format('F Y') }}</div>
                                    <div class="text-muted fs-7">Only the current month can be generated manually.</div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer flex-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="kt_generate_batch_submit">
                                <span class="indicator-label">
                                    <i class="ki-outline ki-plus fs-3"></i>Generate {{ now()->format('M Y') }} Batch
                                </span>
                                <span class="indicator-progress">Generating, please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--end::Generate Batch Modal-->
    @endcan
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaContributionListConfig = {
            storeUrl: "{{ route('cpf-contributions.store') }}",
            csrf: "{{ csrf_token() }}",
            currentMonth: "{{ now()->startOfMonth()->format('Y-m-d') }}",
        };
    </script>
    <script src="{{ asset('js/cpf-contributions/index.js') }}"></script>
@endpush
