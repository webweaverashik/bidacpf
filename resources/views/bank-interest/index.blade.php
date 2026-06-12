@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/bank-interest/bank-interest.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Interest Distributions')

@section('header-title')
    @include('bank-interest.partials.page-header', [
        'heading' => 'Interest Distributions',
        'crumbs' => ['CPF Operation', 'Bank Interest', 'Distributions'],
    ])
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" data-interest-table-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search distributions">
                </div>
            </div>

            <div class="card-toolbar">
                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-duotone ki-filter fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>Filter</button>
                <div class="menu menu-sub menu-sub-dropdown w-300px" data-kt-menu="true">
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <div class="separator border-gray-200"></div>
                    <div class="px-7 py-5" data-interest-table-filter="form">
                        <div class="mb-5">
                            <label class="form-label fs-6 fw-semibold">Status:</label>
                            <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                data-interest-filter="status" data-placeholder="Select option" data-allow-clear="false"
                                data-hide-search="true">
                                <option></option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true" data-interest-table-filter="reset">Reset</button>
                            <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                data-interest-table-filter="filter">Apply</button>
                        </div>
                    </div>
                </div>

                <div class="dropdown me-3">
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-exit-up fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>Export
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

                @can('bank_interest.create')
                    <a href="{{ route('bank-interest.distribute') }}" class="btn btn-primary">
                        <i class="ki-duotone ki-plus fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>New Distribution</a>
                @endcan
            </div>
        </div>

        <div class="card-body py-4">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table" id="bida_interest_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th>Reference</th>
                        <th>Cut-off Date</th>
                        <th>Fiscal Year</th>
                        <th class="text-end">Interest (Tk)</th>
                        <th class="text-center">Members</th>
                        <th class="text-end">Distributed (Tk)</th>
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
        var BidaInterestListConfig = {
            tableId: 'bida_interest_table',
            dataUrl: "{{ route('bank-interest.data') }}",
            exportUrl: "{{ route('bank-interest.export') }}",
            filterPrefix: 'interest',
            filters: ['status'],
            order: [
                [2, 'desc']
            ],
            pageLength: 10,
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'reference'
                },
                {
                    data: 'cut_off'
                },
                {
                    data: 'fiscal_year'
                },
                {
                    data: 'interest',
                    className: 'text-end'
                },
                {
                    data: 'members',
                    className: 'text-center'
                },
                {
                    data: 'distributed',
                    className: 'text-end'
                },
                {
                    data: 'status'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-end'
                }
            ]
        };
    </script>
    <script src="{{ asset('js/bank-interest/bida-interest-list.js') }}"></script>
@endpush
