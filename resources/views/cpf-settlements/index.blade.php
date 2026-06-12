@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/cpf-settlements/cpf-settlements.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Final Settlements')

@section('header-title')
    @include('cpf-settlements.partials.page-header', [
        'heading' => 'Final Settlements',
        'crumbs' => ['CPF Operation', 'Final Settlement', 'Settlements'],
    ])
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" data-settlement-table-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search settlements">
                </div>
            </div>

            <div class="card-toolbar">
                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-outline ki-filter fs-2"></i>Filter</button>
                <div class="menu menu-sub menu-sub-dropdown w-300px" data-kt-menu="true">
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <div class="separator border-gray-200"></div>
                    <div class="px-7 py-5" data-settlement-table-filter="form">
                        <div class="mb-5">
                            <label class="form-label fs-6 fw-semibold">Status:</label>
                            <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                data-settlement-filter="status" data-placeholder="Select option" data-allow-clear="false"
                                data-hide-search="true">
                                <option></option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label fs-6 fw-semibold">Type:</label>
                            <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                data-settlement-filter="type" data-placeholder="Select option" data-allow-clear="false"
                                data-hide-search="true">
                                <option></option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true" data-settlement-table-filter="reset">Reset</button>
                            <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                data-settlement-table-filter="filter">Apply</button>
                        </div>
                    </div>
                </div>

                <div class="dropdown me-3">
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-exit-up fs-2"></i>Export
                    </button>
                    <div id="kt_table_report_dropdown_menu"
                        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                        data-kt-menu="true">
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-row-export="xlsx">Export as Excel</a></div>
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-row-export="csv">Export as CSV</a></div>
                        <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-row-export="pdf">Export as PDF</a></div>
                    </div>
                </div>

                @can('cpf_settlement.create')
                    <a href="{{ route('cpf-settlements.create') }}" class="btn btn-primary">
                        <i class="ki-outline ki-plus fs-2"></i>New Settlement</a>
                @endcan
            </div>
        </div>

        <div class="card-body py-4">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table" id="bida_settlement_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th>Settlement No</th>
                        <th>Member</th>
                        <th>Type</th>
                        <th>Settlement Date</th>
                        <th class="text-end">Payable (Tk)</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
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
        var BidaSettlementListConfig = {
            tableId: 'bida_settlement_table',
            dataUrl: "{{ route('cpf-settlements.data') }}",
            exportUrl: "{{ route('cpf-settlements.export') }}",
            filterPrefix: 'settlement',
            filters: ['status', 'type'],
            order: [[4, 'desc']],
            pageLength: 10,
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'settlement_no' },
                { data: 'employee' },
                { data: 'type' },
                { data: 'date' },
                { data: 'payable', className: 'text-end' },
                { data: 'status' },
                { data: 'actions', orderable: false, searchable: false, className: 'text-end' }
            ]
        };
    </script>
    <script src="{{ asset('js/cpf-settlements/bida-settlement-list.js') }}"></script>
@endpush
