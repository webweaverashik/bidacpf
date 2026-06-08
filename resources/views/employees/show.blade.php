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
        $canDeactivate  = auth()->user()->can('employee.deactivate');
        $canEdit        = auth()->user()->can('employee.edit');
        $canDelete      = auth()->user()->can('employee.delete');
    @endphp

    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" data-kt-employees-list-table-filter="search"
                        class="form-control form-control-solid w-350px ps-12" placeholder="Search Employees">
                </div>
                <!--end::Search-->
            </div>
            <!--begin::Card title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-subscription-table-toolbar="base">
                    <!--begin::Filter-->
                    <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-filter fs-2"></i>Filter</button>
                    <!--begin::Menu 1-->
                    <div class="menu menu-sub menu-sub-dropdown w-350px w-md-500px" data-kt-menu="true">
                        <!--begin::Header-->
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                        </div>
                        <!--end::Header-->
                        <!--begin::Separator-->
                        <div class="separator border-gray-200"></div>
                        <!--end::Separator-->
                        <!--begin::Content-->
                        <div class="px-7 py-5" data-kt-employees-list-table-filter="form">
                            <div class="row">
                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Employee Gender:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true" data-filter-field="gender"
                                        data-hide-search="true">
                                        <option></option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Employee Status:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true" data-filter-field="status"
                                        data-hide-search="true">
                                        <option></option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Payment Type:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-filter-field="payment_type" data-hide-search="true">
                                        <option></option>
                                        <option value="due">Due</option>
                                        <option value="current">Current</option>
                                    </select>
                                </div>
                                <!--begin::Input group-->
                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Due Date:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-filter-field="due_date" data-hide-search="true">
                                        <option></option>
                                        <option value="7">1-7</option>
                                        <option value="10">1-10</option>
                                        <option value="15">1-15</option>
                                        <option value="30">1-30</option>
                                    </select>
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Batches:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-filter-field="batch_id" data-hide-search="true">
                                        <option></option>
                                        {{-- @foreach ($batches as $batch)
                                            <option value="{{ $batch->id }}">
                                                {{ $batch->name }}
                                                @if ($isAdmin)
                                                    ({{ $batch->branch_name }})
                                                @endif
                                            </option>
                                        @endforeach --}}
                                    </select>
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Academic Group:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-filter-field="academic_group" data-hide-search="true">
                                        <option></option>
                                        <option value="Science">Science</option>
                                        <option value="Commerce">Commerce</option>
                                        <option value="Arts">Arts</option>
                                    </select>
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="col-12 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Class</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-filter-field="class_id">
                                        <option></option>
                                        {{-- @foreach ($classnames as $classname)
                                            <option value="{{ $classname->id }}">
                                                {{ $classname->name }}
                                            </option>
                                        @endforeach --}}
                                    </select>
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="col-12 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Institutions</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-filter-field="institution">
                                        <option></option>
                                        {{-- @foreach ($institutions as $institution)
                                            <option value="{{ $institution->name }}">
                                                {{ $institution->name }}
                                            </option>
                                        @endforeach --}}
                                    </select>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset"
                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-kt-employees-list-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6"
                                    data-kt-menu-dismiss="true"
                                    data-kt-employees-list-table-filter="filter">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Menu 1-->
                    @can('employee.create')
                        <!--begin::Add Employee-->
                        <a href="{{ route('employees.create') }}" class="btn btn-primary">
                            <i class="ki-outline ki-plus fs-2"></i>New Employee</a>
                        <!--end::Add Employee-->
                    @endcan
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body py-4">
            {{-- 
    Employees Table Partial - AJAX version
    
    IMPORTANT: Column order must match exactly with JavaScript DataTable columns array (19 columns)
    Index: 0=#, 1=Employee, 2=Class, 3=Group, 4=Batch, 5=Institution,
           6=Mobile(Home), 7=Mobile(SMS), 8=Mobile(WhatsApp),
           9=Guardian1, 10=Guardian2, 11=Sibling1, 12=Sibling2,
           13=TuitionFee, 14=PaymentType, 15=Status,
           16=AdmissionDate, 17=AdmittedBy, 18=Actions
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
            <div class="employees-table-wrapper" style="opacity:0; height:0; overflow:hidden;">
                <table class="table table-hover table-row-dashed align-middle fs-6 gy-5 ashik-table">
                    <thead>
                        <tr class="fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-25px">#</th>
                            <th class="min-w-200px">CPF A/C No.</th>
                            <th class="min-w-150px">Employee Name</th>
                            <th class="min-w-80px">Designation</th>
                            <th class="min-w-100px">Email</th>
                            <th class="min-w-80px">Mobile</th>
                            <th class="min-w-100px">Joining Date</th>
                            <th class="min-w-100px">Grade</th>
                            <th class="min-w-100px">Basic Salary</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-70px text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        {{-- Data loaded via AJAX --}}
                    </tbody>
                </table>
            </div>
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
