@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/cpf-ledger/show.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'CPF Statement — ' . $employee->name)

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">CPF Statement</h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('cpf-ledger.index') }}" class="text-muted text-hover-primary">CPF Ledger</a>
            </li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">{{ $employee->name }}</li>
        </ul>
    </div>
@endsection

@section('content')
    <!--begin::Member card-->
    <div class="card mb-6">
        <div class="card-body d-flex flex-wrap align-items-center">
            <div class="symbol symbol-75px me-5 mb-3">
                <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" class="rounded">
            </div>
            <div class="flex-grow-1 me-5 mb-3">
                <div class="fs-3 fw-bold text-gray-900">{{ $employee->name }}</div>
                <div class="text-muted fw-semibold">
                    {{ $employee->designation }}
                    @if ($employee->grade)
                        &middot; Grade {{ $employee->grade }}
                    @endif
                    &middot; A/C: {{ $employee->cpf_account_no }}
                </div>
                <div class="mt-2">
                    @if ($employee->is_active)
                        <span class="badge badge-light-success">Active</span>
                    @else
                        <span class="badge badge-light-danger">Inactive</span>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-wrap">
                <div class="border border-gray-300 border-dashed rounded min-w-150px py-3 px-4 me-4 mb-3">
                    <div class="fs-6 text-gray-800 fw-bold">{{ $employee->payScaleStep?->payScale?->name ?? '—' }}</div>
                    <div class="fw-semibold text-gray-500 fs-7">Pay Scale</div>
                </div>
                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-4 mb-3">
                    <div class="fs-5 text-gray-800 fw-bold">{{ number_format((int) $employee->current_basic_salary) }}
                    </div>
                    <div class="fw-semibold text-gray-500 fs-7">Basic Salary (Tk)</div>
                </div>
                <div class="border border-gray-300 border-dashed rounded min-w-110px py-3 px-4 me-4 mb-3">
                    <div class="fs-5 text-success fw-bold">{{ number_format($totalCredits) }}</div>
                    <div class="fw-semibold text-gray-500 fs-7">Total Credits</div>
                </div>
                <div class="border border-gray-300 border-dashed rounded min-w-110px py-3 px-4 me-4 mb-3">
                    <div class="fs-5 text-danger fw-bold">{{ number_format($totalDebits) }}</div>
                    <div class="fw-semibold text-gray-500 fs-7">Total Debits</div>
                </div>
                <div class="border border-primary border-dashed rounded min-w-125px py-3 px-4 mb-3">
                    <div class="fs-4 text-primary fw-bold">{{ number_format($balance) }}</div>
                    <div class="fw-semibold text-gray-500 fs-7">Current Balance (Tk)</div>
                </div>
            </div>
        </div>
    </div>
    <!--end::Member card-->

    <!--begin::Statement card-->
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" data-stmt-table-filter="search"
                        class="form-control form-control-solid w-md-300px ps-12" placeholder="Search statement">
                </div>
            </div>

            <div class="card-toolbar">
                <div class="me-3" style="min-width: 200px;">
                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                        data-stmt-filter="fiscal-year" data-placeholder="All fiscal years" data-allow-clear="true"
                        data-hide-search="true">
                        <option></option>
                        @foreach ($fiscalYears as $fy)
                            <option value="{{ $fy }}">FY {{ $fy }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dropdown">
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-exit-up fs-2"></i>Export
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
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-4 ashik-table"
                id="bida_ledger_stmt_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Remarks</th>
                        <th class="text-end">Debit (Tk)</th>
                        <th class="text-end">Credit (Tk)</th>
                        <th class="text-end">Balance (Tk)</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <!--end::Statement card-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaLedgerStatementConfig = {
            dataUrl: "{{ route('cpf-ledger.statement.data', $employee->id) }}",
            exportUrl: "{{ route('cpf-ledger.statement.export', $employee->id) }}",
        };
    </script>
    <script src="{{ asset('js/cpf-ledger/show.js') }}"></script>
@endpush