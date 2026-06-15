@push('page-css')
    <link href="{{ asset('css/settings/payscale.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')
@section('title', 'Upload Pay Scale')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">Upload Pay Scale</h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">Settings</li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item">
                <a href="{{ route('payscale.index') }}" class="text-muted text-hover-primary">Pay Scales</a>
            </li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">Upload</li>
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
                    <h3 class="fw-bold text-gray-900 fs-3 m-0">New Pay Scale</h3>
                </span>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('payscale.template') }}" class="btn btn-light-primary">
                    <i class="bi bi-download fs-2"></i> Download Template
                </a>
            </div>
        </div>

        <div class="card-body py-8">
            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-5 mb-8">
                <i class="ki-outline ki-information-5 fs-2tx text-warning me-4"></i>
                <div class="fw-semibold fs-7 text-gray-700">
                    The spreadsheet header must read <code>grade, step-1, step-2, … step-20</code>, one row per grade
                    with a basic salary in each step the grade has (leave the rest blank). The whole file must be
                    valid to create the pay scale. Nothing is saved until you review the preview and confirm.
                </div>
            </div>

            <form id="payscale_form" class="form" novalidate="novalidate">
                <div class="row">
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Name</label>
                            <input type="text" name="name" class="form-control"
                                placeholder="e.g. National Pay Scale 2015" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Effective Year</label>
                            <input type="number" name="effective_year" class="form-control" min="1900"
                                max="{{ now()->year + 5 }}" value="{{ now()->year }}" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2 d-block">Activate on create</label>
                            <label class="form-check form-switch form-check-custom form-check-solid mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" />
                                <span class="form-check-label fw-semibold text-gray-600">Set as the active scale</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Effective From</label>
                            <input type="text" name="effective_from" class="form-control js-flatpickr"
                                placeholder="Select date" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Effective To</label>
                            <input type="text" name="effective_to" class="form-control js-flatpickr"
                                placeholder="Optional" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Spreadsheet (.xlsx, .xls, .csv)</label>
                            <input type="file" name="file" id="upload_file" class="form-control"
                                accept=".xlsx,.xls,.csv" />
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" id="btn_preview">
                        <span class="indicator-label"><i class="ki-outline ki-eye fs-2"></i> Upload &amp; Preview</span>
                        <span class="indicator-progress">Reading file&hellip;
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
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
                    <span class="indicator-label"><i class="ki-outline ki-check-circle fs-2"></i> Create Pay Scale</span>
                    <span class="indicator-progress">Creating&hellip;
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>
        </div>

        <div class="card-body py-6">
            <!--begin::Summary-->
            <div class="row g-4 mb-6">
                <div class="col-4">
                    <div class="border border-gray-300 border-dashed rounded p-4 text-center">
                        <div class="fs-2 fw-bold text-gray-900" id="sum_grades">0</div>
                        <div class="fw-semibold fs-7 text-gray-500">Grades</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border border-info border-dashed rounded p-4 text-center">
                        <div class="fs-2 fw-bold text-info" id="sum_steps">0</div>
                        <div class="fw-semibold fs-7 text-gray-500">Total Steps</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border border-danger border-dashed rounded p-4 text-center">
                        <div class="fs-2 fw-bold text-danger" id="sum_invalid">0</div>
                        <div class="fw-semibold fs-7 text-gray-500">Invalid Grades</div>
                    </div>
                </div>
            </div>
            <!--end::Summary-->

            <div class="notice d-none align-items-center bg-light-danger rounded border-danger border border-dashed p-4 mb-6"
                id="invalid_note">
                <i class="ki-outline ki-information-5 fs-2tx text-danger me-3"></i>
                <div class="fw-semibold fs-7 text-gray-700">
                    Some grades have errors (highlighted below). Fix the file and upload again — the pay scale can
                    only be created when every grade is valid.
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-row-bordered align-middle text-nowrap gy-2 ashik-table"
                    id="grid_table">
                    <thead id="grid_head"></thead>
                    <tbody id="grid_body" class="fw-semibold text-gray-800"></tbody>
                </table>
            </div>
        </div>
    </div>
    <!--end::Preview card-->
@endsection

@push('page-js')
    <script>
        var BidaPayScaleUploadConfig = {
            previewUrl: "{{ route('payscale.preview') }}",
            commitUrl: "{{ route('payscale.store') }}",
            indexUrl: "{{ route('payscale.index') }}",
            csrfToken: "{{ csrf_token() }}",
        };
    </script>
    <script src="{{ asset('js/settings/payscale-upload.js') }}"></script>
    <script>
        document.getElementById("settings_payscale_link").classList.add("active");
    </script>
@endpush
