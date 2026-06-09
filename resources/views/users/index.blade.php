@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/employees/index.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'All Employees')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Employees
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">
                    Employee Info
                </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                All Employees
            </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    @php
        // Preloading permissions checking
        $canDeactivate = auth()->user()->can('user.update');
        $canEdit = auth()->user()->can('user.update');
        $canDelete = auth()->user()->can('user.delete');
    @endphp

    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5">
                    </i>
                    <input type="text" data-kt-user-table-filter="search"
                        class="form-control form-control-solid w-350px ps-13" placeholder="Search user" />
                </div>
                <!--end::Search-->
            </div>
            <!--begin::Card title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <!--begin::Show Deleted Toggle-->
                    <div class="form-check form-switch form-check-custom form-check-solid me-5">
                        <input class="form-check-input" type="checkbox" id="show_deleted_only" />
                        <label class="form-check-label fw-semibold text-gray-700" for="show_deleted_only">
                            Show Deleted Only
                        </label>
                    </div>
                    <!--end::Show Deleted Toggle-->
                    <!--begin::Filter-->
                    <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-filter fs-2"></i>Filter</button>
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
                        <div class="px-7 py-5" data-users-table-filter="form">
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Branch:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-users-table-filter="branch" data-hide-search="true">
                                    <option></option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->branch_name }}">{{ $branch->branch_name }} Branch
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Role:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true" data-users-table-filter="role"
                                    data-hide-search="true">
                                    <option></option>
                                    <option value="Admin">Admin</option>
                                    <option value="Manager">Manager</option>
                                    <option value="Accountant">Accountant</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-users-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                    data-users-table-filter="filter">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Menu 1-->
                    <!--begin::Add user-->
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_user">
                        <i class="ki-outline ki-plus fs-2"></i>New User</a>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="kt_users_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-50px">#</th>
                        <th class="min-w-125px">User Info</th>
                        <th>Mobile No.</th>
                        <th class="w-150px">Branch</th>
                        <th class="w-100px">Role</th>
                        <th class="min-w-125px">Last Login</th>
                        <th class="min-w-100px">Active/Inactive</th>
                        <th class="min-w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold fs-5">
                    {{-- Data loaded via AJAX --}}
                </tbody>
            </table>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->

    <!--begin::Modal - Add User-->
    <div class="modal fade" id="kt_modal_add_user" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_user_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Add New User</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-add-users-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_add_user_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_user_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_user_header"
                            data-kt-scroll-wrappers="#kt_modal_add_user_scroll" data-kt-scroll-offset="300px">

                            <!--begin::Photo Upload-->
                            <div class="fv-row mb-7">
                                <label class="d-block fw-semibold fs-6 mb-5">User Photo</label>
                                <div class="image-input image-input-outline image-input-placeholder"
                                    data-kt-image-input="true" id="kt_image_input_add">
                                    <!--begin::Preview-->
                                    <div class="image-input-wrapper w-125px h-125px"
                                        style="background-image: url('{{ asset('img/male-placeholder.png') }}');">
                                    </div>
                                    <!--end::Preview-->
                                    <!--begin::Edit-->
                                    <label
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                        title="Change photo">
                                        <i class="ki-outline ki-pencil fs-7"></i>
                                        <input type="file" name="user_photo" accept=".png, .jpg, .jpeg" />
                                        <input type="hidden" name="photo_remove" />
                                    </label>
                                    <!--end::Edit-->
                                    <!--begin::Cancel-->
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                        title="Cancel photo">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </span>
                                    <!--end::Cancel-->
                                    <!--begin::Remove-->
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                        title="Remove photo">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </span>
                                    <!--end::Remove-->
                                </div>
                                <div class="form-text">Allowed file types: png, jpg, jpeg. Max size: 100KB</div>
                            </div>
                            <!--end::Photo Upload-->

                            <div class="row">
                                <!--begin::Role Input-->
                                <div class="col-lg-12">
                                    <div class="fv-row mb-7">
                                        <label class="required fw-semibold fs-6 mb-2">Role</label>
                                        <!--begin::Row-->
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <!--begin::Option-->
                                                <input type="radio" class="btn-check" name="user_role" value="admin"
                                                    id="role_admin_input" />
                                                <label
                                                    class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                    for="role_admin_input">
                                                    <i class="las la-user-secret fs-2x me-5"></i>
                                                    <!--begin::Info-->
                                                    <span class="d-block fw-semibold text-start">
                                                        <span class="text-gray-900 fw-bold d-block fs-6">Admin</span>
                                                    </span>
                                                    <!--end::Info-->
                                                </label>
                                                <!--end::Option-->
                                            </div>
                                            <div class="col-lg-4">
                                                <!--begin::Option-->
                                                <input type="radio" class="btn-check" name="user_role" value="manager"
                                                    id="role_mananger_input" />
                                                <label
                                                    class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                    for="role_mananger_input">
                                                    <i class="las la-user-ninja fs-2x me-5"></i>
                                                    <!--begin::Info-->
                                                    <span class="d-block fw-semibold text-start">
                                                        <span class="text-gray-900 fw-bold d-block fs-6">Manager</span>
                                                    </span>
                                                    <!--end::Info-->
                                                </label>
                                                <!--end::Option-->
                                            </div>
                                            <div class="col-lg-4">
                                                <!--begin::Option-->
                                                <input type="radio" class="btn-check" name="user_role"
                                                    value="accountant" id="role_accountant_input" checked="checked" />
                                                <label
                                                    class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                    for="role_accountant_input">
                                                    <i class="las la-user fs-2x me-5"></i>
                                                    <!--begin::Info-->
                                                    <span class="d-block fw-semibold text-start">
                                                        <span class="text-gray-900 fw-bold d-block fs-6">Accountant</span>
                                                    </span>
                                                    <!--end::Info-->
                                                </label>
                                                <!--end::Option-->
                                            </div>
                                        </div>
                                        <!--end::Row-->
                                    </div>
                                </div>
                                <!--end::Role Input -->
                                <!--begin::User name input-->
                                <div class="col-lg-6" id="user_name_input_div">
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="required fw-semibold fs-6 mb-2">Name</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="text" name="user_name"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Write full name" value="{{ old('user_name') }}" required />
                                        <!--end::Input-->
                                    </div>
                                </div>
                                <!--end::User name input-->
                                <!--begin::User email input-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <label class="required fw-semibold fs-6 mb-2">Email</label>
                                        <input type="email" name="user_email"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="test@mail.com" value="{{ old('user_email') }}" required />
                                    </div>
                                </div>
                                <!--end::User email input-->
                                <!--begin::User mobile input-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <label class="required fw-semibold fs-6 mb-2">Mobile No.</label>
                                        <input type="text" name="user_mobile"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="e.g. 01812345678" value="{{ old('user_mobile') }}" required />
                                    </div>
                                </div>
                                <!--end::User mobile input-->
                                <!--begin::Branch Input-->
                                <div class="col-lg-6" id="branch_input_div">
                                    <div class="fv-row mb-5">
                                        <!--begin::Label-->
                                        <label class="required fs-6 fw-semibold form-label mb-2">Assign to Branch</label>
                                        <!--end::Label-->
                                        <!--begin::Row-->
                                        <div class="fv-row">
                                            <!--begin::Col-->
                                            <select name="user_branch" class="form-select form-select-solid"
                                                data-control="select2" data-hide-search="true"
                                                data-placeholder="Select branch" required>
                                                <option></option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }} Branch
                                                    </option>
                                                @endforeach
                                            </select>
                                            <!--end::Col-->
                                        </div>
                                        <!--end::Row-->
                                    </div>
                                </div>
                                <!--end::Branch Input-->
                            </div>
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-add-users-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-add-users-modal-action="submit">
                                <span class="indicator-label">Submit</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::Form-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Add User-->

    <!--begin::Modal - Edit User-->
    <div class="modal fade" id="kt_modal_edit_user" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_edit_user_title">Update User</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-edit-users-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_user_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_user_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_user_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_user_scroll" data-kt-scroll-offset="300px">

                            <!--begin::Photo Upload-->
                            <div class="fv-row mb-7">
                                <label class="d-block fw-semibold fs-6 mb-5">User Photo</label>
                                <div class="image-input image-input-outline image-input-placeholder"
                                    data-kt-image-input="true" id="kt_image_input_edit">
                                    <!--begin::Preview-->
                                    <div class="image-input-wrapper w-125px h-125px" id="edit_user_photo_preview"
                                        style="background-image: url('{{ asset('img/male-placeholder.png') }}');">
                                    </div>
                                    <!--end::Preview-->
                                    <!--begin::Edit-->
                                    <label
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                        title="Change photo">
                                        <i class="ki-outline ki-pencil fs-7"></i>
                                        <input type="file" name="user_photo_edit" accept=".png, .jpg, .jpeg" />
                                        <input type="hidden" name="remove_photo" value="0" />
                                    </label>
                                    <!--end::Edit-->
                                    <!--begin::Cancel-->
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                        title="Cancel photo">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </span>
                                    <!--end::Cancel-->
                                    <!--begin::Remove-->
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                        title="Remove photo">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </span>
                                    <!--end::Remove-->
                                </div>
                                <div class="form-text">Allowed file types: png, jpg, jpeg. Max size: 100KB</div>
                            </div>
                            <!--end::Photo Upload-->

                            <div class="row">
                                <!--begin::Role Input-->
                                <div class="col-lg-12">
                                    <div class="fv-row mb-7">
                                        <label class="required fw-semibold fs-6 mb-2">Role</label>
                                        <!--begin::Row-->
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <!--begin::Option-->
                                                <input type="radio" class="btn-check" name="user_role_edit"
                                                    value="admin" id="role_admin_edit" />
                                                <label
                                                    class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                    for="role_admin_edit">
                                                    <i class="las la-user-secret fs-2x me-5"></i>
                                                    <!--begin::Info-->
                                                    <span class="d-block fw-semibold text-start">
                                                        <span class="text-gray-900 fw-bold d-block fs-6">Admin</span>
                                                    </span>
                                                    <!--end::Info-->
                                                </label>
                                                <!--end::Option-->
                                            </div>
                                            <div class="col-lg-4">
                                                <!--begin::Option-->
                                                <input type="radio" class="btn-check" name="user_role_edit"
                                                    value="manager" id="role_manager_edit" />
                                                <label
                                                    class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                    for="role_manager_edit">
                                                    <i class="las la-user-ninja fs-2x me-5"></i>
                                                    <!--begin::Info-->
                                                    <span class="d-block fw-semibold text-start">
                                                        <span class="text-gray-900 fw-bold d-block fs-6">Manager</span>
                                                    </span>
                                                    <!--end::Info-->
                                                </label>
                                                <!--end::Option-->
                                            </div>
                                            <div class="col-lg-4">
                                                <!--begin::Option-->
                                                <input type="radio" class="btn-check" name="user_role_edit"
                                                    value="accountant" id="role_accountant_edit" />
                                                <label
                                                    class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                    for="role_accountant_edit">
                                                    <i class="las la-user fs-2x me-5"></i>
                                                    <!--begin::Info-->
                                                    <span class="d-block fw-semibold text-start">
                                                        <span class="text-gray-900 fw-bold d-block fs-6">Accountant</span>
                                                    </span>
                                                    <!--end::Info-->
                                                </label>
                                                <!--end::Option-->
                                            </div>
                                        </div>
                                        <!--end::Row-->
                                    </div>
                                </div>
                                <!--end::Role Input -->
                                <!--begin::User name input-->
                                <div class="col-lg-6" id="user_name_edit_div">
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="required fw-semibold fs-6 mb-2">Name</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="text" name="user_name_edit"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Write full name" required />
                                        <!--end::Input-->
                                    </div>
                                </div>
                                <!--end::User name input-->
                                <!--begin::User email input-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <label class="fw-semibold fs-6 mb-2 required">Email</label>
                                        <input type="email" name="user_email_edit"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="test@mail.com" required />
                                    </div>
                                </div>
                                <!--end::User email input-->
                                <!--begin::User mobile input-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <label class="required fw-semibold fs-6 mb-2">Mobile No.</label>
                                        <input type="text" name="user_mobile_edit"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="e.g. 01812345678" required />
                                    </div>
                                </div>
                                <!--end::User mobile input-->
                                <!--begin::Branch Input-->
                                <div class="col-lg-6" id="branch_edit_div">
                                    <div class="fv-row mb-5">
                                        <!--begin::Label-->
                                        <label class="required fs-6 fw-semibold form-label mb-2">Assign to Branch</label>
                                        <!--end::Label-->
                                        <!--begin::Row-->
                                        <div class="fv-row">
                                            <!--begin::Col-->
                                            <select name="user_branch_edit" class="form-select form-select-solid"
                                                data-control="select2" data-hide-search="true"
                                                data-placeholder="Select branch" required>
                                                <option></option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }} Branch
                                                    </option>
                                                @endforeach
                                            </select>
                                            <!--end::Col-->
                                        </div>
                                        <!--end::Row-->
                                    </div>
                                </div>
                                <!--end::Branch Input-->
                            </div>
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-edit-users-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-edit-users-modal-action="submit">
                                <span class="indicator-label">Update</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::Form-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Edit User-->

    <!--begin::Modal - Edit User Password-->
    <div class="modal fade" id="kt_modal_edit_password" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-450px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_password_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_edit_password_title">Password Reset</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-edit-password-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_password_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5" id="kt_modal_edit_password_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_password_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_password_scroll" data-kt-scroll-offset="300px">
                            <div class="row">
                                <div class="col-lg-12">
                                    <!--begin::Input group-->
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="required fw-semibold fs-6 mb-2">Write New Password</label>
                                        <!--end::Label-->
                                        <div class="input-group">
                                            <input type="password" name="new_password" id="userPasswordNew"
                                                class="form-control mb-3 mb-lg-0" placeholder="Enter New Password"
                                                required />
                                            <span class="input-group-text toggle-password" data-target="userPasswordNew"
                                                style="cursor: pointer;" title="See Password" data-bs-toggle="tooltip">
                                                <i class="ki-outline ki-eye fs-3"></i>
                                            </span>
                                        </div>
                                        <!-- Password strength meter -->
                                        <div id="password-strength-text" class="mt-1 fw-bold small text-muted"></div>
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div id="password-strength-bar" class="progress-bar" role="progressbar"
                                                style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <!--end::Input group-->
                                </div>
                            </div>
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-edit-password-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-success" data-kt-edit-password-modal-action="submit">
                                <span class="indicator-label">Update</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::Form-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Edit User Password-->

    <!--begin::Modal - Delete User Confirmation-->
    <div class="modal fade" id="kt_modal_delete_user" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold text-danger">
                        <i class="ki-outline ki-shield fs-2 text-danger me-2"></i>
                        Delete User Account
                    </h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body pt-0">
                    <!--begin::Step 1 - Warning-->
                    <div id="delete_step_warning">
                        <!--begin::User Info-->
                        <div class="d-flex align-items-center bg-light-primary rounded p-4 mb-5">
                            <div class="symbol symbol-50px me-4">
                                <img id="delete_user_photo" src="{{ asset('img/male-placeholder.png') }}"
                                    alt="User" />
                            </div>
                            <div>
                                <div class="fw-bold text-gray-900 fs-5" id="delete_user_name">User Name</div>
                                <div class="text-muted fs-7" id="delete_user_email">user@email.com</div>
                            </div>
                        </div>
                        <!--end::User Info-->

                        <!--begin::Warning Notice-->
                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mb-5">
                            <i class="ki-outline ki-information-5 fs-2tx text-warning me-4"></i>
                            <div class="d-flex flex-stack flex-grow-1">
                                <div class="fw-semibold">
                                    <h4 class="text-gray-900 fw-bold">What happens when you delete this user?</h4>
                                    <ul class="text-gray-700 fs-6 mb-0 ps-4">
                                        <li>User's login access will be revoked immediately</li>
                                        <li>Account will be hidden from active users list</li>
                                        <li>User will not be able to log into the system</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!--end::Warning Notice-->

                        <!--begin::Recovery Notice-->
                        <div class="notice d-flex bg-light-success rounded border-success border border-dashed p-4 mb-5">
                            <i class="ki-outline ki-shield-tick fs-2tx text-success me-4"></i>
                            <div class="d-flex flex-stack flex-grow-1">
                                <div class="fw-semibold">
                                    <h4 class="text-gray-900 fw-bold">Data is Safe!</h4>
                                    <p class="text-gray-700 fs-6 mb-0">
                                        All user data including transaction history, wallet logs, activity records,
                                        and profile information will be <strong>safely preserved</strong> and can be
                                        recovered by an administrator if needed.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <!--end::Recovery Notice-->

                        <!--begin::Actions-->
                        <div class="d-flex justify-content-end gap-3">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                No, Keep User
                            </button>
                            <button type="button" class="btn btn-danger" id="btn_delete_proceed">
                                <i class="ki-outline ki-trash fs-4 me-1"></i>
                                Yes, Delete User
                            </button>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Step 1 - Warning-->

                    <!--begin::Step 2 - Confirm-->
                    <div id="delete_step_confirm" style="display: none;">
                        <!--begin::User Badge-->
                        <div class="text-center mb-5">
                            <span class="badge badge-light-danger fs-5 px-4 py-3" id="delete_user_name_confirm">
                                User Name
                            </span>
                        </div>
                        <!--end::User Badge-->

                        <!--begin::Final Warning-->
                        <div class="alert alert-danger d-flex align-items-center p-4 mb-5">
                            <i class="ki-outline ki-shield-cross fs-2hx text-danger me-3"></i>
                            <div class="d-flex flex-column">
                                <span class="fw-bold fs-5">Final Confirmation Required</span>
                                <span class="text-gray-700">Type <strong>DELETE</strong> below to confirm</span>
                            </div>
                        </div>
                        <!--end::Final Warning-->

                        <!--begin::Confirm Input-->
                        <div class="fv-row mb-5">
                            <input type="text" id="delete_confirm_input"
                                class="form-control form-control-lg text-center" placeholder="Type DELETE to confirm"
                                autocomplete="off" />
                            <div class="form-text text-center mt-2">This action will hide the user from the system</div>
                        </div>
                        <!--end::Confirm Input-->

                        <!--begin::Actions-->
                        <div class="d-flex justify-content-end gap-3">
                            <button type="button" class="btn btn-light" id="btn_delete_back">
                                <i class="ki-outline ki-arrow-left fs-4 me-1"></i>
                                Go Back
                            </button>
                            <button type="button" class="btn btn-danger" id="btn_delete_confirm" disabled>
                                <span class="indicator-label">
                                    <i class="ki-outline ki-trash fs-4 me-1"></i>
                                    Delete User
                                </span>
                                <span class="indicator-progress">
                                    Deleting... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Step 2 - Confirm-->
                </div>
            </div>
        </div>
    </div>
    <!--end::Modal - Delete User Confirmation-->

    <!--begin::Modal - Recover User Confirmation-->
    <div class="modal fade" id="kt_modal_recover_user" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-400px">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold text-success">
                        <i class="ki-outline ki-arrow-circle-left fs-2 text-success me-2"></i>
                        Recover User
                    </h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-5">
                        <span class="badge badge-light-success fs-5 px-4 py-3" id="recover_user_name">
                            User Name
                        </span>
                    </div>

                    <div class="notice d-flex bg-light-success rounded border-success border border-dashed p-4 mb-5">
                        <i class="ki-outline ki-shield-tick fs-2tx text-success me-4"></i>
                        <div class="d-flex flex-stack flex-grow-1">
                            <div class="fw-semibold">
                                <p class="text-gray-700 fs-6 mb-0">
                                    This will restore the user account and allow them to log in again.
                                    All their previous data will be accessible.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="btn_recover_confirm">
                            <span class="indicator-label">
                                <i class="ki-outline ki-arrow-circle-left fs-4 me-1"></i>
                                Recover User
                            </span>
                            <span class="indicator-progress">
                                Recovering... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--end::Modal - Recover User Confirmation-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script src="{{ asset('js/employees/index.js') }}"></script>
@endpush
