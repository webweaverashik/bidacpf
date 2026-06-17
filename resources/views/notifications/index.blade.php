@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Notifications')

@section('header-title')
    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
            Notifications
        </h1>
    </div>
@endsection

@section('content')
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
                    <input type="text" data-notifications-table-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search notifications">
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
                <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                    <!--begin::Header-->
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Separator-->
                    <div class="separator border-gray-200"></div>
                    <!--end::Separator-->
                    <!--begin::Content-->
                    <div class="px-7 py-5" data-notifications-table-filter="form">
                        <!--begin::Status-->
                        <div class="mb-5">
                            <label class="form-label fs-6 fw-semibold">Status:</label>
                            <select class="form-select form-select-solid fw-bold" data-notifications-table-filter="status"
                                data-kt-select2="true" data-placeholder="Select status" data-allow-clear="true"
                                data-hide-search="true" data-dropdown-parent="#kt_app_body">
                                <option></option>
                                <option value="unread">Unread</option>
                                <option value="read">Read</option>
                            </select>
                        </div>
                        <!--end::Status-->
                        <!--begin::Type-->
                        <div class="mb-5">
                            <label class="form-label fs-6 fw-semibold">Type:</label>
                            <select class="form-select form-select-solid fw-bold" data-notifications-table-filter="category"
                                data-kt-select2="true" data-placeholder="Select type" data-allow-clear="true"
                                data-hide-search="true" data-dropdown-parent="#kt_app_body">
                                <option></option>
                                @foreach ($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!--end::Type-->
                        <!--begin::Actions-->
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true" data-notifications-table-filter="reset">Reset</button>
                            <button type="button" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                data-notifications-table-filter="filter">Apply</button>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Menu 1-->

                <!--begin::Mark all as read-->
                <button type="button" class="btn btn-light-primary" id="notifications_mark_all">
                    <i class="ki-outline ki-check-circle fs-3"></i>
                    Mark all as read
                </button>
                <!--end::Mark all as read-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body pt-0">
            <table id="notifications_table" class="table table-row-dashed table-row-gray-300 align-middle gy-4 ashik-table">
                <thead>
                    <tr class="fw-bold text-gray-700 fs-7 text-uppercase gs-0">
                        <th class="w-50px">#</th>
                        <th>Type</th>
                        <th>Notification</th>
                        <th class="w-100px">Status</th>
                        <th class="w-150px">Time</th>
                        <th class="text-end w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <!--end::Card body-->
    </div>
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaNotificationsConfig = {
            dataUrl: "{{ route('notifications.data') }}",
            readAllUrl: "{{ route('notifications.read-all') }}",
            // :id is swapped client-side
            destroyUrl: "{{ route('notifications.destroy', ':id') }}",
            csrf: "{{ csrf_token() }}",
        };


    </script>

    <script src="{{ asset('js/notifications/index.js') }}"></script>
@endpush
