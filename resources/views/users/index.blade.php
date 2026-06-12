@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/users/index.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'User Management')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Users
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">User Management</li>
        </ul>
    </div>
@endsection

@section('content')
    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" data-kt-user-table-filter="search"
                        class="form-control form-control-solid w-250px w-md-350px ps-13" placeholder="Search user" />
                </div>
            </div>
            <!--end::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <div class="d-flex justify-content-end align-items-center gap-3 flex-wrap"
                    data-kt-user-table-toolbar="base">
                    <!--begin::Show Deleted Toggle-->
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" id="show_deleted_only" />
                        <label class="form-check-label fw-semibold text-gray-700" for="show_deleted_only">
                            Show Deleted Only
                        </label>
                    </div>
                    <!--end::Show Deleted Toggle-->

                    <!--begin::Filter-->
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-filter fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>Filter
                    </button>
                    <!--begin::Menu-->
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true"
                        id="kt_user_filter_menu">
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                        </div>
                        <div class="separator border-gray-200"></div>
                        <div class="px-7 py-5" data-users-table-filter="form">
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Role:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select role" data-allow-clear="true" data-users-table-filter="role"
                                    data-hide-search="true">
                                    <option></option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role }}">{{ $role }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-users-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                    data-users-table-filter="filter">Apply</button>
                            </div>
                        </div>
                    </div>
                    <!--end::Menu-->
                    <!--end::Filter-->

                    @can('user.create')
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_user">
                            <i class="ki-duotone ki-plus fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>New User
                        </a>
                    @endcan
                </div>
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table" id="kt_users_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-50px">#</th>
                        <th class="min-w-200px">User Info</th>
                        <th class="min-w-175px">Email</th>
                        <th class="min-w-120px">Mobile No.</th>
                        <th class="w-150px">Role</th>
                        <th class="min-w-125px">Last Login</th>
                        <th class="w-100px text-center">Status</th>
                        <th class="text-end min-w-150px">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold fs-6">
                    {{-- Loaded via AJAX --}}
                </tbody>
            </table>
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->

    @include('users.partials.add-user-modal')
    @include('users.partials.edit-user-modal')
    @include('users.partials.reset-password-modal')
    @include('users.partials.delete-user-modal')
    @include('users.partials.recover-user-modal')
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaUserRoutes = {
            data: "{{ route('users.data') }}",
            store: "{{ route('users.store') }}",
            show: "{{ route('users.show', ':id') }}",
            json: "{{ route('users.json', ':id') }}",
            update: "{{ route('users.update', ':id') }}",
            destroy: "{{ route('users.destroy', ':id') }}",
            toggleActive: "{{ route('users.toggleActive') }}",
            recover: "{{ route('users.recover') }}",
            resetPassword: "{{ route('users.password', ':id') }}",
            placeholder: "{{ asset('img/male-placeholder.png') }}"
        };
    </script>
    <script src="{{ asset('js/users/index.js') }}"></script>
@endpush
