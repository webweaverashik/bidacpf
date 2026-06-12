@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/cpf-ledger/transactions.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Ledger Transactions')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">Ledger Transactions</h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">CPF Operation</a>
            </li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">Ledger Transactions</li>
        </ul>
    </div>
@endsection

@section('content')
    @php $typeOptions = \App\Enums\LedgerTransactionType::options(); @endphp

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" data-txn-table-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search transactions">
                </div>
            </div>

            <div class="card-toolbar">
                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-duotone ki-filter fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span></i>Filter</button>
                <div class="menu menu-sub menu-sub-dropdown w-350px" data-kt-menu="true">
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <div class="separator border-gray-200"></div>
                    <div class="px-7 py-5" data-txn-table-filter="form">
                        <div class="mb-5">
                            <label class="form-label fs-6 fw-semibold">Employee:</label>
                            <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                data-txn-filter="employee" data-placeholder="All employees" data-allow-clear="true">
                                <option></option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->cpf_account_no }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label fs-6 fw-semibold">Transaction Type:</label>
                            <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                data-txn-filter="type" data-placeholder="All types" data-allow-clear="true"
                                data-hide-search="true">
                                <option></option>
                                @foreach ($typeOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row mb-5">
                            <div class="col-6">
                                <label class="form-label fs-6 fw-semibold">From:</label>
                                <input type="text" class="form-control form-control-solid" id="txn_from"
                                    placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div class="col-6">
                                <label class="form-label fs-6 fw-semibold">To:</label>
                                <input type="text" class="form-control form-control-solid" id="txn_to"
                                    placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true" data-txn-table-filter="reset">Reset</button>
                            <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                data-txn-table-filter="filter">Apply</button>
                        </div>
                    </div>
                </div>

                <div class="dropdown">
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-exit-up fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span></i>Export
                    </button>
                    <div id="kt_table_report_dropdown_menu"
                        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                        data-kt-menu="true">
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-row-export="xlsx">Export
                                as Excel</a></div>
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3"
                                data-row-export="csv">Export as
                                CSV</a></div>
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3"
                                data-row-export="pdf">Export
                                as PDF</a></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body py-4">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-4 ashik-table"
                id="bida_ledger_txn_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th class="text-end">Debit (Tk)</th>
                        <th class="text-end">Credit (Tk)</th>
                        <th class="text-end">Balance (Tk)</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaLedgerTxnConfig = {
            dataUrl: "{{ route('cpf-ledger.transactions.data') }}",
            exportUrl: "{{ route('cpf-ledger.transactions.export') }}",
        };
    </script>
    <script src="{{ asset('js/cpf-ledger/transactions.js') }}"></script>
@endpush
