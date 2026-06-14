@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/reports/reports.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Reports')

@section('header-title')
    @include('reports.partials.page-header', [
        'heading' => 'Reports',
        'crumbs' => ['Reporting', 'Reports'],
    ])
@endsection

@section('content')
    <div class="row g-5 g-xl-8">
        {{-- ============================ Controls ============================ --}}
        <div class="col-xl-3">
            <div class="card card-flush">
                <div class="card-header border-0 pt-6">
                    <div class="card-title flex-column">
                        <h2 class="mb-1">Generate a Report</h2>
                        <div class="text-muted fs-7">Pick a report, set the options, then preview or download.</div>
                    </div>
                </div>

                <div class="card-body pt-2">
                    {{-- Report type (grouped) --}}
                    <div class="mb-6">
                        <label class="form-label fw-semibold required">Report Type</label>
                        <select id="report_select" class="form-select form-select-solid" data-control="select2"
                            data-placeholder="Select a report…" data-hide-search="false">
                            <option></option>
                            @forelse ($grouped as $groupLabel => $items)
                                <optgroup label="{{ $groupLabel }}">
                                    @foreach ($items as $item)
                                        <option value="{{ $item['key'] }}" data-kind="{{ $item['kind'] }}">
                                            {{ $item['label'] }}</option>
                                    @endforeach
                                </optgroup>
                            @empty
                                <option disabled>No reports available for your role.</option>
                            @endforelse
                        </select>
                        <div id="report_desc" class="form-text d-none"></div>
                    </div>

                    {{-- AJAX-loaded parameter panel --}}
                    <div id="report_params" class="rpt-params">
                        <div class="rpt-empty text-muted text-center py-10">
                            <i class="ki-duotone ki-filter-tablet fs-3x text-gray-300 mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="fs-6">Choose a report above to set its options.</div>
                        </div>
                    </div>

                    {{-- Action bar --}}
                    <div id="report_actions" class="d-none mt-8">
                        <div class="separator separator-dashed mb-6"></div>
                        <div class="d-flex flex-row gap-3">
                            <button type="button" class="btn btn-primary w-100" data-report-action="preview">
                                <i class="ki-duotone ki-eye fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>Preview
                            </button>

                            @if ($canExport)
                                <div class="dropdown w-100">
                                    <button type="button" class="btn btn-light-primary w-100" data-kt-menu-trigger="click"
                                        data-kt-menu-placement="bottom-start">
                                        <i class="ki-duotone ki-exit-down fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>Download
                                    </button>
                                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                                        data-kt-menu="true" id="report_download_menu">
                                        <div class="menu-item px-3" data-format="pdf">
                                            <a href="#" class="menu-link px-3" data-report-action="download"
                                                data-fmt="pdf">
                                                <i class="ki-outline ki-file-down fs-4 me-2"></i>Download PDF</a>
                                        </div>
                                        <div class="menu-item px-3" data-format="xlsx">
                                            <a href="#" class="menu-link px-3" data-report-action="download"
                                                data-fmt="xlsx">
                                                <i class="ki-outline ki-file-down fs-4 me-2"></i>Download Excel</a>
                                        </div>
                                        <div class="menu-item px-3" data-format="csv">
                                            <a href="#" class="menu-link px-3" data-report-action="download"
                                                data-fmt="csv">
                                                <i class="ki-outline ki-file-down fs-4 me-2"></i>Download CSV</a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================= Preview ============================= --}}
        <div class="col-xl-9">
            <div class="card card-flush h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title flex-column">
                        <h2 class="mb-1" id="report_preview_title">Preview</h2>
                        <div class="text-muted fs-7" id="report_preview_subtitle">The selected report will appear here.
                        </div>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div id="report_preview">
                        <div class="rpt-empty text-muted text-center py-20">
                            <i class="ki-duotone ki-document fs-3x text-gray-300 mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="fs-6">No preview yet.</div>
                            <div class="fs-7">Select a report and click <span class="fw-semibold">Preview</span>.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaReportConfig = {
            paramsUrl: "{{ route('reports.params') }}",
            previewUrl: "{{ route('reports.preview') }}",
            @if ($canExport)
                generateUrl: "{{ route('reports.generate') }}",
            @endif
            csrf: "{{ csrf_token() }}",
            canExport: {{ $canExport ? 'true' : 'false' }}
        };
    </script>
    <script src="{{ asset('js/reports/bida-report.js') }}"></script>
@endpush
