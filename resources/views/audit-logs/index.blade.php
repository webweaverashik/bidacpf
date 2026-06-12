@extends('layouts.app')

@section('title', 'Audit Logs')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/audit-logs/index.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Audit Logs
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">System</li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Audit Logs</li>
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
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" data-audit-logs-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search audit logs">
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
                    <div class="px-7 py-5" data-audit-logs-filter="form">
                        <div class="row">
                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Event:</label>
                                <select class="form-select form-select-solid fw-bold" data-audit-logs-filter="event"
                                    data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true"
                                    data-hide-search="true" data-dropdown-parent="#kt_app_body">
                                    <option></option>
                                    @foreach ($events as $event)
                                        <option value="{{ $event }}">{{ Str::headline($event) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Log Name:</label>
                                <select class="form-select form-select-solid fw-bold" data-audit-logs-filter="log_name"
                                    data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true"
                                    data-dropdown-parent="#kt_app_body">
                                    <option></option>
                                    @foreach ($logNames as $logName)
                                        <option value="{{ $logName }}">{{ Str::headline($logName) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Subject:</label>
                                <select class="form-select form-select-solid fw-bold" data-audit-logs-filter="subject_type"
                                    data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true"
                                    data-dropdown-parent="#kt_app_body">
                                    <option></option>
                                    @foreach ($subjectTypes as $type)
                                        <option value="{{ $type }}">{{ class_basename($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <!--begin::Actions-->
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true" data-audit-logs-filter="reset">Reset</button>
                            <button type="button" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                data-audit-logs-filter="filter">Apply</button>
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
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table" id="bida_audit_logs_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th>Description</th>
                        <th>Event</th>
                        <th>Log Name</th>
                        <th>Subject</th>
                        <th class="min-w-200px">Changes</th>
                        <th>Causer</th>
                        <th>When</th>
                        <th class="text-end">Actions</th>
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
        const routeAuditLogsData = "{{ route('audit-logs.data') }}";
    </script>
    <script src="{{ asset('js/audit-logs/index.js') }}"></script>
@endpush
