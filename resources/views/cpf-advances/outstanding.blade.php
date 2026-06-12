@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/cpf-advances/cpf-advance.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Outstanding Advances')

@section('header-title')
    @include('cpf-advances.partials.page-header', [
        'heading' => 'Outstanding Advances',
        'crumbs' => ['CPF Advance/Loan', 'Outstanding Advances'],
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
                    <input type="text" data-outstanding-table-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search advances">
                </div>
            </div>

            <div class="card-toolbar">
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

                <a href="{{ route('cpf-advances.index') }}" class="btn btn-light-primary">All Advances</a>
            </div>
        </div>

        <div class="card-body py-4">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table"
                id="bida_outstanding_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th>Advance No</th>
                        <th>Employee</th>
                        <th class="text-end">Approved (Tk)</th>
                        <th class="text-end">Outstanding (Tk)</th>
                        <th class="text-end">Per Inst. (Tk)</th>
                        <th class="min-w-150px">Progress</th>
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
        var BidaAdvanceListConfig = {
            tableId: 'bida_outstanding_table',
            dataUrl: "{{ route('cpf-advances.outstanding.data') }}",
            exportUrl: "{{ route('cpf-advances.outstanding.export') }}",
            filterPrefix: 'outstanding',
            filters: [],
            order: [
                [4, 'desc']
            ],
            pageLength: 10,
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'advance_no'
                },
                {
                    data: 'employee'
                },
                {
                    data: 'approved',
                    className: 'text-end'
                },
                {
                    data: 'outstanding',
                    className: 'text-end'
                },
                {
                    data: 'installment',
                    className: 'text-end'
                },
                {
                    data: 'progress',
                    orderable: false,
                    searchable: false
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
    <script src="{{ asset('js/cpf-advances/bida-advance-list.js') }}"></script>
@endpush
