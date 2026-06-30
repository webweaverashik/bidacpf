@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/employees/index.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'All Employees')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Employees
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">Employee Info</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">All Employees</li>
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    <!--begin::Tabs nav-->
    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6 fw-semibold" id="kt_employee_tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active d-flex align-items-center" data-bs-toggle="tab" href="#kt_employee_tab_active"
                role="tab">
                <i class="ki-outline ki-people fs-4 me-2"></i>Active Service
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link d-flex align-items-center" data-bs-toggle="tab" href="#kt_employee_tab_others"
                role="tab">
                <i class="ki-outline ki-exit-right-corner fs-4 me-2"></i>Others
            </a>
        </li>
    </ul>
    <!--end::Tabs nav-->

    <!--begin::Tabs content-->
    <div class="tab-content" id="kt_employee_tab_content">

        {{-- ============================================================= --}}
        {{-- ACTIVE SERVICE TAB --}}
        {{-- ============================================================= --}}
        <div class="tab-pane fade show active" id="kt_employee_tab_active" role="tabpanel">
            <!--begin::Card-->
            <div class="card" data-emp-card="active">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" data-emp-filter="search"
                                class="form-control form-control-solid w-md-350px ps-12"
                                placeholder="Search active employees">
                        </div>
                        <!--end::Search-->
                    </div>
                    <!--end::Card title-->

                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Filter-->
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end">
                            <i class="ki-duotone ki-filter fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Filter
                        </button>
                        <!--begin::Menu-->
                        <div class="menu menu-sub menu-sub-dropdown w-350px" data-kt-menu="true">
                            <div class="px-7 py-5">
                                <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                            </div>
                            <div class="separator border-gray-200"></div>
                            <div class="px-7 py-5" data-emp-filter="form">
                                <div class="row">
                                    <div class="col-12 mb-5">
                                        <label class="form-label fs-6 fw-semibold">Employee Grade:</label>
                                        <select class="form-select form-select-solid fw-bold" data-emp-filter="grade"
                                            data-kt-select2="true" data-placeholder="Select grade" data-allow-clear="true"
                                            data-hide-search="true" data-dropdown-parent="#kt_app_body">
                                            <option></option>
                                            @foreach (range(1, 20) as $grade)
                                                <option value="{{ $grade }}">Grade {{ $grade }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 mb-5">
                                        <label class="form-label fs-6 fw-semibold">Activation:</label>
                                        <select class="form-select form-select-solid fw-bold"
                                            data-emp-filter="active_status" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true" data-hide-search="true"
                                            data-dropdown-parent="#kt_app_body">
                                            <option></option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="reset"
                                        class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                        data-kt-menu-dismiss="true" data-emp-filter="reset">Reset</button>
                                    <button type="button" class="btn btn-primary fw-semibold px-6"
                                        data-kt-menu-dismiss="true" data-emp-filter="apply">Apply</button>
                                </div>
                            </div>
                        </div>
                        <!--end::Menu-->

                        <!--begin::Export dropdown-->
                        <div class="dropdown d-inline-block me-3">
                            <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-exit-up fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>Export
                            </button>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                                data-kt-menu="true">
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3" data-row-export="xlsx">Export as Excel</a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3" data-row-export="csv">Export as CSV</a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3" data-row-export="pdf">Export as PDF</a>
                                </div>
                            </div>
                        </div>
                        <!--end::Export dropdown-->

                        @can('employee.create')
                            <a href="{{ route('employees.create') }}" class="btn btn-primary">
                                <i class="ki-outline ki-plus fs-2"></i>New Employee</a>
                        @endcan
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body py-4">
                    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table"
                        id="bida_employee_active_table">
                        <thead>
                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-25px">#</th>
                                <th>CPF A/C No.</th>
                                <th>Employee Name</th>
                                <th>Designation</th>
                                <th>Mobile</th>
                                <th>Joining Date</th>
                                <th>Grade</th>
                                <th class="text-end">Basic Salary (Tk)</th>
                                <th class="text-end">Current Balance (Tk)</th>
                                <th>Activation</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800 fw-semibold"></tbody>
                    </table>
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>

        {{-- ============================================================= --}}
        {{-- OTHERS TAB (retired / resigned / deceased) --}}
        {{-- ============================================================= --}}
        <div class="tab-pane fade" id="kt_employee_tab_others" role="tabpanel">
            <!--begin::Card-->
            <div class="card" data-emp-card="others">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" data-emp-filter="search"
                                class="form-control form-control-solid w-md-350px ps-12"
                                placeholder="Search former employees">
                        </div>
                        <!--end::Search-->
                    </div>
                    <!--end::Card title-->

                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Filter-->
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end">
                            <i class="ki-duotone ki-filter fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Filter
                        </button>
                        <!--begin::Menu-->
                        <div class="menu menu-sub menu-sub-dropdown w-350px" data-kt-menu="true">
                            <div class="px-7 py-5">
                                <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                            </div>
                            <div class="separator border-gray-200"></div>
                            <div class="px-7 py-5" data-emp-filter="form">
                                <div class="row">
                                    <div class="col-12 mb-5">
                                        <label class="form-label fs-6 fw-semibold">Employee Grade:</label>
                                        <select class="form-select form-select-solid fw-bold" data-emp-filter="grade"
                                            data-kt-select2="true" data-placeholder="Select grade"
                                            data-allow-clear="true" data-hide-search="true"
                                            data-dropdown-parent="#kt_app_body">
                                            <option></option>
                                            @foreach (range(1, 20) as $grade)
                                                <option value="{{ $grade }}">Grade {{ $grade }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 mb-5">
                                        <label class="form-label fs-6 fw-semibold">Service Status:</label>
                                        <select class="form-select form-select-solid fw-bold"
                                            data-emp-filter="service_status" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-hide-search="true" data-dropdown-parent="#kt_app_body">
                                            <option></option>
                                            <option value="retired">Retired</option>
                                            <option value="resigned">Resigned</option>
                                            <option value="deceased">Deceased</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="reset"
                                        class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                        data-kt-menu-dismiss="true" data-emp-filter="reset">Reset</button>
                                    <button type="button" class="btn btn-primary fw-semibold px-6"
                                        data-kt-menu-dismiss="true" data-emp-filter="apply">Apply</button>
                                </div>
                            </div>
                        </div>
                        <!--end::Menu-->

                        <!--begin::Export dropdown-->
                        <div class="dropdown d-inline-block me-3">
                            <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-exit-up fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>Export
                            </button>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                                data-kt-menu="true">
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3" data-row-export="xlsx">Export as Excel</a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3" data-row-export="csv">Export as CSV</a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3" data-row-export="pdf">Export as PDF</a>
                                </div>
                            </div>
                        </div>
                        <!--end::Export dropdown-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body py-4">
                    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table"
                        id="bida_employee_others_table">
                        <thead>
                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-25px">#</th>
                                <th>CPF A/C No.</th>
                                <th>Employee Name</th>
                                <th>Designation</th>
                                <th>Mobile</th>
                                <th>Joining Date</th>
                                <th>Grade</th>
                                <th class="text-end">Basic Salary (Tk)</th>
                                <th class="text-end">Current Balance (Tk)</th>
                                <th>Service Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800 fw-semibold"></tbody>
                    </table>
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>

    </div>
    <!--end::Tabs content-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeEmployeesData = "{{ route('employees.data') }}";
        const routeEmployeesExport = "{{ route('employees.export') }}";
    </script>
    <script src="{{ asset('js/employees/index.js') }}"></script>
@endpush
