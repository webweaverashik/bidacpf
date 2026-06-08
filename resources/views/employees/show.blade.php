@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/employees/show.css') }}" rel="stylesheet" type="text/css" />
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
    <!--begin::Layout-->
    <div class="d-flex flex-column flex-xl-row">
        <!--begin::Sidebar-->
        <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
            <!--begin::Card-->
            <div class="card card-flush mb-0
            @if (optional($student->studentActivation)->active_status === 'inactive') border border-dashed border-danger
            @elseif($student->studentActivation == null) border border-dashed border-info @endif"
                data-kt-sticky="false" data-kt-sticky-name="student-summary" data-kt-sticky-offset="{default: false, lg: 0}"
                data-kt-sticky-width="{lg: '250px', xl: '350px'}" data-kt-sticky-left="auto" data-kt-sticky-top="100px"
                data-kt-sticky-animation="false" data-kt-sticky-zindex="95">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>Student Info</h2>
                    </div>
                    <!--end::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::More options-->
                        <a href="#" class="btn btn-sm btn-light btn-icon" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end">
                            <i class="ki-outline ki-dots-horizontal fs-3"></i>
                        </a>
                        <!--begin::Menu-->
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-6 w-175px py-4"
                            data-kt-menu="true">
                            @can('students.deactivate')
                                <div class="menu-item px-3">
                                    @if (optional($student->studentActivation)->active_status == 'active')
                                        <a href="#" class="menu-link text-hover-warning px-3" data-bs-toggle="modal"
                                            data-bs-target="#kt_toggle_activation_student_modal"
                                            data-student-unique-id="{{ $student->student_unique_id }}"
                                            data-student-name="{{ $student->name }}" data-student-id="{{ $student->id }}"
                                            data-active-status="{{ optional($student->studentActivation)->active_status }}">
                                            <i class="bi bi-person-slash fs-2 me-2"></i> Deactivate
                                        </a>
                                    @else
                                        <a href="#" class="menu-link text-hover-success px" data-bs-toggle="modal"
                                            data-bs-target="#kt_toggle_activation_student_modal"
                                            data-student-unique-id="{{ $student->student_unique_id }}"
                                            data-student-name="{{ $student->name }}" data-student-id="{{ $student->id }}"
                                            data-active-status="{{ optional($student->studentActivation)->active_status }}">
                                            <i class="bi bi-person-check fs-3 me-2"></i> Activate
                                        </a>
                                    @endif
                                </div>
                            @endcan
                            @can('students.edit')
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="{{ route('students.edit', $student->id) }}"
                                        class="menu-link text-hover-primary px-3">
                                        <i class="ki-outline ki-pencil fs-3 me-2"></i> Edit Student
                                    </a>
                                </div>
                                <!--end::Menu item-->
                            @endcan
                            @can('students.delete')
                                <!--begin::Menu item-->
                                {{-- <div class="menu-item px-3">
                            <a href="#" class="menu-link text-hover-danger px-3 delete-student" data-student-id="{{ $student->id }}">
                                <i class="bi bi-trash fs-3 me-2"></i> Delete
                            </a>
                        </div> --}}
                                <!--end::Menu item-->
                            @endcan
                        </div>
                        <!--end::Menu-->
                        <!--end::More options-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0 fs-6">
                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Details-->
                        <div class="d-flex align-items-center">
                            <!--begin::Avatar-->
                            <div class="symbol symbol-60px symbol-circle me-3">
                                <img src="{{ $student->photo_url ? asset($student->photo_url) : asset($student->gender == 'male' ? 'img/boy.png' : 'img/girl.png') }}"
                                    alt="{{ $student->name }}" />
                            </div>
                            <!--end::Avatar-->
                            <!--begin::Info-->
                            <div class="d-flex flex-column">
                                <!--begin::Name-->
                                <span class="fs-4 fw-bold text-gray-900 me-2">{{ $student->name }}</span>
                                <!--end::Name-->
                                <!--begin::Student ID-->
                                <span class="fw-bold text-gray-600">{{ $student->student_unique_id }}</span>
                                <!--end::Student ID-->
                            </div>
                            <!--end::Info-->
                        </div>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->
                    <!--begin::Seperator-->
                    <div class="separator separator-dashed mb-7"></div>
                    <!--end::Seperator-->
                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Title-->
                        <h5 class="mb-4">Academic Info</h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <div class="mb-0">
                            <!--begin::Details-->
                            <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Branch:</td>
                                    <td class="text-gray-800">{{ $student->branch->branch_name }}</td>
                                </tr>
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Class:</td>
                                    <td class="text-gray-800">
                                        <a href="{{ route('classnames.show', $student->class_id) }}" target="_blank"
                                            class="text-gray-800 text-hover-primary">
                                            {{ $student->class->name }} ({{ $student->class->class_numeral }})</a>
                                    </td>
                                </tr>
                                <!--end::Row-->
                                <!--begin::Row-->
                                @if ($student->academic_group != 'General')
                                    <tr>
                                        <td class="text-gray-500">Group:</td>
                                        <td>
                                            @php
                                                $badge =
                                                    [
                                                        'Science' => 'info',
                                                        'Commerce' => 'primary',
                                                        'Arts' => 'warning',
                                                    ][$student->academic_group] ?? null;
                                            @endphp

                                            @if ($badge)
                                                <span
                                                    class="badge badge-pill badge-{{ $badge }}">{{ $student->academic_group }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                <!--end::Row-->
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Batch:</td>
                                    <td>{{ $student->batch->name }}</td>
                                </tr>
                                <!--end::Row-->
                            </table>
                            <!--end::Details-->
                        </div>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->
                    <!--begin::Seperator-->
                    <div class="separator separator-dashed mb-7"></div>
                    <!--end::Seperator-->
                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Title-->
                        <h5 class="mb-4">Payment Info</h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <div class="mb-0">
                            <!--begin::Details-->
                            <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Payment Style:</td>
                                    <td class="text-gray-800">
                                        @if ($student->payments)
                                            {{ ucfirst($student->payments->payment_style) }}
                                        @else
                                            Current
                                        @endif
                                    </td>
                                </tr>
                                <!--end::Row-->
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Monthly Fee:</td>
                                    <td>
                                        @if ($student->payments)
                                            ৳{{ $student->payments->tuition_fee }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <!--end::Row-->
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Due Date:</td>
                                    <td class="text-gray-800">1 to @if ($student->payments)
                                            {{ $student->payments->due_date }}
                                        @else
                                            7
                                        @endif
                                    </td>
                                </tr>
                                <!--end::Row-->
                                <!--begin::Row-->
                                <tr>
                                    <td class="text-gray-500">Reference:</td>
                                    @if ($student->reference && $student->reference->referer)
                                        <td class="text-gray-800">
                                            @php
                                                $referer = $student->reference->referer;
                                                $type = strtolower($student->reference->referer_type);
                                                $route =
                                                    $type === 'student'
                                                        ? route('students.show', $referer->id)
                                                        : route('teachers.show', $referer->id);
                                            @endphp
                                            <a href="{{ $route }}"
                                                class="fw-bold text-gray-800 text-hover-primary">
                                                {{ $referer->name ?? 'N/A' }}
                                                @if ($type === 'student')
                                                    ({{ $referer->student_unique_id }})
                                                @endif
                                            </a>
                                    @endif
                                    </td>
                                </tr>
                                <!--end::Row-->
                            </table>
                            <!--end::Details-->
                        </div>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->
                    <!--begin::Seperator-->
                    <div class="separator separator-dashed mb-7"></div>
                    <!--end::Seperator-->
                    <!--begin::Section-->
                    <div class="mb-0">
                        <!--begin::Title-->
                        <h5 class="mb-4">Activation Details</h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Status:</td>
                                <td>
                                    @php $status = $student->studentActivation?->active_status; @endphp
                                    @if ($status === 'inactive')
                                        <span class="badge badge-danger rounded-pill">{{ ucfirst($status) }}</span>
                                    @elseif($status === 'active')
                                        <span class="badge badge-success rounded-pill">{{ ucfirst($status) }}</span>
                                    @else
                                        <span class="badge badge-info rounded-pill">Pending Approval</span>
                                    @endif
                                </td>
                            </tr>
                            <!--end::Row-->
                            <!--begin::Row-->
                            <tr class="">
                                @if (optional($student->studentActivation)->active_status == 'active')
                                    <td class="text-gray-500">Active Since:</td>
                                @elseif(optional($student->studentActivation)->active_status == 'inactive')
                                    <td class="text-gray-500">Inactive Since:</td>
                                @endif
                                @if ($student->studentActivation)
                                    <td class="text-gray-800">
                                        {{ $student->studentActivation->created_at->diffForHumans() }}
                                        <span class="ms-1" data-bs-toggle="tooltip"
                                            title="{{ $student->studentActivation->created_at->format('h:i:s A, d-M-Y') }}">
                                            <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                        </span>
                                    </td>
                                @endif
                            </tr>
                            <!--end::Row-->
                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Admission Date:</td>
                                <td class="text-gray-800">
                                    {{ $student->created_at->format('d-M-Y') }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $student->created_at->format('h:i:s A, d-M-Y') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>
                                </td>
                            </tr>
                            <!--end::Row-->
                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Remarks:</td>
                                <td class="text-gray-800">
                                    @if ($student->remarks)
                                        {{ $student->remarks }}
                                    @else
                                        <span class="text-gray-600">-</span>
                                    @endif
                                </td>
                            </tr>
                            <!--end::Row-->
                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Admitted By:</td>
                                <td class="text-gray-800">
                                    @if ($student->createdBy)
                                        <a href="{{ route('settlements.show', $student->created_by) }}" target="_blank"
                                            class="text-gray-800 text-hover-primary">
                                            {{ $student->createdBy->name }}
                                        </a>
                                    @else
                                        System
                                    @endif
                                </td>
                            </tr>
                            <!--end::Row-->
                        </table>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Sidebar-->

        <!--begin::Content-->
        <div class="flex-lg-row-fluid ms-lg-10">
            <!--begin:::Tabs-->
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                        href="#kt_student_view_personal_info_tab">
                        <i class="ki-outline ki-home fs-3 me-2"></i> Personal Info
                    </a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_student_view_enrollment_tab">
                        <i class="ki-outline ki-book-open fs-3 me-2"></i> Enrollment
                    </a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_transactions_tab">
                        <i class="ki-outline ki-credit-cart fs-3 me-2"></i> Transactions
                    </a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_sheets_tab">
                        <i class="ki-outline ki-some-files fs-3 me-2"></i> Sheets
                    </a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_attendance_tab">
                        <i class="ki-outline ki-calendar fs-3 me-2"></i> Attendance
                    </a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_activity_tab">
                        <i class="ki-outline ki-save-2 fs-3 me-2"></i> Activity
                    </a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item ms-auto">
                    <!--begin::Action menu-->
                    <a href="#" class="btn btn-primary ps-7" data-kt-menu-trigger="click"
                        data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                        Actions
                        <i class="ki-outline ki-down fs-2 me-0"></i>
                    </a>
                    <!--begin::Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 w-250px fs-6"
                        data-kt-menu="true">
                        <!--begin::Menu item-->
                        <div class="menu-item px-5">
                            <div class="menu-content text-muted pb-2 px-5 fs-7 text-uppercase">Sheet</div>
                        </div>
                        <!--end::Menu item-->
                        @can('notes.distribute')
                            <div class="menu-item px-5">
                                <a href="{{ route('notes.single.create') }}" class="menu-link text-hover-primary px-5">
                                    <i class="ki-outline ki-note-2 fs-2 me-2"></i> Note Distribution
                                </a>
                            </div>
                        @endcan
                        <!--begin::Menu separator-->
                        <div class="separator my-3"></div>
                        <!--end::Menu separator-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-5">
                            <div class="menu-content text-muted pb-2 px-5 fs-7 text-uppercase">Account</div>
                        </div>
                        <!--end::Menu item-->
                        @can('students.deactivate')
                            <!--begin::Menu item-->
                            <div class="menu-item px-5">
                                @if (optional($student->studentActivation)->active_status == 'active')
                                    <a href="#" class="menu-link px-5 text-hover-warning" data-bs-toggle="modal"
                                        data-bs-target="#kt_toggle_activation_student_modal"
                                        data-student-unique-id="{{ $student->student_unique_id }}"
                                        data-student-name="{{ $student->name }}" data-student-id="{{ $student->id }}"
                                        data-active-status="{{ optional($student->studentActivation)->active_status }}">
                                        <i class="bi bi-person-slash fs-2 me-2"></i> Deactivate Student
                                    </a>
                                @else
                                    <a href="#" class="menu-link px-5 text-hover-success" data-bs-toggle="modal"
                                        data-bs-target="#kt_toggle_activation_student_modal"
                                        data-student-unique-id="{{ $student->student_unique_id }}"
                                        data-student-name="{{ $student->name }}" data-student-id="{{ $student->id }}"
                                        data-active-status="{{ optional($student->studentActivation)->active_status }}">
                                        <i class="bi bi-person-check fs-2 me-2"></i> Activate Student
                                    </a>
                                @endif
                            </div>
                            <!--end::Menu item-->
                        @endcan
                        @can('students.form.download')
                            @if (optional($student->studentActivation)->active_status == 'active')
                                <div class="menu-item px-5">
                                    <a href="{{ route('students.download', $student->id) }}" target="_blank"
                                        class="menu-link text-hover-primary px-5">
                                        <i class="bi bi-download fs-2 me-2"></i> Download Form
                                    </a>
                                </div>
                            @endif
                        @endcan
                        @can('students.edit')
                            <!--begin::Menu item-->
                            <div class="menu-item px-5 my-1">
                                <a href="{{ route('students.edit', $student->id) }}"
                                    class="menu-link px-5 text-hover-primary">
                                    <i class="ki-outline ki-pencil fs-3 me-2"></i> Edit Student
                                </a>
                            </div>
                            <!--end::Menu item-->
                        @endcan
                        @can('students.delete')
                            <!--begin::Menu item-->
                            {{-- <div class="menu-item px-5">
                        <a href="#" class="menu-link text-hover-danger px-5 delete-student" data-student-id="{{ $student->id }}">
                            <i class="bi bi-trash fs-3 me-2"></i> Delete Student
                        </a>
                    </div> --}}
                            <!--end::Menu item-->
                        @endcan
                    </div>
                    <!--end::Menu-->
                    <!--end::Action Menu-->
                </li>
                <!--end:::Tab item-->
            </ul>
            <!--end:::Tabs-->

            <!--begin:::Tab content-->
            <div class="tab-content" id="myTabContent">
                <!--begin:::Personal Info Tab pane-->
                <div class="tab-pane fade show active" id="kt_student_view_personal_info_tab" role="tabpanel">
                    <!--begin::Personal Info-->
                    <div class="card mb-5 mb-xl-10" id="kt_profile_details_view">
                        <!--begin::Card header-->
                        <div class="card-header cursor-pointer">
                            <!--begin::Card title-->
                            <div class="card-title m-0">
                                <h3 class="fw-bold m-0">{{ $student->name }}'s Info</h3>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--begin::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body p-9">
                            <!--begin::Row-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Full Name</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">{{ $student->name }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->
                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Gender</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <span class="fw-bold text-gray-800 fs-6">
                                        @if ($student->gender == 'male')
                                            <i class="las la-mars fs-4"></i>
                                        @elseif($student->gender == 'female')
                                            <i class="las la-venus fs-4"></i>
                                        @endif
                                        {{ ucfirst($student->gender) }}
                                    </span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Blood Group</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <span class="fw-bold text-gray-800 fs-6">{{ $student->blood_group }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Date of Birth</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <span
                                        class="fw-bold text-gray-800 fs-6">{{ optional($student->date_of_birth)->format('d-M-Y') }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Home Address</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <span class="fw-bold text-gray-800 fs-6">{{ $student->home_address }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Religion</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <span class="fw-bold text-gray-800 fs-6">{{ $student->religion }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Email</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 d-flex align-items-center">
                                    <span class="fw-bold fs-6 text-gray-800 me-2">{{ $student->email }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Phone (Home)</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 d-flex align-items-center">
                                    <span
                                        class="fw-bold fs-6 text-gray-850 me-2">{{ $student->mobileNumbers->where('number_type', 'home')->pluck('mobile_number')->implode('') }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Phone (SMS)</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 d-flex align-items-center">
                                    <span
                                        class="fw-bold fs-6 text-gray-800 me-2">{{ $student->mobileNumbers->where('number_type', 'sms')->pluck('mobile_number')->implode('') }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Phone (WhatsApp)</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 d-flex align-items-center">
                                    <span
                                        class="fw-bold fs-6 text-gray-800 me-2">{{ $student->mobileNumbers->where('number_type', 'whatsapp')->pluck('mobile_number')->implode('') }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Institution
                                    <span class="ms-1" data-bs-toggle="tooltip" title="School or College Name">
                                        <i class="ki-outline ki-information fs-7"></i>
                                    </span>
                                </label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">{{ $student->institution->name }} (EIIN:
                                        {{ $student->institution->eiin_number }})</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Personal Info-->

                    <!--begin::Guardians-->
                    <div class="card mb-5 mb-xl-10">
                        <!--begin::Card header-->
                        <div class="card-header">
                            <!--begin::Title-->
                            <div class="card-title">
                                <h3>Guardian Info</h3>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body">
                            <!--begin::Guardians-->
                            <div class="row gx-9 gy-6">
                                @foreach ($student->guardians as $guardian)
                                    <!--begin::Col-->
                                    <div class="col-xl-6">
                                        <!--begin::Guardian-->
                                        <div class="card card-dashed h-xl-100 flex-row flex-wrap p-6 align-items-center">
                                            <!--begin::Photo-->
                                            <div class="symbol symbol-60px me-5">
                                                <img src="{{ $guardian->photo_url ?? asset($guardian->gender == 'male' ? 'img/male.png' : 'img/female.png') }}"
                                                    alt="{{ $guardian->name }}">
                                            </div>
                                            <!--end::Photo-->
                                            <!--begin::Details-->
                                            <div class="d-flex flex-column py-2">
                                                <div class="d-flex align-items-center fs-5 fw-bold mb-2">
                                                    {{ $guardian->name }}</div>
                                                <div class="fs-6 fw-semibold text-gray-600">
                                                    {{ $guardian->mobile_number }}<br>
                                                    {{ ucfirst($guardian->relationship) }}<br>
                                                </div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Guardian-->
                                    </div>
                                    <!--end::Col-->
                                @endforeach
                            </div>
                            <!--end::Guardians-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Guardians-->

                    <!--begin::Siblings-->
                    @if ($student->siblings->count() > 0)
                        <div class="card mb-5 mb-xl-10">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <!--begin::Title-->
                                <div class="card-title">
                                    <h3>Sibling Info</h3>
                                </div>
                                <!--end::Title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body">
                                <!--begin::Guardians-->
                                <div class="row gx-9 gy-6">
                                    @foreach ($student->siblings as $sibling)
                                        <!--begin::Col-->
                                        <div class="col-xl-6">
                                            <!--begin::Guardian-->
                                            <div
                                                class="card card-dashed h-xl-100 flex-row flex-wrap p-6 align-items-center">
                                                <!--begin::Photo-->
                                                <div class="symbol symbol-60px me-5">
                                                    <img src="{{ asset($sibling->relationship == 'brother' ? 'img/boy.png' : 'img/girl.png') }}"
                                                        alt="{{ $sibling->name }}">
                                                </div>
                                                <!--end::Photo-->
                                                <!--begin::Details-->
                                                <div class="d-flex flex-column py-2">
                                                    <div class="d-flex align-items-center fs-5 fw-bold mb-2">
                                                        {{ $sibling->name }}
                                                        <span
                                                            class="ms-5 text-gray-600 fs-7 fw-semibold">{{ ucfirst($sibling->relationship) }}</span>
                                                    </div>
                                                    <div class="fs-6 fw-semibold text-gray-600">
                                                        Class/Age: {{ $sibling->class }}<br>
                                                        Year: {{ $sibling->year }}<br>
                                                        School: {{ $sibling->institution_name }}
                                                    </div>
                                                </div>
                                                <!--end::Details-->
                                            </div>
                                            <!--end::Guardian-->
                                        </div>
                                        <!--end::Col-->
                                    @endforeach
                                </div>
                                <!--end::Guardians-->
                            </div>
                            <!--end::Card body-->
                        </div>
                    @endif
                    <!--end::Siblings-->
                </div>
                <!--end:::Personal Info Tab pane-->

                <!--begin:::Enrolled Subjects Tab pane-->
                <div class="tab-pane fade" id="kt_student_view_enrollment_tab" role="tabpanel">
                    <!--begin::Card - Regular Class Subjects-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>{{ $student->class->name }} ({{ $student->class->class_numeral }}) @if ($student->academic_group != 'General')
                                        - {{ $student->academic_group }}
                                    @endif
                                </h2>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-0">
                            <!--begin::Table wrapper-->
                            @php
                                // Organize subjects into three categories
                                // 1. Compulsory General - academic_group is 'General' and subject_type is 'compulsory'
                                $compulsoryGeneral = $student->subjectsTaken->filter(function ($item) {
                                    return $item->subject->academic_group === 'General' &&
                                        $item->subject->subject_type === 'compulsory' &&
                                        !$item->is_4th_subject;
                                });

                                // 2. Science/Commerce Subjects - All subjects (compulsory OR optional) that are NOT 4th subject
                                $groupSubjects = $student->subjectsTaken->filter(function ($item) {
                                    return in_array($item->subject->academic_group, ['Science', 'Commerce']) &&
                                        !$item->is_4th_subject;
                                });

                                // 3. 4th Subject - is_4th_subject = true
                                $fourthSubject = $student->subjectsTaken->filter(function ($item) {
                                    return $item->is_4th_subject === true;
                                });

                                // Get the academic group name for display
                                $groupName =
                                    $groupSubjects->first()?->subject->academic_group ?? $student->academic_group;

                                // Check if any subjects exist
                                $hasAnySubjects =
                                    $compulsoryGeneral->isNotEmpty() ||
                                    $groupSubjects->isNotEmpty() ||
                                    $fourthSubject->isNotEmpty();
                            @endphp

                            @if ($hasAnySubjects)
                                <div class="row">
                                    {{-- 1. Compulsory (General) --}}
                                    @if ($compulsoryGeneral->isNotEmpty())
                                        <div class="col-12 mb-6">
                                            <h5 class="fw-bold text-gray-800 mb-4">
                                                <span class="bullet bullet-dot bg-success me-2 h-10px w-10px"></span>
                                                Compulsory (General)
                                            </h5>
                                            <div class="row">
                                                @foreach ($compulsoryGeneral as $subjectTaken)
                                                    <div class="col-md-3 mb-3">
                                                        <h6 class="text-gray-650 text-gray-800">
                                                            <i class="bi bi-check2-circle fs-3 text-success"></i>
                                                            {{ $subjectTaken->subject->name ?? 'N/A' }}
                                                        </h6>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- 2. Science/Commerce Subjects --}}
                                    @if ($groupSubjects->isNotEmpty())
                                        <div class="col-12 mb-6">
                                            <h5 class="fw-bold text-gray-800 mb-4">
                                                <span class="bullet bullet-dot bg-primary me-2 h-10px w-10px"></span>
                                                {{ $groupName }} Subjects
                                            </h5>
                                            <div class="row">
                                                @foreach ($groupSubjects as $subjectTaken)
                                                    <div class="col-md-3 mb-3">
                                                        <h6 class="text-gray-750 text-gray-800">
                                                            <i class="bi bi-check2-circle fs-3 text-primary"></i>
                                                            {{ $subjectTaken->subject->name ?? 'N/A' }}
                                                        </h6>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- 3. 4th Subject (Optional) --}}
                                    @if ($fourthSubject->isNotEmpty())
                                        <div class="col-12 mb-4">
                                            <h5 class="fw-bold text-gray-800 mb-4">
                                                <span class="bullet bullet-dot bg-info me-2 h-10px w-10px"></span> 4th
                                                Subject
                                            </h5>
                                            <div class="row">
                                                @foreach ($fourthSubject as $subjectTaken)
                                                    <div class="col-md-3 mb-3">
                                                        <h6 class="text-gray-750 text-gray-800">
                                                            <i class="bi bi-check2-circle fs-3 text-info"></i>
                                                            {{ $subjectTaken->subject->name ?? 'N/A' }}
                                                        </h6>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <!--begin::Empty State-->
                                <div class="row mb-6">
                                    <div class="col-12">
                                        <div class="card empty-state-card p-10 text-center">
                                            <div class="mb-4">
                                                <i class="ki-outline ki-book-open fs-3x text-gray-400"></i>
                                            </div>
                                            <h4 class="text-gray-600 fw-semibold mb-2">No Subjects Enrolled</h4>
                                            <p class="text-gray-500 fs-6 mb-0">This student has not been enrolled in any
                                                regular class subjects yet.</p>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Empty State-->
                            @endif
                            <!--end::Table wrapper-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card - Regular Class Subjects-->

                    <!--begin::Card - Special Class Enrollment-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2><i class="ki-outline ki-abstract-26 fs-2 text-primary me-2"></i>Special Class
                                    Enrollment</h2>
                            </div>
                            <!--end::Card title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <span class="badge badge-light-primary fs-7">{{ $student->secondaryClasses->count() }}
                                    Enrolled</span>
                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Card header-->

                        <!--begin::Card body-->
                        <div class="card-body py-0 pb-5">
                            @if ($student->secondaryClasses->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                                        <thead>
                                            <tr class="fw-bold text-muted fs-7 text-uppercase">
                                                <th class="min-w-150px">Class Name</th>
                                                <th class="min-w-100px">Payment Type</th>
                                                <th class="min-w-100px">Fee Amount</th>
                                                <th class="min-w-100px">Enrolled At</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fs-6 fw-semibold text-gray-600">
                                            @foreach ($student->secondaryClasses as $enrollment)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div
                                                                class="symbol symbol-40px symbol-circle bg-light-primary me-3">
                                                                <span class="symbol-label">
                                                                    <i class="ki-outline ki-teacher fs-3 text-primary"></i>
                                                                </span>
                                                            </div>
                                                            <div class="d-flex flex-column">
                                                                <a class="text-gray-800 fw-bold text-hover-primary"
                                                                    href="{{ route('classnames.secondary-classes.show', [
                                                                        $enrollment->secondaryClass->class,
                                                                        $enrollment->secondaryClass,
                                                                    ]) }}">
                                                                    {{ $enrollment->secondaryClass->name ?? 'Unknown Class' }}
                                                                </a>

                                                                <span class="text-gray-500 fs-7">
                                                                    {{ $enrollment->secondaryClass->class->name ?? 'N/A' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if (optional($enrollment->secondaryClass)->payment_type === 'monthly')
                                                            <span class="badge badge-light-info">Monthly</span>
                                                        @else
                                                            <span class="badge badge-light-success">One Time</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="text-gray-800">৳{{ number_format($enrollment->amount, 0) }}</span>
                                                    </td>
                                                    <td>
                                                        @if ($enrollment->enrolled_at)
                                                            {{ $enrollment->enrolled_at->format('d-M-Y') }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <!--begin::Empty State-->
                                <div class="text-center py-10">
                                    <div class="mb-4">
                                        <i class="ki-outline ki-abstract-26 fs-3x text-gray-400"></i>
                                    </div>
                                    <h4 class="text-gray-600 fw-semibold mb-2">No Special Classes</h4>
                                    <p class="text-gray-500 fs-6 mb-0">This student is not enrolled in any special classes.
                                    </p>
                                </div>
                                <!--end::Empty State-->
                            @endif
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card - Special Class Enrollment-->
                </div>
                <!--end:::Enrolled Subjects Tab pane-->

                <!--begin:::Transaction Tab pane-->
                <div class="tab-pane fade" id="kt_student_view_transactions_tab" role="tabpanel">
                    <!--begin::Earnings-->
                    <div class="card mb-6 mb-xl-9">
                        <!--begin::Header-->
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>Tuition Fee Payment Summary</h2>
                            </div>
                        </div>
                        <!--end::Header-->
                        <!--begin::Body-->
                        <div class="card-body py-0">
                            <div class="fs-5 fw-semibold text-gray-500 mb-4">
                                Summary of transacted amount of this student.
                            </div>
                            <!--begin::Left Section-->
                            <div class="d-flex flex-wrap flex-stack mb-5">
                                <!--begin::Row-->
                                <div class="d-flex flex-wrap">
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true"
                                                data-kt-countup-value="{{ $student->paymentTransactions->where('is_approved', true)->sum('amount_paid') }}"
                                                data-kt-countup-prefix="৳">0</span>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Total Paid</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-125px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span class="" data-kt-countup="true"
                                                data-kt-countup-value="{{ $student->paymentInvoices->count() }}">0</span>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Invoices</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-warning w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true"
                                                data-kt-countup-value="{{ $student->paymentInvoices->sum('amount_due') }}"
                                                data-kt-countup-prefix="৳">0</span>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Due</span>
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Row-->
                            </div>
                            <!--end::Left Section-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Earnings-->

                    <!--begin::Statements-->
                    <div class="card mb-6 mb-xl-9">
                        <!--begin::Header-->
                        <div class="card-header align-items-center">
                            <!--begin::Title-->
                            <div class="card-title">
                                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x fw-semibold border-0">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab"
                                            href="#kt_tab_pane_invoices">Invoices</a>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab"
                                            href="#kt_tab_pane_transactions">Transactions</a>
                                </ul>
                            </div>
                            <!--end::Title-->
                            <!--begin::Toolbar-->
                            <div class="card-toolbar flex-shrink-0" style="white-space: nowrap;">
                            </div>
                            <!--end::Toolbar-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Card body-->
                        <div class="card-body pb-5 tab-content">
                            <div class="tab-pane fade show active" id="kt_tab_pane_invoices" role="tabpanel">
                                <!--begin::Table-->
                                <table id="kt_student_view_invoices_table"
                                    class="table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4 ucms-table">
                                    <thead class="border-bottom border-gray-200">
                                        <tr class="fw-bold fs-7 text-uppercase gs-0">
                                            <th class="w-25px">#</th>
                                            <th class="w-150px">Invoice No.</th>
                                            <th>Invoice Type</th>
                                            <th>Billing Month</th>
                                            <th>Toal Amount (৳)</th>
                                            <th>Remaining (৳)</th>
                                            <th>Status</th>
                                            <th class="w-100px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($student->paymentInvoices->sortByDesc('created_at') as $invoice)
                                            <tr>
                                                <td>{{ $loop->index + 1 }}</td>
                                                <td><a href="{{ route('invoices.show', $invoice->id) }}"
                                                        target="_blank">{{ $invoice->invoice_number }}</a></td>
                                                <td>{{ $invoice->invoiceType->type_name }}</td>
                                                <td>
                                                    @if (!empty($invoice->month_year) && preg_match('/^(\d{2})_(\d{4})$/', $invoice->month_year, $matches))
                                                        {{ \Carbon\Carbon::create($matches[2], $matches[1], 1)->format('F Y') }}
                                                    @elseif(empty($invoice->month_year) && $invoice->invoiceType?->type_name == 'Special Class Fee')
                                                        <span class="badge badge-primary rounded-pill">One Time</span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>{{ $invoice->total_amount }}</td>
                                                <td>{{ $invoice->amount_due }}</td>
                                                <!--start: Invoice Status Badge-->
                                                @php
                                                    $status = $invoice->status;
                                                    $payment = optional($invoice->student)->payments;
                                                    $dueDate = null;
                                                    $isOverdue = false;

                                                    if ($payment && $payment->due_date && $invoice->month_year) {
                                                        try {
                                                            $monthYearRaw = trim($invoice->month_year);
                                                            if (preg_match('/^\d{2}_\d{4}$/', $monthYearRaw)) {
                                                                $monthYear = \Carbon\Carbon::createFromFormat(
                                                                    'm_Y',
                                                                    $monthYearRaw,
                                                                );
                                                                $dueDate = $monthYear
                                                                    ->copy()
                                                                    ->day((int) $payment->due_date);

                                                                if (
                                                                    in_array($status, ['due', 'partially_paid']) &&
                                                                    now()->toDateString() > $dueDate->toDateString()
                                                                ) {
                                                                    $isOverdue = true;
                                                                }
                                                            }
                                                        } catch (\Exception $e) {
                                                            // Silently ignore
                                                        }
                                                    }
                                                @endphp
                                                <td>
                                                    @if ($status === 'due')
                                                        @if ($isOverdue)
                                                            <span class="badge badge-danger rounded-pill">Overdue</span>
                                                        @else
                                                            <span class="badge badge-warning rounded-pill">Due</span>
                                                        @endif
                                                    @elseif($status === 'partially_paid')
                                                        <span class="badge badge-info rounded-pill">Partial</span>
                                                        @if ($isOverdue)
                                                            <span
                                                                class="badge badge-danger rounded-pill ms-1">Overdue</span>
                                                        @endif
                                                    @elseif($status === 'paid')
                                                        <span class="badge badge-success rounded-pill">Paid</span>
                                                    @endif
                                                </td>
                                                <!--end: Invoice Status Badge-->
                                                <td>
                                                    @if (optional($invoice->student->studentActivation)->active_status == 'active' && $invoice->status == 'due')
                                                        @can('invoices.edit')
                                                            <a href="#" title="Edit invoice"
                                                                data-invoice-id="{{ $invoice->id }}" data-bs-toggle="modal"
                                                                data-bs-target="#kt_modal_edit_invoice"
                                                                class="btn btn-icon text-hover-primary w-30px h-30px">
                                                                <i class="ki-outline ki-pencil fs-2"></i>
                                                            </a>
                                                        @endcan
                                                        @can('invoices.delete')
                                                            <a href="#" title="Delete invoice" data-bs-toggle="tooltip"
                                                                class="btn btn-icon text-hover-danger w-30px h-30px delete-invoice"
                                                                data-invoice-id="{{ $invoice->id }}">
                                                                <i class="ki-outline ki-trash fs-2"></i>
                                                            </a>
                                                        @endcan
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!--end::Table-->
                            </div>
                            <div class="tab-pane fade show" id="kt_tab_pane_transactions" role="tabpanel">
                                <!--begin::Table-->
                                <table id="kt_student_view_transactions_table"
                                    class="table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4 ucms-table">
                                    <thead class="border-bottom border-gray-200">
                                        <tr class="fw-bold fs-7 text-uppercase gs-0">
                                            <th class="w-25px">#</th>
                                            <th class="w-150px">Date</th>
                                            <th class="w-150px">Invoice No.</th>
                                            <th class="w-150px">Voucher No.</th>
                                            <th class="w-150px">Amount</th>
                                            <th class="w-150px">Payment Type</th>
                                            <th>Remarks</th>
                                            <th class="w-100px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($student->paymentTransactions->sortByDesc('created_at') as $transaction)
                                            <tr>
                                                <td>{{ $loop->index + 1 }}</td>
                                                <td>
                                                    {{ $transaction->created_at->format('d-M-Y') }}
                                                    <span class="ms-1" data-bs-toggle="tooltip"
                                                        title="{{ $transaction->created_at->format('h:i:s A, d-M-Y') }}">
                                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                                    </span>
                                                </td>
                                                <td><a
                                                        href="{{ route('invoices.show', $transaction->paymentInvoice->id) }}">{{ $transaction->paymentInvoice->invoice_number }}</a>
                                                </td>
                                                <td>{{ $transaction->voucher_no }}</td>
                                                <td class="text-success">৳{{ $transaction->amount_paid }}</td>
                                                <td>
                                                    @if ($transaction->payment_type === 'partial')
                                                        <span class="badge badge-warning rounded-pill">Partial</span>
                                                    @elseif($transaction->payment_type === 'full')
                                                        <span class="badge badge-success rounded-pill">Full Paid</span>
                                                    @elseif($transaction->payment_type === 'discounted')
                                                        <span class="badge badge-info rounded-pill">Discounted</span>
                                                    @endif
                                                </td>
                                                <td>{{ $transaction->remarks }}</td>
                                                <td>
                                                    @if ($transaction->is_approved === false)
                                                        @can('transactions.approve')
                                                            <a href="#" title="Approve Transaction"
                                                                class="btn btn-icon text-hover-success w-30px h-30px approve-txn me-2"
                                                                data-txn-id="{{ $transaction->id }}">
                                                                <i class="bi bi-check-circle fs-2"></i>
                                                            </a>
                                                        @endcan
                                                        @can('transactions.delete')
                                                            <a href="#" title="Delete Transaction"
                                                                class="btn btn-icon text-hover-danger w-30px h-30px delete-txn"
                                                                data-txn-id="{{ $transaction->id }}" data-is-approved="0">
                                                                <i class="ki-outline ki-trash fs-2"></i>
                                                            </a>
                                                        @endcan
                                                        @cannot('transactions.approve')
                                                            <span class="badge rounded-pill text-bg-secondary">Pending
                                                                Approval</span>
                                                        @endcannot
                                                    @else
                                                        @can('transactions.payslip.download')
                                                            <a href="#" data-bs-toggle="tooltip"
                                                                title="Download Statement"
                                                                class="btn btn-icon text-hover-primary w-30px h-30px download-statement"
                                                                data-invoice-id="{{ $transaction->paymentInvoice->id }}"
                                                                data-student-id="{{ $student->id }}"
                                                                data-year="{{ $transaction->paymentInvoice->created_at->format('Y') }}">
                                                                <i class="bi bi-download fs-2"></i>
                                                            </a>
                                                        @endcan

                                                        @can('transactions.delete')
                                                            @if ($transaction->created_at->greaterThan(now()->subDay()))
                                                                <a href="#" title="Delete Transaction"
                                                                    class="btn btn-icon text-hover-danger w-30px h-30px delete-txn"
                                                                    data-txn-id="{{ $transaction->id }}"
                                                                    data-is-approved="1">
                                                                    <i class="ki-outline ki-trash fs-2"></i>
                                                                </a>
                                                            @endif
                                                        @endcan
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!--end::Table-->
                            </div>
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Statements-->
                </div>
                <!--end:::Transaction Tab pane-->

                <!--begin:::Sheets Tab pane-->
                <div class="tab-pane fade" id="kt_student_view_sheets_tab" role="tabpanel">
                    <!--begin::Statements-->
                    <div class="card mb-6 mb-xl-9">
                        <!--begin::Header-->
                        <div class="card-header">
                            <!--begin::Title-->
                            <div class="card-title">
                                <!--begin::Search-->
                                <div class="d-flex align-items-center position-relative my-1">
                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                                    <input type="text" data-kt-notes-distribution-table-filter="search"
                                        class="form-control form-control-solid w-350px ps-12"
                                        placeholder="Search in Notes Distribution">
                                </div>
                                <!--end::Search-->
                            </div>
                            <!--end::Title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Toolbar-->
                                <div class="d-flex justify-content-end" data-kt-notes-distribution-table-filter="base">
                                    <!--begin::Filter-->
                                    <button type="button" class="btn btn-light-primary me-3"
                                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                        <i class="ki-outline ki-filter fs-2"></i> Filter
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
                                        <div class="px-7 py-5" data-kt-notes-distribution-table-filter="form">
                                            <!--begin::Input group-->
                                            <div class="mb-10">
                                                <label class="form-label fs-6 fw-semibold">Sheet Group:</label>
                                                <select class="form-select form-select-solid fw-bold"
                                                    data-kt-select2="true" data-placeholder="Select option"
                                                    data-allow-clear="true"
                                                    data-kt-notes-distribution-table-filter="product">
                                                    <option></option>
                                                    @foreach ($sheet_class_names as $class)
                                                        <option
                                                            value="{{ $class['name'] }} ({{ $class['class_numeral'] }})">
                                                            {{ $class['name'] }} ({{ $class['class_numeral'] }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="mb-10">
                                                <label class="form-label fs-6 fw-semibold">Subject:</label>
                                                <select class="form-select form-select-solid fw-bold"
                                                    data-kt-select2="true" data-placeholder="Select option"
                                                    data-allow-clear="true"
                                                    data-kt-notes-distribution-table-filter="product">
                                                    <option></option>
                                                    @foreach ($sheet_subjectNames as $subject)
                                                        <option value="{{ $subject }}">{{ $subject }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Actions-->
                                            <div class="d-flex justify-content-end">
                                                <button type="reset"
                                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                                    data-kt-menu-dismiss="true"
                                                    data-kt-notes-distribution-table-filter="reset">Reset</button>
                                                <button type="submit" class="btn btn-primary fw-semibold px-6"
                                                    data-kt-menu-dismiss="true"
                                                    data-kt-notes-distribution-table-filter="filter">Apply</button>
                                            </div>
                                            <!--end::Actions-->
                                        </div>
                                        <!--end::Content-->
                                    </div>
                                    <!--end::Menu 1-->
                                    <!--end::Filter-->
                                </div>
                                <!--end::Toolbar-->
                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Card body-->
                        <div class="card-body pb-5">
                            <!--begin::Table-->
                            <table id="kt_student_view_sheets_table"
                                class="table align-middle table-row-dashed table-hover fs-6 gy-5 ucms-table">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-30px">#</th>
                                        <th>Topic Name</th>
                                        <th>Sujbect</th>
                                        <th class="w-200px">Sheet Group</th>
                                        <th class="w-150px">Received Date</th>
                                        <th class="w-150px">Distributed By</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach ($student->sheetsTopicTaken->sortByDesc('created_at') as $note)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td class="text-gray-800 mb-1">{{ $note->sheetTopic->topic_name }}</td>
                                            <td>{{ $note->sheetTopic->subject->name }}</td>
                                            <td><a href="{{ route('sheets.show', $note->class->sheet->id) }}"
                                                    target="_blank">{{ $note->class->name }}
                                                    ({{ $note->class->class_numeral }})
                                                </a></td>
                                            <td>
                                                {{ $note->created_at->format('d-m-Y') }}
                                                <span class="ms-1" data-bs-toggle="tooltip"
                                                    title="{{ $note->created_at->format('h:i:s A, d-M-Y') }}">
                                                    <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                                </span>
                                            </td>
                                            <td>{{ $note->distributedBy->name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <!--end::Table-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Statements-->
                </div>
                <!--end:::Sheets Tab pane-->

                <!--begin:::Attendance Tab pane-->
                <div class="tab-pane fade" id="kt_student_view_attendance_tab" role="tabpanel">

                    {{-- ─── Card 1 : Attendance History (Calendar) ─── --}}
                    <div class="card pt-4 mb-6 mb-xl-9" id="kt_attendance_history_card">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>Attendance History</h2>
                            </div>
                            <div class="card-toolbar">
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="d-flex align-items-center fs-7 fw-semibold text-gray-600">
                                        <span class="w-10px h-10px rounded-circle me-1 d-inline-block"
                                            style="background:#50cd89"></span> Present
                                    </div>
                                    <div class="d-flex align-items-center fs-7 fw-semibold text-gray-600">
                                        <span class="w-10px h-10px rounded-circle me-1 d-inline-block"
                                            style="background:#f1416c"></span> Absent
                                    </div>
                                    <div class="d-flex align-items-center fs-7 fw-semibold text-gray-600">
                                        <span class="w-10px h-10px rounded-circle me-1 d-inline-block"
                                            style="background:#ffc700"></span> Late
                                    </div>
                                    <div class="h-20px border-start border-gray-300 mx-1"></div>
                                    <button type="button" id="kt_attendance_export_btn"
                                        class="btn btn-sm btn-light-primary">
                                        <i class="ki-outline ki-picture fs-4 me-1"></i>Export Image
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0 pb-5">
                            <div id="kt_attendance_calendar" data-events="{{ json_encode($attendance_events) }}"
                                data-student-name="{{ $student->name }}"
                                data-student-id="{{ $student->student_unique_id }}">
                            </div>
                        </div>
                    </div>

                    {{-- ─── Card 2 : Overview Pie Chart ─── --}}
                    <div class="card pt-4 mb-6 mb-xl-9" id="kt_attendance_overview_card">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2 id="kt_attendance_overview_title">Overview</h2>
                            </div>
                            <div class="card-toolbar">
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" id="kt_overview_prev_month"
                                        class="btn btn-sm btn-icon btn-light">
                                        <i class="ki-outline ki-arrow-left fs-3"></i>
                                    </button>
                                    <span id="kt_overview_month_label"
                                        class="fw-bold fs-6 text-gray-700 min-w-100px text-center"></span>
                                    <button type="button" id="kt_overview_next_month"
                                        class="btn btn-sm btn-icon btn-light">
                                        <i class="ki-outline ki-arrow-right fs-3"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0 pb-8">
                            <div id="kt_attendance_pie_chart_wrapper"
                                data-events="{{ json_encode($attendance_events) }}">

                                {{-- Chart + Stats side by side --}}
                                <div class="d-flex flex-column flex-md-row align-items-center gap-8">

                                    {{-- Pie Chart --}}
                                    <div style="position:relative; width:280px; height:280px; flex-shrink:0;">
                                        <canvas id="kt_attendance_pie_chart"></canvas>
                                    </div>

                                    {{-- Stats Cards --}}
                                    <div class="d-flex flex-column gap-4 flex-grow-1 w-100">

                                        <div class="d-flex align-items-center bg-light-success rounded p-4">
                                            <span class="w-14px h-14px rounded-circle me-4 d-inline-block flex-shrink-0"
                                                style="background:#50cd89;"></span>
                                            <div class="d-flex flex-column flex-grow-1">
                                                <span class="text-gray-700 fw-semibold fs-6">Present</span>
                                                <span class="text-gray-500 fs-7">Days attended</span>
                                            </div>
                                            <span id="kt_stat_present" class="fw-bold fs-2 text-success">0</span>
                                        </div>

                                        <div class="d-flex align-items-center bg-light-danger rounded p-4">
                                            <span class="w-14px h-14px rounded-circle me-4 d-inline-block flex-shrink-0"
                                                style="background:#f1416c;"></span>
                                            <div class="d-flex flex-column flex-grow-1">
                                                <span class="text-gray-700 fw-semibold fs-6">Absent</span>
                                                <span class="text-gray-500 fs-7">Days missed</span>
                                            </div>
                                            <span id="kt_stat_absent" class="fw-bold fs-2 text-danger">0</span>
                                        </div>

                                        <div class="d-flex align-items-center bg-light-warning rounded p-4">
                                            <span class="w-14px h-14px rounded-circle me-4 d-inline-block flex-shrink-0"
                                                style="background:#ffc700;"></span>
                                            <div class="d-flex flex-column flex-grow-1">
                                                <span class="text-gray-700 fw-semibold fs-6">Late</span>
                                                <span class="text-gray-500 fs-7">Days late</span>
                                            </div>
                                            <span id="kt_stat_late" class="fw-bold fs-2 text-warning">0</span>
                                        </div>

                                        {{-- Attendance Rate --}}
                                        <div class="border border-dashed border-gray-300 rounded p-4 mt-2">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold fs-6 text-gray-700">Attendance Rate</span>
                                                <span id="kt_stat_rate" class="fw-bold fs-4 text-primary">0%</span>
                                            </div>
                                            <div class="progress h-8px bg-light-primary">
                                                <div id="kt_stat_rate_bar" class="progress-bar bg-primary rounded"
                                                    role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Empty state (hidden by default) --}}
                                <div id="kt_pie_empty_msg"
                                    class="d-none flex-column align-items-center justify-content-center py-10 text-center">
                                    <i class="ki-outline ki-calendar-remove fs-3x text-gray-300 mb-3"></i>
                                    <span class="fs-6 text-gray-400">No attendance data for this month</span>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
                <!--end:::Attendance Tab pane-->

                <!--begin:::Activity Tab pane-->
                <div class="tab-pane fade" id="kt_student_view_activity_tab" role="tabpanel">
                    <!--begin::Card - Activations-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>Activations</h2>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body pt-0 pb-5">
                            <!--begin::Table-->
                            <table class="table align-middle table-row-dashed table-hover fs-6 gy-5 ucms-table"
                                id="kt_students_acitivation_table">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-20px">#</th>
                                        <th class="min-w-100px">Activity</th>
                                        <th>Reason</th>
                                        <th>Updated by</th>
                                        <th class="min-w-125px">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="fs-6 fw-semibold text-gray-600">
                                    @foreach ($student->activations->sortByDesc('created_at') as $record)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>
                                                @if ($record->active_status == 'inactive')
                                                    <span
                                                        class="badge badge-danger rounded-pill">{{ ucfirst($record->active_status) }}</span>
                                                @else
                                                    <span
                                                        class="badge badge-success rounded-pill">{{ ucfirst($record->active_status) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $record->reason }}</td>
                                            <td>{{ $record->updatedBy->name }}</td>
                                            <td>
                                                {{ $record->created_at->diffForHumans() }}
                                                <span class="ms-1" data-bs-toggle="tooltip"
                                                    title="{{ $record->created_at->format('h:i:s A, d-M-Y') }}">
                                                    <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <!--end::Table-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card - Activations-->

                    <!--begin::Card - Class Change History-->
                    @if ($student->classChangeHistories->count() > 0)
                        <div class="card pt-4 mb-6 mb-xl-9">
                            <!--begin::Card header-->
                            <div class="card-header border-0">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2><i class="ki-outline ki-arrows-circle fs-2 text-primary me-2"></i>Class Change
                                        History</h2>
                                </div>
                                <!--end::Card title-->
                                <!--begin::Card toolbar-->
                                <div class="card-toolbar">
                                    <span
                                        class="badge badge-light-primary fs-7">{{ $student->classChangeHistories->count() }}
                                        Changes</span>
                                </div>
                                <!--end::Card toolbar-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0 pb-5">
                                <!--begin::Table-->
                                <table class="table align-middle table-row-dashed table-hover fs-6 gy-5 ucms-table"
                                    id="kt_students_class_change_history_table">
                                    <thead>
                                        <tr class="fw-bold fs-7 text-uppercase gs-0">
                                            <th class="w-20px">#</th>
                                            <th class="min-w-150px">From Class</th>
                                            <th class="min-w-150px">To Class</th>
                                            <th class="min-w-100px">Changed By</th>
                                            <th class="min-w-125px">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-6 fw-semibold text-gray-600">
                                        @foreach ($student->classChangeHistories->sortByDesc('created_at') as $history)
                                            <tr>
                                                <td>{{ $loop->index + 1 }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge badge-light-danger me-2">
                                                            <i class="ki-outline ki-arrow-left fs-6"></i>
                                                        </span>
                                                        @if ($history->fromClass)
                                                            <span class="text-gray-800">{{ $history->fromClass->name }}
                                                                ({{ $history->fromClass->class_numeral }})
                                                            </span>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge badge-light-success me-2">
                                                            <i class="ki-outline ki-arrow-right fs-6"></i>
                                                        </span>
                                                        @if ($history->toClass)
                                                            <span class="text-gray-800">{{ $history->toClass->name }}
                                                                ({{ $history->toClass->class_numeral }})</span>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>{{ optional($history->createdBy)->name ?? 'System' }}</td>
                                                <td>
                                                    {{ $history->created_at->format('d-M-Y') }}
                                                    <span class="ms-1" data-bs-toggle="tooltip"
                                                        title="{{ $history->created_at->format('h:i:s A, d-M-Y') }}">
                                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!--end::Table-->
                            </div>
                            <!--end::Card body-->
                        </div>
                    @endif
                    <!--end::Card - Class Change History-->

                    <!--begin::Card - Student Detail Updates (Admin Only)-->
                    @if (auth()->check() && auth()->user()->isAdmin())
                        <div class="card pt-4 mb-6 mb-xl-9">
                            <!--begin::Card header-->
                            <div class="card-header border-0 pt-6 flex-wrap">
                                <!--begin::Card title-->
                                <div class="card-title flex-column">
                                    <h2><i class="ki-outline ki-notepad-edit fs-2 text-warning me-2"></i>Student Profiling
                                        Edit History</h2>
                                </div>
                                <!--end::Card title-->
                                <!--begin::Card toolbar-->
                                <div
                                    class="card-toolbar flex-row-fluid justify-content-end gap-3 min-w-300px mt-4 mt-sm-0">
                                    <!--begin::Search-->
                                    <div class="d-flex align-items-center position-relative my-1">
                                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                                        <input type="text" data-kt-student-change-logs-table-filter="search"
                                            class="form-control form-control-solid w-200px ps-12"
                                            placeholder="Search history...">
                                    </div>
                                    <!--end::Search-->

                                    <!--begin::Filter-->
                                    <div class="w-175px my-1">
                                        <select data-kt-student-change-logs-table-filter="field"
                                            class="form-select form-select-solid" data-control="select2"
                                            data-placeholder="All Fields" data-hide-search="false"
                                            data-allow-clear="true">
                                            <option value="all">All Fields</option>
                                            <option value="Academic Group">Academic Group</option>
                                            <option value="Batch">Batch</option>
                                            <option value="Blood Group">Blood Group</option>
                                            <option value="Branch">Branch</option>
                                            <option value="Class">Class</option>
                                            <option value="Date of Birth">Date of Birth</option>
                                            <option value="Email">Email</option>
                                            <option value="Gender">Gender</option>
                                            <option value="Guardian">Guardian Info</option>
                                            <option value="Home Address">Home Address</option>
                                            <option value="Institution">Institution</option>
                                            <option value="Phone">Mobile Number Info</option>
                                            <option value="Name">Name</option>
                                            <option value="Payment Due Date">Payment Due Date</option>
                                            <option value="Payment Style">Payment Style</option>
                                            <option value="Religion">Religion</option>
                                            <option value="Remarks">Remarks</option>
                                            <option value="Sibling">Sibling Info</option>
                                            <option value="Tuition Fee">Tuition Fee</option>
                                        </select>
                                    </div>
                                    <!--end::Filter-->

                                    <span class="badge badge-light-warning fs-7 my-1">{{ $student->changeLogs->count() }}
                                        Updates</span>
                                </div>
                                <!--end::Card toolbar-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0 pb-5">
                                <!--begin::Table-->
                                <table class="table align-middle table-row-dashed table-hover fs-6 gy-5 ucms-table"
                                    id="kt_students_change_logs_table">
                                    <thead>
                                        <tr class="fw-bold fs-7 text-uppercase gs-0">
                                            <th class="w-20px">#</th>
                                            <th class="min-w-150px">Field Changed</th>
                                            <th class="min-w-150px">Previous Value</th>
                                            <th class="min-w-150px">New Value</th>
                                            <th class="min-w-100px">Updated By</th>
                                            <th class="min-w-125px">Date & Time</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-6 fw-semibold text-gray-600">
                                        @foreach ($student->changeLogs as $log)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td><span class="text-gray-800 fw-semibold">{{ $log->field_name }}</span>
                                                </td>
                                                <td>
                                                    @if ($log->old_value !== null && $log->old_value !== '')
                                                        <span
                                                            class="text-danger fs-7 fw-medium">{{ $log->old_value }}</span>
                                                    @else
                                                        <span class="text-muted fs-7"><em>None</em></span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($log->new_value !== null && $log->new_value !== '')
                                                        <span class="text-success fs-7 fw-medium"
                                                            style="color: #47be7d !important;">{{ $log->new_value }}</span>
                                                    @else
                                                        <span class="text-muted fs-7"><em>None</em></span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($log->updatedBy)
                                                        <a href="{{ route('settlements.show', $log->updatedBy->id) }}"
                                                            target="_blank" class="text-gray-800 text-hover-primary">
                                                            {{ $log->updatedBy->name }}
                                                        </a>
                                                    @else
                                                        System
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $log->created_at->format('d-M-Y h:i A') }}
                                                    <span
                                                        class="text-muted fs-8">({{ $log->created_at->diffForHumans() }})</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!--end::Table-->
                            </div>
                            <!--end::Card body-->
                        </div>
                    @endif
                    <!--end::Card - Student Detail Updates-->

                </div>
                <!--end:::Activity Tab pane-->
            </div>
            <!--end:::Tab content-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Layout-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script src="{{ asset('js/employees/show.js') }}"></script>
@endpush
