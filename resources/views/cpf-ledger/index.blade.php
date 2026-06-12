@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/cpf-ledger/index.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'CPF Ledger')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">CPF Ledger</h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">CPF Operation</a>
            </li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">CPF Ledger</li>
        </ul>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" data-ledger-table-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search members">
                </div>
            </div>

            <div class="card-toolbar">
                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-duotone ki-filter fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>Filter</button>
                <div class="menu menu-sub menu-sub-dropdown w-300px" data-kt-menu="true">
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <div class="separator border-gray-200"></div>
                    <div class="px-7 py-5" data-ledger-table-filter="form">
                        <div class="mb-5">
                            <label class="form-label fs-6 fw-semibold">Status:</label>
                            <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                data-ledger-filter="status" data-placeholder="Select option" data-allow-clear="false"
                                data-hide-search="true">
                                <option></option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true" data-ledger-table-filter="reset">Reset</button>
                            <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                data-ledger-table-filter="filter">Apply</button>
                        </div>
                    </div>
                </div>

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
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-row-export="xlsx">Export
                                as Excel</a></div>
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-row-export="csv">Export as
                                CSV</a></div>
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-row-export="pdf">Export as
                                PDF</a></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body py-4">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table"
                id="bida_ledger_member_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th>CPF A/C No.</th>
                        <th>Employee Name</th>
                        <th>Designation</th>
                        <th>Pay Scale</th>
                        <th>Grade</th>
                        <th class="text-end">Basic Salary (Tk)</th>
                        <th class="text-end">Current Balance (Tk)</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaLedgerMembersConfig = {
            dataUrl: "{{ route('cpf-ledger.data') }}",
            exportUrl: "{{ route('cpf-ledger.export') }}",
        };
    </script>
    <script src="{{ asset('js/cpf-ledger/index.js') }}"></script>
@endpush
