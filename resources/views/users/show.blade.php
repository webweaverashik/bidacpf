@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'User Activity')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">User Details</h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted"><a href="{{ route('users.index') }}"
                    class="text-muted text-hover-primary">Users</a></li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">{{ $user->name }}</li>
        </ul>
    </div>
@endsection

@section('content')
    @php
        $roleName = $user->roles->first()?->name ?? '—';
        $roleBadge = match ($roleName) {
            'Admin' => 'badge-light-danger',
            'CPF Officer' => 'badge-light-success',
            'Auditor' => 'badge-light-info',
            default => 'badge-light-secondary',
        };
    @endphp

    {{-- Profile Card --}}
    <div class="card mb-5 mb-xl-10">
        <div class="card-body pt-9 pb-0">
            <div class="d-flex flex-column flex-sm-row flex-wrap flex-sm-nowrap">
                <div class="me-0 me-sm-7 mb-4 text-center text-sm-start">
                    <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative d-inline-block">
                        <img src="{{ $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png') }}"
                            alt="{{ $user->name }}" class="w-100" />
                        <div
                            class="position-absolute translate-middle bottom-0 start-100 mb-6 rounded-circle border border-4 border-body h-20px w-20px {{ $user->is_active ? 'bg-success' : 'bg-gray-400' }}">
                        </div>
                    </div>
                </div>

                <div class="flex-grow-1">
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-center align-items-md-start mb-2">
                        <div class="d-flex flex-column text-center text-md-start mb-4 mb-md-0">
                            <div class="d-flex flex-column flex-sm-row align-items-center mb-2">
                                <span class="text-gray-900 fs-2 fw-bold me-2">{{ $user->name }}</span>
                                <span class="badge {{ $roleBadge }} fw-bold me-2">{{ $roleName }}</span>
                                <span
                                    class="badge {{ $user->is_active ? 'badge-light-success' : 'badge-light-danger' }} fw-bold">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div
                                class="d-flex flex-column flex-sm-row flex-wrap justify-content-center justify-content-md-start fw-semibold fs-6 mb-4 pe-2">
                                <span class="d-flex align-items-center text-gray-500 me-0 me-sm-5 mb-2">
                                    <i
                                        class="ki-outline ki-briefcase fs-4 me-1"></i>{{ $user->designation ?: 'No designation' }}
                                </span>
                                <span class="d-flex align-items-center text-gray-500 me-0 me-sm-5 mb-2 text-break">
                                    <i class="ki-outline ki-sms fs-4 me-1"></i>{{ $user->email }}
                                </span>
                                <span class="d-flex align-items-center text-gray-500 mb-2">
                                    <i class="ki-outline ki-phone fs-4 me-1"></i>{{ $user->mobile_number ?: 'N/A' }}
                                </span>
                            </div>
                        </div>

                        @can('user.update')
                            <div class="d-flex flex-column flex-sm-row gap-2 my-2 my-md-4">
                                <a href="#" class="btn btn-sm btn-light-primary edit-user-btn" data-bs-toggle="modal"
                                    data-bs-target="#kt_modal_edit_user" data-user-id="{{ $user->id }}">
                                    <i class="ki-outline ki-pencil fs-4 me-1"></i>Edit
                                </a>
                                <a href="#" class="btn btn-sm btn-light-warning change-password-btn"
                                    data-bs-toggle="modal" data-bs-target="#kt_modal_edit_password"
                                    data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}">
                                    <i class="ki-outline ki-key fs-4 me-1"></i>Reset Password
                                </a>
                            </div>
                        @endcan
                    </div>

                    {{-- Stats --}}
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="border border-gray-300 border-dashed rounded py-3 px-4 h-100">
                                <div class="d-flex align-items-center">
                                    <i class="ki-outline ki-shield-tick fs-3 text-primary me-2"></i>
                                    <div class="fs-4 fw-bold text-gray-800">{{ $roleName }}</div>
                                </div>
                                <div class="fw-semibold fs-7 text-gray-500">Role</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border border-gray-300 border-dashed rounded py-3 px-4 h-100">
                                <div class="d-flex align-items-center">
                                    <i class="ki-outline ki-entrance-left fs-3 text-success me-2"></i>
                                    <div class="fs-4 fw-bold text-gray-800">{{ number_format($stats['total_logins']) }}
                                    </div>
                                </div>
                                <div class="fw-semibold fs-7 text-gray-500">Total Logins</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border border-gray-300 border-dashed rounded py-3 px-4 h-100">
                                <div class="d-flex align-items-center">
                                    <i class="ki-outline ki-time fs-3 text-warning me-2"></i>
                                    <div class="fs-6 fw-bold text-gray-800">
                                        {{ $stats['last_login']?->format('d M, Y h:i A') ?? 'Never' }}</div>
                                </div>
                                <div class="fw-semibold fs-7 text-gray-500">Last Login</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border border-gray-300 border-dashed rounded py-3 px-4 h-100">
                                <div class="d-flex align-items-center">
                                    <i class="ki-outline ki-calendar fs-3 text-info me-2"></i>
                                    <div class="fs-6 fw-bold text-gray-800">
                                        {{ $stats['member_since']?->format('d M, Y') ?? '—' }}</div>
                                </div>
                                <div class="fw-semibold fs-7 text-gray-500">Member Since</div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold mt-5">
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-5 me-md-10 py-5 active" data-bs-toggle="tab"
                                href="#kt_tab_activity_log">
                                <i class="ki-outline ki-abstract-26 fs-4 me-2"></i>Activity Log
                            </a>
                        </li>
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 py-5" data-bs-toggle="tab"
                                href="#kt_tab_login_activity">
                                <i class="ki-outline ki-shield-tick fs-4 me-2"></i>Login Activity
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="tab-content">
        @include('users.partials.activity-log-card', [
            'tabId' => 'kt_tab_activity_log',
            'active' => true,
            'tableId' => 'kt_user_activity_table',
            'searchAttr' => 'user-activity',
            'menuId' => 'kt_user_activity_filter_menu',
        ])
        @include('users.partials.login-activity-card', [
            'tabId' => 'kt_tab_login_activity',
            'active' => false,
            'tableId' => 'kt_user_login_table',
            'searchAttr' => 'user-login',
            'menuId' => 'kt_user_login_filter_menu',
        ])
    </div>

    @include('users.partials.edit-user-modal')
    @include('users.partials.reset-password-modal')
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaUserShowConfig = {
            activitiesUrl: "{{ route('users.activities', $user->id) }}",
            loginActivitiesUrl: "{{ route('users.login-activities', $user->id) }}",
            activitySearchAttr: 'user-activity',
            loginSearchAttr: 'user-login',
            activityTableId: 'kt_user_activity_table',
            loginTableId: 'kt_user_login_table',
            activityMenuId: 'kt_user_activity_filter_menu',
            loginMenuId: 'kt_user_login_filter_menu'
        };
        // Routes reused by edit + reset-password modals on this page.
        var BidaUserRoutes = {
            json: "{{ route('users.json', ':id') }}",
            update: "{{ route('users.update', ':id') }}",
            resetPassword: "{{ route('users.password', ':id') }}",
            placeholder: "{{ asset('img/male-placeholder.png') }}"
        };
    </script>
    <script src="{{ asset('js/users/show.js') }}"></script>
@endpush
