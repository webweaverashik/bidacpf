@extends('layouts.app')

@section('title', 'Salary History of All Employees')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/employees/salary-history/index.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Salary History of All Employees
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('employees.index') }}" class="text-muted text-hover-primary">Employee Info</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Salary History</li>
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" data-salary-history-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search salary history">
                </div>
                <!--end::Search-->
            </div>
            <!--end::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Filter-->
                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-outline ki-filter fs-2"></i>Filter
                </button>
                <!--begin::Menu 1-->
                <div class="menu menu-sub menu-sub-dropdown w-350px" data-kt-menu="true">
                    <!--begin::Header-->
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Separator-->
                    <div class="separator border-gray-200"></div>
                    <!--end::Separator-->
                    <!--begin::Content-->
                    <div class="px-7 py-5" data-salary-history-filter="form">
                        <div class="row">
                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Change Type:</label>
                                <select class="form-select form-select-solid fw-bold"
                                    data-salary-history-filter="change_type" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true" data-hide-search="true"
                                    data-dropdown-parent="#kt_app_body">
                                    <option></option>
                                    <option value="initial">Initial</option>
                                    <option value="annual_increment">Annual Increment</option>
                                    <option value="promotion">Promotion</option>
                                    <option value="revision">Revision</option>
                                </select>
                            </div>

                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Pay Scale:</label>
                                <select class="form-select form-select-solid fw-bold"
                                    data-salary-history-filter="pay_scale_id" data-kt-select2="true"
                                    data-placeholder="Select pay scale" data-allow-clear="true"
                                    data-dropdown-parent="#kt_app_body">
                                    <option></option>
                                    @foreach ($payScales as $scale)
                                        <option value="{{ $scale->id }}">
                                            {{ $scale->name }}{{ $scale->is_active ? '' : ' (Inactive)' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Grade:</label>
                                <select class="form-select form-select-solid fw-bold" data-salary-history-filter="grade"
                                    data-kt-select2="true" data-placeholder="Select pay scale first" data-allow-clear="true"
                                    data-dropdown-parent="#kt_app_body" disabled>
                                    <option></option>
                                </select>
                            </div>

                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Basic Salary:</label>
                                <select class="form-select form-select-solid fw-bold"
                                    data-salary-history-filter="pay_scale_step_id" data-kt-select2="true"
                                    data-placeholder="Select grade first" data-allow-clear="true"
                                    data-dropdown-parent="#kt_app_body" disabled>
                                    <option></option>
                                </select>
                            </div>
                        </div>
                        <!--begin::Actions-->
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true" data-salary-history-filter="reset">Reset</button>
                            <button type="button" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                data-salary-history-filter="filter">Apply</button>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Menu 1-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table"
                id="bida_salary_history_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th class="w-250px">Employee Name<br>CPF A/C No.</th>
                        <th>Designation</th>
                        <th>Pay Scale</th>
                        <th>Grade / Step</th>
                        <th>Basic Salary (Tk)</th>
                        <th>Effective Date</th>
                        <th>Change Type</th>
                        <th>Remarks</th>
                        <th>Created By</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold"></tbody>
            </table>
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeSalaryHistoryData = "{{ route('employee-salary.data') }}";
        const routeSalaryHistoryGrades = "{{ route('employee-salary.filter.grades') }}";
        const routeSalaryHistorySteps = "{{ route('employee-salary.filter.steps') }}";
    </script>
    <script src="{{ asset('js/employees/salary-history/index.js') }}"></script>
@endpush
