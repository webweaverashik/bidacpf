@push('page-css')
    <link href="{{ asset('css/employees/edit.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')
@section('title', 'Edit Employee — ' . $employee->name)

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Edit Employee
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('employees.index') }}" class="text-muted text-hover-primary">Employees</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('employees.show', $employee) }}" class="text-muted text-hover-primary">
                    {{ $employee->name }}
                </a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Edit</li>
        </ul>
    </div>
@endsection

@section('content')
    <div id="error-container"></div>

    {{-- ================================================================== --}}
    {{--  Edit Form Card                                                      --}}
    {{-- ================================================================== --}}
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="fw-bold text-gray-900 fs-3 m-0">
                    <i class="ki-outline ki-pencil fs-3 me-2 text-primary"></i>
                    Employee Information
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-light-primary">
                    <i class="ki-outline ki-arrow-left fs-5 me-1"></i> Back to Profile
                </a>
            </div>
        </div>

        <div class="card-body py-10">
            <form id="kt_edit_employee_form" class="w-100" novalidate="novalidate" enctype="multipart/form-data">
                @csrf

                <div class="row">

                    {{-- ── Left Column ──────────────────────────────────────────── --}}
                    <div class="col-lg-8">

                        <div class="pb-8">
                            <h4 class="fw-bold text-gray-700 mb-1">Personal Details &amp; Pay Scale</h4>
                            <div class="text-muted fw-semibold fs-7">
                                Update employee information and pay scale assignment.
                            </div>
                        </div>

                        {{-- CPF Account No & Name --}}
                        <div class="row">
                            <div class="col-md-5">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">CPF Account No.</label>
                                    <input type="text" name="cpf_account_no" class="form-control"
                                        value="{{ old('cpf_account_no', $employee->cpf_account_no) }}"
                                        placeholder="e.g. PRA/K/1234/25" />
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Full Name</label>
                                    <input type="text" name="name" class="form-control"
                                        value="{{ old('name', $employee->name) }}" placeholder="Enter employee full name" />
                                </div>
                            </div>
                        </div>

                        {{-- Designation --}}
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Designation</label>
                            <input type="text" name="designation" class="form-control"
                                value="{{ old('designation', $employee->designation) }}"
                                placeholder="e.g. Assistant Director, Investment Officer" />
                        </div>

                        {{-- Email & Mobile --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="fv-row mb-7">
                                    <label class="fw-semibold fs-6 mb-2">
                                        Email
                                        <span class="text-muted">(optional)</span>
                                    </label>
                                    <input type="email" name="email" class="form-control"
                                        value="{{ old('email', $employee->email) }}" placeholder="Enter email address" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="fv-row mb-7">
                                    <label class="fw-semibold fs-6 mb-2">
                                        Mobile Number
                                        <span class="text-muted">(optional)</span>
                                    </label>
                                    <input type="text" name="mobile_number" maxlength="20"
                                        class="form-control"
                                        value="{{ old('mobile_number', $employee->mobile_number) }}"
                                        placeholder="e.g. 01712345678" />
                                </div>
                            </div>
                        </div>

                        {{-- Joining Date & Retirement Date --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Joining Date</label>
                                    <input name="joining_date" id="joining_date_input"
                                        class="form-control" placeholder="Select joining date"
                                        value="{{ old('joining_date', $employee->joining_date?->format('Y-m-d')) }}"
                                        autocomplete="off" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="fv-row mb-7">
                                    <label class="fw-semibold fs-6 mb-2">
                                        Retirement Date
                                        <span class="text-muted">(optional)</span>
                                    </label>
                                    <input name="retirement_date" id="retirement_date_input"
                                        class="form-control" placeholder="Select retirement date"
                                        value="{{ old('retirement_date', $employee->retirement_date?->format('Y-m-d')) }}"
                                        autocomplete="off" />
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================ --}}
                        {{--  Pay Scale Section                                            --}}
                        {{-- ============================================================ --}}
                        <div class="separator separator-dashed my-8"></div>

                        <h4 class="fw-bold text-gray-800 mb-2">
                            <i class="ki-outline ki-wallet fs-3 me-2 text-primary"></i>
                            Pay Scale Assignment
                        </h4>

                        {{-- ── Permission notices ─────────────────────────────────────── --}}

                        {{--
                            Notice A — Inactive scale + Admin can change → shown until Admin picks a new active scale.
                            JS hides this (notice_pay_scale_locked → d-none) and shows notice_pay_scale_unlocked instead.
                        --}}
                        @if (!$assignedActive && $canChangePayScale)
                            <div id="notice_pay_scale_locked"
                                class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mb-6">
                                <i class="ki-outline ki-information-5 fs-2tx text-warning me-3"></i>
                                <div class="d-flex flex-column">
                                    <h6 class="mb-1 text-warning fw-bold">Inactive Pay Scale — Action Required</h6>
                                    <span class="fs-7 text-gray-700">
                                        The assigned pay scale <strong>{{ $assignedScaleName ?? 'N/A' }}</strong> is
                                        <strong>inactive</strong>. Please select a new <strong>active</strong> pay scale
                                        below, then choose the appropriate grade and basic salary.
                                    </span>
                                </div>
                            </div>

                            {{-- Shown by JS once a valid active scale is selected --}}
                            <div id="notice_pay_scale_unlocked"
                                class="notice d-flex bg-light-success rounded border-success border border-dashed p-4 mb-6 d-none">
                                <i class="ki-outline ki-shield-tick fs-2tx text-success me-3"></i>
                                <div class="d-flex flex-column">
                                    <h6 class="mb-1 text-success fw-bold">Active Pay Scale Selected</h6>
                                    <span class="fs-7 text-gray-700">
                                        You have selected an active pay scale. Please choose the appropriate
                                        <strong>grade</strong> and <strong>basic salary</strong> below.
                                    </span>
                                </div>
                            </div>
                        @endif

                        @if (!$assignedActive && !$canChangePayScale)
                            {{-- Inactive scale + no permission to change at all --}}
                            <div id="notice_pay_scale_locked"
                                class="notice d-flex bg-light-danger rounded border-danger border border-dashed p-4 mb-6">
                                <i class="ki-outline ki-shield-cross fs-2tx text-danger me-3"></i>
                                <div class="d-flex flex-column">
                                    <h6 class="mb-1 text-danger fw-bold">Pay Scale Locked</h6>
                                    <span class="fs-7 text-gray-700">
                                        The assigned pay scale <strong>{{ $assignedScaleName ?? 'N/A' }}</strong> is
                                        <strong>inactive</strong>. You do not have permission to change the pay scale.
                                        Contact an administrator.
                                    </span>
                                </div>
                            </div>
                        @endif

                        @if ($assignedActive && !$canChangeGradeSalary)
                            {{-- Active scale but user has no permission to change grade/salary --}}
                            <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4 mb-6">
                                <i class="ki-outline ki-information-5 fs-2tx text-info me-3"></i>
                                <div class="d-flex flex-column">
                                    <h6 class="mb-1 text-info fw-bold">Pay Scale (Read-Only)</h6>
                                    <span class="fs-7 text-gray-700">
                                        You do not have permission to change the grade or basic salary.
                                    </span>
                                </div>
                            </div>
                        @endif

                        {{-- ── Pay Scale Selector ──────────────────────────────────────── --}}
                        @if ($canChangePayScale)
                            {{-- Admin sees the full pay scale dropdown --}}
                            <div class="fv-row mb-7" id="pay_scale_row">
                                <label class="required fw-semibold fs-6 mb-2">Pay Scale</label>
                                <select name="pay_scale_id" id="pay_scale_select" class="form-select"
                                    data-control="select2" data-placeholder="Select a pay scale" data-hide-search="true">
                                    <option></option>
                                    @foreach ($payScales as $scale)
                                        <option value="{{ $scale->id }}"
                                            data-active="{{ $scale->is_active ? '1' : '0' }}"
                                            {{ (int) $assignedScaleId === (int) $scale->id ? 'selected' : '' }}>
                                            {{ $scale->name }}
                                            ({{ $scale->effective_year }})
                                            {{ $scale->is_active ? '' : '— Inactive' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text" id="pay_scale_hint"></div>
                            </div>
                        @else
                            {{-- Hidden — always send the assigned pay scale id --}}
                            <input type="hidden" name="pay_scale_id" value="{{ $assignedScaleId }}" />

                            {{-- Read-only display badge --}}
                            <div class="mb-6">
                                <span class="fw-semibold fs-6 text-gray-700 me-2">Current Pay Scale:</span>
                                <span
                                    class="badge {{ $assignedActive ? 'badge-light-success' : 'badge-light-danger' }} fs-7">
                                    {{ $assignedScaleName ?? 'N/A' }}
                                    {{ $assignedActive ? '' : '(Inactive)' }}
                                </span>
                            </div>
                        @endif

                        {{-- ── Grade & Basic Salary ───────────────────────────────────── --}}
                        {{--
                            IMPORTANT: The grade and salary selects are ALWAYS rendered in the
                            DOM — even when canChangeGradeSalary is false. They start as disabled.
                            When the Admin picks a new active pay scale (inactive-scale scenario),
                            JS calls enableGradeSalarySelects() which removes the disabled attribute
                            and loads grades + steps via AJAX.

                            When neither canChangePayScale NOR canChangeGradeSalary is true
                            (e.g. CPF Officer with inactive scale, or regular user), the selects
                            remain permanently disabled and a hidden input carries the current step id.
                        --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="fv-row mb-7">
                                    <label class="{{ $canChangeGradeSalary ? 'required' : '' }} fw-semibold fs-6 mb-2">
                                        Grade
                                    </label>
                                    <select name="grade" id="grade_select" class="form-select"
                                        data-control="select2" data-placeholder="Select a grade" data-hide-search="true"
                                        {{ !$canChangeGradeSalary ? 'disabled' : '' }}>
                                        <option></option>
                                        @foreach ($grades as $grade)
                                            <option value="{{ $grade }}"
                                                {{ (int) old('grade', $currentGrade) === (int) $grade ? 'selected' : '' }}>
                                                Grade {{ $grade }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="fv-row mb-7">
                                    <label class="{{ $canChangeGradeSalary ? 'required' : '' }} fw-semibold fs-6 mb-2">
                                        Basic Salary
                                    </label>
                                    <select name="pay_scale_step_id" id="basic_salary_select"
                                        class="form-select"
                                        data-placeholder="{{ $canChangeGradeSalary ? 'Select grade first' : ($canChangePayScale ? 'Select pay scale first' : 'No permission') }}"
                                        {{ !$canChangeGradeSalary ? 'disabled' : '' }}>
                                        <option></option>
                                        {{-- Steps are populated by JS (on page load for active scales, or after pay scale selection) --}}
                                    </select>
                                    <div class="form-text" id="salary_hint"></div>
                                </div>
                            </div>
                        </div>

                        {{--
                            Hidden fallback for pay_scale_step_id:
                            - Sent when the user has NO ability to change grade/salary at all
                              (CPF Officer with inactive scale, regular user, or no permission).
                            - NOT rendered when canChangePayScale is true (Admin with inactive scale)
                              because the Admin will pick a new step via the visible selects.
                            - NOT rendered when canChangeGradeSalary is true (normal editable flow).
                        --}}
                        @if (!$canChangeGradeSalary && !$canChangePayScale)
                            <input type="hidden" name="pay_scale_step_id" value="{{ $employee->pay_scale_step_id }}" />
                        @endif

                    </div>
                    {{-- ── end Left Column ──────────────────────────────────── --}}

                    {{-- ── Right Column — Photo only ───────────────────────── --}}
                    <div class="col-lg-4">

                        <div class="fv-row mb-7">
                            <label class="d-block fw-semibold fs-6 mb-5">
                                Profile Photo
                                <span class="text-muted">(optional)</span>
                            </label>

                            <style>
                                .image-input-placeholder {
                                    background-image: url('{{ asset('assets/media/svg/files/blank-image.svg') }}');
                                }

                                [data-bs-theme="dark"] .image-input-placeholder {
                                    background-image: url('{{ asset('assets/media/svg/files/blank-image-dark.svg') }}');
                                }
                            </style>

                            <div class="image-input image-input-circle image-input-outline
                                    {{ $employee->photo ? '' : 'image-input-empty image-input-placeholder' }}"
                                data-kt-image-input="true" id="kt_employee_photo_input">

                                <div class="image-input-wrapper w-125px h-125px"
                                    @if ($employee->photo) style="background-image: url('{{ asset($employee->photo) }}')" @endif>
                                </div>

                                <label
                                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change photo">
                                    <i class="ki-outline ki-pencil fs-7"></i>
                                    <input type="file" name="photo" id="photo_file_input"
                                        accept=".png,.jpg,.jpeg" />
                                    <input type="hidden" name="photo_remove" id="photo_remove_input" value="0" />
                                </label>

                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel">
                                    <i class="ki-outline ki-cross fs-2"></i>
                                </span>

                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove photo">
                                    <i class="ki-outline ki-cross fs-2"></i>
                                </span>
                            </div>

                            <div class="form-text">Allowed: png, jpg, jpeg. Max 512 KB.</div>
                        </div>

                        {{-- CPF Account info (read-only) --}}
                        <div class="separator separator-dashed my-6"></div>

                        <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4 mb-5">
                            <i class="ki-outline ki-information-5 fs-2tx text-info me-3"></i>
                            <div class="fw-semibold">
                                <div class="fs-7 text-gray-700">
                                    <strong>CPF A/C:</strong> {{ $employee->cpf_account_no }}<br>
                                    <strong class="mt-1 d-block">Joining Date</strong>
                                    <span class="text-muted fs-8">
                                        {{ $employee->joining_date?->format('d M Y') ?? '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Permission badge summary --}}
                        <div class="card bg-light border-0 p-4">
                            <div class="fs-7 fw-semibold text-gray-700 mb-2">
                                <i class="ki-outline ki-shield-tick fs-5 me-1 text-success"></i>
                                Your Edit Permissions
                            </div>
                            <ul class="list-unstyled mb-0 fs-8 text-gray-600">
                                <li class="mb-1">
                                    <i class="ki-outline ki-check fs-7 text-success me-1"></i>
                                    Personal details &amp; status
                                </li>
                                <li class="mb-1">
                                    @if ($canChangePayScale)
                                        <i class="ki-outline ki-check fs-7 text-success me-1"></i>
                                        Change pay scale
                                    @else
                                        <i class="ki-outline ki-cross fs-7 text-danger me-1"></i>
                                        Change pay scale
                                    @endif
                                </li>
                                <li class="mb-1" id="perm_badge_grade">
                                    @if ($canChangeGradeSalary)
                                        <i class="ki-outline ki-check fs-7 text-success me-1"></i>
                                        Change grade &amp; basic salary
                                    @elseif ($canChangePayScale)
                                        {{--
                                            Admin with inactive scale: grade/salary will become
                                            available once a new active pay scale is selected.
                                            JS updates this badge text dynamically.
                                        --}}
                                        <i class="ki-outline ki-time fs-7 text-warning me-1"></i>
                                        Change grade &amp; basic salary
                                        <span class="text-muted">(after selecting pay scale)</span>
                                    @else
                                        <i class="ki-outline ki-cross fs-7 text-danger me-1"></i>
                                        Change grade &amp; basic salary
                                    @endif
                                </li>
                            </ul>
                        </div>

                    </div>
                    {{-- ── end Right Column ────────────────────────────────── --}}

                </div>{{-- end .row --}}

                {{-- ── Action Buttons ──────────────────────────────────────── --}}
                <div class="separator separator-dashed my-8"></div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-light btn-active-light-primary">
                        <i class="ki-outline ki-cross fs-4 me-1"></i> Cancel
                    </a>
                    <button type="button" id="btn_update" class="btn btn-primary">
                        <span class="indicator-label">
                            <i class="ki-outline ki-check fs-4 me-1"></i>
                            Save Changes
                        </span>
                        <span class="indicator-progress">
                            Please wait&hellip;
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{--  JS Configuration Object                                            --}}
    {{-- ================================================================== --}}
    <script>
        var EmployeeEditConfig = {
            stepsUrl: "{{ route('employees.steps-by-grade') }}",
            gradesByScaleUrl: "{{ route('employees.grades-by-pay-scale') }}",
            updateUrl: "{{ route('employees.update', $employee) }}",
            showUrl: "{{ route('employees.show', $employee) }}",
            csrfToken: "{{ csrf_token() }}",

            // Permission flags (set by controller)
            canChangePayScale: {{ $canChangePayScale ? 'true' : 'false' }},
            canChangeGradeSalary: {{ $canChangeGradeSalary ? 'true' : 'false' }},

            // Current employee pay scale state
            employee: {
                pay_scale_step_id: {{ $currentStepId ?? 'null' }},
                grade: {{ $currentGrade ?? 'null' }},
                pay_scale_id: {{ $assignedScaleId ?? 'null' }},
            }
        };
    </script>
@endsection

@push('page-js')
    <script src="{{ asset('js/employees/edit.js') }}"></script>
@endpush
