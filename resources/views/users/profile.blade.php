@extends('layouts.app')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/users/profile.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'My Profile')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">My Profile</h1>
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
        $isAdmin = auth()->user()->isAdmin();
    @endphp

    {{-- Profile Card --}}
    <div class="card mb-5 mb-xl-10">
        <div class="card-body pt-9 pb-0">
            <div class="d-flex flex-column flex-sm-row flex-wrap flex-sm-nowrap">
                {{-- Avatar --}}
                <div class="me-0 me-sm-7 mb-4 text-center text-sm-start">
                    <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative d-inline-block">
                        <img src="{{ $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png') }}"
                            alt="{{ $user->name }}" class="w-100" />
                        <div
                            class="position-absolute translate-middle bottom-0 start-100 mb-6 rounded-circle border border-4 border-body h-20px w-20px {{ $user->is_active ? 'bg-success' : 'bg-gray-400' }}">
                        </div>
                    </div>
                </div>

                {{-- Info --}}
                <div class="flex-grow-1">
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-center align-items-md-start mb-2">
                        <div class="d-flex flex-column text-center text-md-start mb-4 mb-md-0">
                            <div class="d-flex flex-column flex-sm-row align-items-center mb-2">
                                <span class="text-gray-900 fs-2 fw-bold me-2">{{ $user->name }}</span>
                                <span class="badge {{ $roleBadge }} fw-bold">{{ $roleName }}</span>
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

                        {{-- Actions --}}
                        <div class="d-flex flex-column flex-sm-row gap-2 my-2 my-md-4">
                            @if ($isAdmin)
                                <button type="button" class="btn btn-sm btn-light-primary" id="btn_edit_profile">
                                    <i class="ki-outline ki-pencil fs-4 me-1"></i>Edit Profile
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-light-primary" id="btn_change_photo">
                                    <i class="ki-outline ki-picture fs-4 me-1"></i>Change Photo
                                </button>
                            @endif
                            <button type="button" class="btn btn-sm btn-light-warning" id="btn_change_password">
                                <i class="ki-outline ki-lock fs-4 me-1"></i>Change Password
                            </button>
                        </div>
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

    {{-- Tab Content (reuses the same partials as the user show page) --}}
    <div class="tab-content">
        @include('users.partials.activity-log-card', [
            'tabId' => 'kt_tab_activity_log',
            'active' => true,
            'tableId' => 'kt_profile_activity_table',
            'searchAttr' => 'profile-activity',
            'menuId' => 'kt_profile_activity_filter_menu',
        ])
        @include('users.partials.login-activity-card', [
            'tabId' => 'kt_tab_login_activity',
            'active' => false,
            'tableId' => 'kt_profile_login_table',
            'searchAttr' => 'profile-login',
            'menuId' => 'kt_profile_login_filter_menu',
        ])
    </div>

    {{-- Password Modal --}}
    <div class="modal fade" id="kt_modal_password" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header pb-0 border-0 justify-content-end">
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-10 pt-0 pb-15">
                    <div class="text-center mb-13">
                        <h1 class="mb-3">Change Password</h1>
                        <div class="text-muted fw-semibold fs-5">Update your account password</div>
                    </div>

                    <form id="kt_modal_password_form" class="form" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="fv-row mb-8">
                            <label class="required fw-semibold fs-6 mb-2">New Password</label>
                            <div class="position-relative">
                                <input type="password" name="new_password" id="modal_password_new"
                                    class="form-control form-control-solid" placeholder="Enter new password"
                                    autocomplete="new-password" />
                                <span
                                    class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2 toggle-password"
                                    data-target="modal_password_new">
                                    <i class="ki-outline ki-eye fs-3"></i>
                                </span>
                            </div>
                        </div>

                        <div class="fv-row mb-8">
                            <label class="required fw-semibold fs-6 mb-2">Confirm Password</label>
                            <div class="position-relative">
                                <input type="password" name="new_password_confirmation" id="modal_password_confirm"
                                    class="form-control form-control-solid" placeholder="Confirm new password"
                                    autocomplete="new-password" />
                                <span
                                    class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2 toggle-password"
                                    data-target="modal_password_confirm">
                                    <i class="ki-outline ki-eye fs-3"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mb-8">
                            <div class="fs-6 fw-semibold text-muted mb-2">Password Strength</div>
                            <div id="modal_password_strength_text" class="fw-bold fs-5 mb-2"></div>
                            <div class="progress h-8px">
                                <div id="modal_password_strength_bar" class="progress-bar" role="progressbar"
                                    style="width: 0%;"></div>
                            </div>
                            <div class="text-muted fs-7 mt-2">
                                At least 8 characters, one uppercase, one lowercase, one number, and one special character.
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning" id="btn_submit_password" disabled>
                                <span class="indicator-label">Update Password</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Photo Modal (non-admin) --}}
    @unless ($isAdmin)
        <div class="modal fade" id="kt_modal_photo" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
            data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered mw-500px">
                <div class="modal-content">
                    <div class="modal-header pb-0 border-0 justify-content-end">
                        <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                    </div>
                    <div class="modal-body scroll-y mx-5 mx-xl-10 pt-0 pb-15">
                        <div class="text-center mb-13">
                            <h1 class="mb-3">Change Profile Photo</h1>
                            <div class="text-muted fw-semibold fs-5">Upload a new profile picture</div>
                        </div>

                        <form id="kt_modal_photo_form" class="form" enctype="multipart/form-data">
                            @csrf
                            <div class="fv-row mb-8">
                                <div class="d-flex flex-center">
                                    <div class="image-input image-input-outline image-input-placeholder"
                                        data-kt-image-input="true" id="kt_photo_upload">
                                        <div class="image-input-wrapper w-150px h-150px"
                                            style="background-image: url('{{ $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png') }}')">
                                        </div>
                                        <label
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                            title="Change photo">
                                            <i class="ki-outline ki-pencil fs-7"></i>
                                            <input type="file" name="photo" accept=".png, .jpg, .jpeg"
                                                id="photo_input" />
                                            <input type="hidden" name="remove_photo" id="photo_remove" value="0" />
                                        </label>
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                            title="Cancel photo">
                                            <i class="ki-outline ki-cross fs-2"></i>
                                        </span>
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                            title="Remove photo">
                                            <i class="ki-outline ki-cross fs-2"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-text text-center mt-3">Allowed: png, jpg, jpeg. Max 100KB.</div>
                            </div>
                            <div class="text-center">
                                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="btn_submit_photo">
                                    <span class="indicator-label">Save Photo</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endunless

    {{-- Profile Modal (admin) --}}
    @if ($isAdmin)
        <div class="modal fade" id="kt_modal_profile" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
            data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered mw-650px">
                <div class="modal-content">
                    <div class="modal-header pb-0 border-0 justify-content-end">
                        <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                    </div>
                    <div class="modal-body scroll-y mx-5 mx-xl-10 pt-0 pb-15">
                        <div class="text-center mb-13">
                            <h1 class="mb-3">Edit Profile</h1>
                            <div class="text-muted fw-semibold fs-5">Update your personal information</div>
                        </div>

                        <form id="kt_modal_profile_form" class="form" enctype="multipart/form-data">
                            @csrf

                            <div class="fv-row mb-8">
                                <label class="fs-6 fw-semibold mb-4 d-block">Profile Photo</label>
                                <div class="image-input image-input-outline image-input-placeholder"
                                    data-kt-image-input="true" id="kt_profile_photo_upload">
                                    <div class="image-input-wrapper w-125px h-125px"
                                        style="background-image: url('{{ $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png') }}')">
                                    </div>
                                    <label
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                        title="Change photo">
                                        <i class="ki-outline ki-pencil fs-7"></i>
                                        <input type="file" name="photo" accept=".png, .jpg, .jpeg"
                                            id="profile_photo_input" />
                                        <input type="hidden" name="remove_photo" id="profile_photo_remove"
                                            value="0" />
                                    </label>
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                        title="Cancel photo">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </span>
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                        title="Remove photo">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </span>
                                </div>
                                <div class="form-text">Allowed: png, jpg, jpeg. Max 100KB.</div>
                            </div>

                            <div class="row g-9 mb-8">
                                <div class="col-md-6 fv-row">
                                    <label class="required fs-6 fw-semibold mb-2">Full Name</label>
                                    <input type="text" name="name" id="profile_name" value="{{ $user->name }}"
                                        class="form-control form-control-solid" placeholder="Enter full name" />
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                </div>
                                <div class="col-md-6 fv-row">
                                    <label class="fs-6 fw-semibold mb-2">Designation</label>
                                    <input type="text" name="designation" id="profile_designation"
                                        value="{{ $user->designation }}" class="form-control form-control-solid"
                                        placeholder="e.g. Assistant Director" />
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row g-9 mb-8">
                                <div class="col-md-6 fv-row">
                                    <label class="required fs-6 fw-semibold mb-2">Email</label>
                                    <input type="email" name="email" id="profile_email" value="{{ $user->email }}"
                                        class="form-control form-control-solid" placeholder="Enter email" />
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                </div>
                                <div class="col-md-6 fv-row">
                                    <label class="fs-6 fw-semibold mb-2">Mobile Number</label>
                                    <input type="text" name="mobile_number" id="profile_mobile"
                                        value="{{ $user->mobile_number }}" class="form-control form-control-solid"
                                        placeholder="01XXXXXXXXX" maxlength="11" />
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="text-center">
                                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="btn_submit_profile">
                                    <span class="indicator-label">Save Changes</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var ProfileConfig = {
            userPhotoUrl: "{{ $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png') }}",
            placeholderUrl: "{{ asset('img/male-placeholder.png') }}",
            passwordResetUrl: "{{ route('users.password.reset') }}",
            profileUpdateUrl: "{{ route('users.profile.update') }}",
            activitiesUrl: "{{ route('users.profile.activities') }}",
            loginActivitiesUrl: "{{ route('users.profile.login-activities') }}",
            isAdmin: {{ $isAdmin ? 'true' : 'false' }},
            // Selectors shared with the reusable feed-table partials.
            activityTableId: 'kt_profile_activity_table',
            loginTableId: 'kt_profile_login_table',
            activitySearchAttr: 'profile-activity',
            loginSearchAttr: 'profile-login',
            activityMenuId: 'kt_profile_activity_filter_menu',
            loginMenuId: 'kt_profile_login_filter_menu'
        };
    </script>
    <script src="{{ asset('js/users/profile.js') }}"></script>
@endpush
