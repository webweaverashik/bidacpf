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
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">
                    Employee Info
                </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                All Employees
            </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    @php
        // Preloading permissions checking
        $canDeactivate = auth()->user()->can('employee.deactivate');
        $canEdit = auth()->user()->can('employee.update');
        $canDelete = auth()->user()->can('employee.delete');
    @endphp

    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                        data-employees-table-filter="search" class="form-control form-control-solid w-md-350px ps-12"
                        placeholder="Search in employees">
                </div>
                <!--end::Search-->

                <!--begin::Export hidden buttons-->
                <div id="kt_hidden_export_buttons" class="d-none"></div>
                <!--end::Export buttons-->

            </div>
            <!--end::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Filter-->
                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-outline ki-filter fs-2"></i>Filter</button>
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
                    <div class="px-7 py-5" data-employees-table-filter="form">
                        <div class="row">
                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Employee Grade:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="false" data-hide-search="true">
                                    <option></option>
                                    @foreach (range(1, 20) as $grade)
                                        <option value="grade_{{ $grade }}">{{ $grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Employee Status:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="false" data-hide-search="true">
                                    <option></option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <!--begin::Actions-->
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true" data-employees-table-filter="reset">Reset</button>
                            <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                data-employees-table-filter="filter">Apply</button>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Menu 1-->

                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-employees-table-filter="base">
                    <!--begin::Export dropdown-->
                    <div class="dropdown">
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end">
                            <i class="ki-outline ki-exit-up fs-2"></i>Export
                        </button>

                        <!--begin::Menu-->
                        <div id="kt_table_report_dropdown_menu"
                            class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                            data-kt-menu="true">
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="copy">Copy to
                                    clipboard</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="excel">Export as Excel</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="csv">Export as CSV</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="pdf">Export as PDF</a>
                            </div>
                            <!--end::Menu item-->
                        </div>
                        <!--end::Menu-->
                    </div>
                    <!--end::Export dropdown-->

                    @can('employee.create')
                        <!--begin::Add Employee-->
                        <a href="{{ route('employees.create') }}" class="btn btn-primary">
                            <i class="ki-outline ki-plus fs-2"></i>New Employee</a>
                        <!--end::Add Employee-->
                    @endcan
                    <!--end::Filter-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body py-4">
            {{-- 
            Employees Table - AJAX version
            --}}

            {{-- Skeleton preloader - shown while DataTable initializes --}}
            {{-- <div class="employees-skeleton" id="skeleton_{{ $tableId }}">
                <div class="skeleton-header">
                    <div class="skeleton-bar" style="width:3%"></div>
                    <div class="skeleton-bar" style="width:18%"></div>
                    <div class="skeleton-bar" style="width:8%"></div>
                    <div class="skeleton-bar" style="width:7%"></div>
                    <div class="skeleton-bar" style="width:9%"></div>
                    <div class="skeleton-bar" style="width:15%"></div>
                    <div class="skeleton-bar" style="width:10%"></div>
                    <div class="skeleton-bar" style="width:9%"></div>
                    <div class="skeleton-bar" style="width:10%"></div>
                    <div class="skeleton-bar" style="width:6%"></div>
                </div>
                @for ($i = 0; $i < 8; $i++)
                    <div class="skeleton-row">
                        <div class="skeleton-cell" style="width:3%"></div>
                        <div class="skeleton-cell" style="width:18%"></div>
                        <div class="skeleton-cell" style="width:8%"></div>
                        <div class="skeleton-cell" style="width:7%"></div>
                        <div class="skeleton-cell" style="width:9%"></div>
                        <div class="skeleton-cell" style="width:15%"></div>
                        <div class="skeleton-cell" style="width:10%"></div>
                        <div class="skeleton-cell" style="width:9%"></div>
                        <div class="skeleton-cell" style="width:10%"></div>
                        <div class="skeleton-cell" style="width:6%"></div>
                    </div>
                @endfor
            </div> --}}

            {{-- Actual DataTable - hidden until ready --}}
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table" id="bida_employee_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th>CPF A/C No.</th>
                        <th>Employee Name</th>
                        <th>Designation</th>
                        <th>Mobile</th>
                        <th>Joining Date</th>
                        <th>Grade</th>
                        <th class="d-none">Grade (filter)</th>
                        <th>Basic Salary (Tk)</th>
                        <th>Current Balance (Tk)</th>
                        <th>Status</th>
                        <th class="not-export">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @foreach ($employees as $employee)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <a href="{{ route('employees.show', $employee->id) }}"
                                    class="text-gray-600 text-hover-primary" title="View Employee Details"
                                    target="_blank">
                                    {{ $employee->cpf_account_no }}
                                </a>
                            </td>
                            <td>{{ $employee->name }}</td>
                            <td>{{ $employee->designation }}</td>
                            <td>{{ $employee->mobile_number }}</td>
                            <td>{{ $employee->joining_date?->format('d M Y') }}</td>
                            <td>{{ $employee->grade }}</td>
                            <td class="d-none">grade_{{ $employee->grade }}</td>
                            <td>{{ $employee->current_basic_salary }}</td>
                            <td>{{ $employee->currentBalance() }}</td>
                            <td>
                                @if ($employee->is_active)
                                    <span class="badge badge-light-success">Active</span>
                                @else
                                    <span class="badge badge-light-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('employees.show', $employee->id) }}" target="_blank"
                                    title="View Employee" class="btn btn-icon text-hover-primary w-30px h-30px">
                                    <i class="ki-outline ki-eye fs-2"></i>
                                </a>
                                <a href="{{ route('employees.edit', $employee->id) }}" title="Edit Employee"
                                    class="btn btn-icon text-hover-primary w-30px h-30px">
                                    <i class="ki-outline ki-pencil fs-2"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->

    <!--begin::Modal - Toggle Activation Employee-->
    <div class="modal fade" id="kt_toggle_activation_employee_modal" tabindex="-1" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 id="toggle-activation-modal-title">Activation/Deactivation Employee</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body py-lg-5">
                    <!--begin::Content-->
                    <div class="flex-row-fluid p-lg-5">
                        {{-- <form action="{{ route('employees.toggleActive') }}" class="form d-flex flex-column" --}}
                        <form action="#" class="form d-flex flex-column" method="POST"
                            id="kt_toggle_activation_form">
                            @csrf
                            <!--begin::Left column-->
                            <div class="d-flex flex-column">
                                <input type="hidden" name="employee_id" id="employee_id" />
                                <input type="hidden" name="active_status" id="activation_status" />
                                <div class="row">
                                    <div class="col-lg-12">
                                        <!--begin::Input group-->
                                        <div class="d-flex flex-column mb-5 fv-row">
                                            <!--begin::Label-->
                                            <label class="fs-5 fw-semibold mb-2 required"
                                                id="reason_label">Activation/Deactivation Reason</label>
                                            <!--end::Label-->
                                            <!--begin::Input-->
                                            <textarea class="form-control" rows="3" name="reason" id="activation_reason"
                                                placeholder="Write the reason for this update" required minlength="3"></textarea>
                                            <!--end::Input-->
                                            <div class="fv-plugins-message-container invalid-feedback" id="reason_error">
                                            </div>
                                        </div>
                                        <!--end::Input group-->
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <!--begin::Button-->
                                    <button type="button" class="btn btn-secondary me-5"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <!--end::Button-->
                                    <!--begin::Button-->
                                    <button type="submit" class="btn btn-primary" id="kt_toggle_activation_submit">
                                        <span class="indicator-label">Submit</span>
                                        <span class="indicator-progress">Please wait...
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                    <!--end::Button-->
                                </div>
                            </div>
                            <!--end::Left column-->
                        </form>
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Toggle Activation Employee-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeDeleteEmployee = "{{ route('employees.destroy', ':id') }}";
        const routeEmployeeShow = "{{ route('employees.show', ':id') }}";
        const routeToggleActive = "{{ route('employees.toggleActive') }}";
    </script>
    <script src="{{ asset('js/employees/index.js') }}"></script>
@endpush
