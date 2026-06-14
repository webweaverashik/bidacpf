@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')
@section('title', 'Scheduled Tasks')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Scheduled Tasks
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">Reports</li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">Scheduled Tasks</li>
        </ul>
    </div>
@endsection

@section('content')
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                    <input type="text" id="task_search" class="form-control form-control-solid w-250px ps-12"
                        placeholder="Search command / output" />
                </div>
            </div>
            <div class="card-toolbar">
                <!--begin::Filter-->
                <button type="button" class="btn btn-light-primary me-2" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-outline ki-filter fs-2"></i> Filter
                </button>
                <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <div class="separator border-gray-200"></div>
                    <div class="px-7 py-5">
                        <div class="mb-5">
                            <label class="form-label fw-semibold">Status</label>
                            <select id="filter_status" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true" data-placeholder="All">
                                <option value="">All</option>
                                @foreach ($statuses as $s)
                                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label fw-semibold">Task</label>
                            <select id="filter_command" class="form-select form-select-solid" data-control="select2"
                                data-placeholder="All">
                                <option value="">All</option>
                                @foreach ($commands as $command => $label)
                                    <option value="{{ $command }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row mb-5">
                            <div class="col-6">
                                <label class="form-label fw-semibold">From</label>
                                <input type="text" id="filter_from" class="form-control form-control-solid js-fp"
                                    placeholder="Start date" />
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">To</label>
                                <input type="text" id="filter_to" class="form-control form-control-solid js-fp"
                                    placeholder="End date" />
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-light btn-sm me-2" id="filter_reset">Reset</button>
                            <button type="button" class="btn btn-primary btn-sm" id="filter_apply">Apply</button>
                        </div>
                    </div>
                </div>
                <!--end::Filter-->
            </div>
        </div>
        <!--end::Card header-->

        <div class="card-body py-4">
            <table class="table table-hover table-row-dashed align-middle fs-6 gy-5 ashik-table" id="tasks_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-50px">#</th>
                        <th class="min-w-200px">Task</th>
                        <th class="w-110px">Status</th>
                        <th class="text-center w-80px">Exit</th>
                        <th class="w-100px">Runtime</th>
                        <th class="min-w-150px">Started</th>
                        <th class="min-w-150px">Finished</th>
                        <th class="text-end w-80px">Details</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!--begin::Detail modal-->
    <div class="modal fade" id="task_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Task Run Details</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-8">
                            <div class="text-muted fs-8 text-uppercase">Task</div>
                            <div class="fw-bold" id="d_task">—</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted fs-8 text-uppercase">Status</div>
                            <div id="d_status">—</div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="text-muted fs-8 text-uppercase">Command</div>
                            <code id="d_command" class="fs-8">—</code>
                        </div>
                    </div>
                    <div class="row mb-4" id="d_description_row">
                        <div class="col-12">
                            <div class="text-muted fs-8 text-uppercase">Description</div>
                            <div id="d_description">—</div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-4">
                            <div class="text-muted fs-8 text-uppercase">Exit Code</div>
                            <div class="fw-bold" id="d_exit">—</div>
                        </div>
                        <div class="col-8">
                            <div class="text-muted fs-8 text-uppercase">Runtime</div>
                            <div class="fw-bold" id="d_runtime">—</div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="text-muted fs-8 text-uppercase">Started</div>
                            <div class="fw-bold" id="d_started">—</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted fs-8 text-uppercase">Finished</div>
                            <div class="fw-bold" id="d_finished">—</div>
                        </div>
                    </div>
                    <div id="d_output_row">
                        <div class="text-muted fs-8 text-uppercase mb-1">Output</div>
                        <pre class="bg-light rounded p-4 fs-8 mb-0" id="d_output"
                            style="max-height:260px;overflow:auto;white-space:pre-wrap;">—</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--end::Detail modal-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaScheduledTasksConfig = {
            dataUrl: "{{ route('scheduled-tasks.data') }}",
            showUrl: "{{ url('scheduled-tasks') }}/:id",
            csrfToken: "{{ csrf_token() }}",
        };
    </script>
    <script src="{{ asset('js/scheduled-tasks/index.js') }}"></script>
    <script>
        var __schedLink = document.getElementById("scheduled_tasks_link");
        if (__schedLink) __schedLink.classList.add("active");
    </script>
@endpush
