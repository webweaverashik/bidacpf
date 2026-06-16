@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/settings/employee-upload.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')
@section('title', 'Employee Upload')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Employee Upload
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">Settings</li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">Bulk Upload</li>
        </ul>
    </div>
@endsection

@section('content')
    @include('settings.partials.hero')

    <!--begin::Upload card-->
    <div class="card mb-6" id="upload_card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <span class="d-inline-flex align-items-center">
                    <i class="ki-outline ki-file-up fs-2x me-2 text-primary"></i>
                    <h3 class="fw-bold text-gray-900 fs-3 m-0">Bulk Employee Upload</h3>
                </span>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('employee-upload.template') }}" class="btn btn-light-primary">
                    <i class="bi bi-download fs-2"></i>
                    Download Template
                </a>
            </div>
        </div>

        <div class="card-body py-8">
            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-5 mb-8">
                <i class="ki-outline ki-information-5 fs-2tx text-warning me-4"></i>
                <div class="fw-semibold fs-7 text-gray-700">
                    Download the template and keep the header row unchanged. Pick the pay scale these employees
                    belong to — each row's <strong>grade</strong> and <strong>step</strong> are matched against it.
                    Dates use <code>YYYY-MM-DD</code>. Nothing is saved until you review the preview and confirm.
                    Maximum {{ \App\Services\Employee\EmployeeUploadService::MAX_ROWS }} rows per upload.
                </div>
            </div>

            <div class="row">
                <div class="col-md-5">
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Pay Scale</label>
                        <select id="pay_scale_id" class="form-select" data-control="select2"
                            data-placeholder="Select a pay scale" data-hide-search="false">
                            <option></option>
                            @foreach ($payScales as $ps)
                                <option value="{{ $ps->id }}">{{ $ps->name }} ({{ $ps->effective_year }})</option>
                            @endforeach
                        </select>
                        @if ($payScales->isEmpty())
                            <div class="form-text text-danger">
                                No active pay scales. Create one on the
                                <a href="{{ route('payscale.index') }}">Payscale</a> tab first.
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Spreadsheet (.xlsx, .xls, .csv)</label>
                        <input type="file" id="upload_file" class="form-control" accept=".xlsx,.xls,.csv">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-primary" id="btn_preview">
                    <span class="indicator-label">
                        <i class="ki-outline ki-eye fs-2"></i> Upload &amp; Preview
                    </span>
                    <span class="indicator-progress">Reading file&hellip;
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>
        </div>
    </div>
    <!--end::Upload card-->

    <!--begin::Preview card-->
    <div class="card d-none" id="preview_card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="fw-bold text-gray-900 fs-3 m-0">Preview &amp; Confirm</h3>
            </div>
            <div class="card-toolbar gap-2">
                <button type="button" class="btn btn-light" id="btn_reset">
                    <i class="ki-outline ki-arrow-circle-left fs-2"></i> Start Over
                </button>
                <button type="button" class="btn btn-success" id="btn_commit" disabled>
                    <span class="indicator-label">
                        <i class="ki-outline ki-check-circle fs-2"></i> Import Valid Rows
                    </span>
                    <span class="indicator-progress">Importing&hellip;
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>
        </div>

        <div class="card-body py-6">
            <!--begin::Summary-->
            <div class="row g-4 mb-6" id="preview_summary">
                <div class="col-4">
                    <div class="border border-gray-300 border-dashed rounded p-4 text-center">
                        <div class="fs-2 fw-bold text-gray-900" id="sum_total">0</div>
                        <div class="fw-semibold fs-7 text-gray-500">Total Rows</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border border-success border-dashed rounded p-4 text-center">
                        <div class="fs-2 fw-bold text-success" id="sum_valid">0</div>
                        <div class="fw-semibold fs-7 text-gray-500">Valid</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border border-danger border-dashed rounded p-4 text-center">
                        <div class="fs-2 fw-bold text-danger" id="sum_invalid">0</div>
                        <div class="fw-semibold fs-7 text-gray-500">Invalid (skipped)</div>
                    </div>
                </div>
            </div>
            <!--end::Summary-->

            <div class="table-responsive">
                <table class="table table-hover table-row-bordered align-middle gy-3 ashik-table" id="preview_table">
                    <thead>
                        <tr class="fw-bold fs-8 text-uppercase">
                            <th class="w-50px">Row</th>
                            <th class="min-w-130px">CPF Account</th>
                            <th class="min-w-150px">Name</th>
                            <th class="min-w-130px">Designation</th>
                            <th class="text-center w-80px">Grade / Step</th>
                            <th class="text-end w-120px">Basic Salary (৳)</th>
                            <th class="min-w-100px">Joining Date</th>
                            <th class="text-end w-100px">Opening Balance (৳)</th>
                            <th class="min-w-200px">Status</th>
                        </tr>
                    </thead>
                    <tbody id="preview_body" class="fw-semibold text-gray-700"></tbody>
                </table>
            </div>
        </div>
    </div>
    <!--end::Preview card-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaEmployeeUploadConfig = {
            previewUrl: "{{ route('employee-upload.preview') }}",
            commitUrl: "{{ route('employee-upload.commit') }}",
            employeesUrl: "{{ route('employees.index') }}",
            csrfToken: "{{ csrf_token() }}",
        };
    </script>
    <script src="{{ asset('js/settings/employee-upload.js') }}"></script>
    <script>
        var __bulkLink = document.getElementById("settings_bulk_admission_link");
        if (__bulkLink) __bulkLink.classList.add("active");
    </script>
@endpush
