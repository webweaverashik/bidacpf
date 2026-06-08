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

            {{-- NOTE: enctype="multipart/form-data" for semantic correctness / native fallback. --}}
            <form id="kt_edit_employee_form" class="w-100" novalidate="novalidate" enctype="multipart/form-data">
                @csrf

                <div class="row">

                    {{-- ── Left Column ──────────────────────────────────────────── --}}
                    <div class="col-lg-8">

                        <div class="pb-8">
                            <h4 class="fw-bold text-gray-700 mb-1">Personal Details &amp; Pay Scale</h4>
                            <div class="text-muted fw-semibold fs-7">
                                Update employee information. CPF opening balances cannot be changed here.
                            </div>
                        </div>

                        {{-- CPF Account No & Name --}}
                        <div class="row">
                            <div class="col-md-5">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">CPF Account No.</label>
                                    <input type="text" name="cpf_account_no" class="form-control form-control-solid"
                                        value="{{ old('cpf_account_no', $employee->cpf_account_no) }}"
                                        placeholder="e.g. PRA/K/1234/25" />
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Full Name</label>
                                    <input type="text" name="name" class="form-control form-control-solid"
                                        value="{{ old('name', $employee->name) }}" placeholder="Enter employee full name" />
                                </div>
                            </div>
                        </div>

                        {{-- Designation --}}
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Designation</label>
                            <input type="text" name="designation" class="form-control form-control-solid"
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
                                    <input type="email" name="email" class="form-control form-control-solid"
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
                                        class="form-control form-control-solid"
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
                                        class="form-control form-control-solid" placeholder="Select joining date"
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
                                        class="form-control form-control-solid" placeholder="Select retirement date"
                                        value="{{ old('retirement_date', $employee->retirement_date?->format('Y-m-d')) }}"
                                        autocomplete="off" />
                                </div>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Status</label>
                            <div class="d-flex gap-6 flex-wrap mt-1">
                                @foreach (\App\Enums\EmployeeStatus::cases() as $case)
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="radio" name="status"
                                            value="{{ $case->value }}" id="status_{{ $case->value }}"
                                            {{ old('status', $employee->status->value) === $case->value ? 'checked' : '' }} />
                                        <label class="form-check-label fw-medium" for="status_{{ $case->value }}">
                                            {{ $case->label() }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Pay Scale Section --}}
                        <div class="separator separator-dashed my-8"></div>

                        <h4 class="fw-bold text-gray-800 mb-5">
                            <i class="ki-outline ki-wallet fs-3 me-2 text-primary"></i>
                            Pay Scale
                            @if ($payScale)
                                <span class="badge badge-light-success ms-2 fs-8">{{ $payScale->name }}</span>
                            @endif
                        </h4>

                        <div class="row">
                            <div class="col-md-4">
                                {{-- Grade --}}
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Grade</label>
                                    <select name="grade" id="grade_select" class="form-select form-select-solid"
                                        data-control="select2" data-placeholder="Select a grade" data-hide-search="true">
                                        <option></option>
                                        @if ($grades)
                                            @foreach ($grades as $grade)
                                                <option value="{{ $grade }}"
                                                    {{ (int) old('grade', $employee->grade) === (int) $grade ? 'selected' : '' }}>
                                                    Grade {{ $grade }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                {{-- Basic Salary (populated via AJAX) --}}
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Basic Salary</label>
                                    <select name="pay_scale_step_id" id="basic_salary_select"
                                        class="form-select form-select-solid" data-placeholder="Select grade first"
                                        disabled>
                                        <option></option>
                                    </select>
                                    <div class="form-text" id="salary_hint"></div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="pay_scale_id" value="{{ $payScale?->id }}" />
                    </div>
                    {{-- ── end Left Column ──────────────────────────────────── --}}

                    {{-- ── Right Column — Photo ────────────────────────────── --}}
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

                            {{-- Pre-populate with existing photo --}}
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

                        {{-- CPF Info (read-only info card) --}}
                        <div class="separator separator-dashed my-6"></div>

                        <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4">
                            <i class="ki-outline ki-information-5 fs-2tx text-info me-3"></i>
                            <div class="fw-semibold">
                                <div class="fs-7 text-gray-700">
                                    <strong>CPF A/C:</strong> {{ $employee->cpf_account_no }}<br>
                                    <strong class="mt-1 d-block">Opening Balance</strong>
                                    <span class="text-muted fs-8">
                                        CPF opening balances cannot be edited after creation.
                                        Contact a super-admin for corrections.
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if ($employee->openingBalance)
                            <div class="mt-4 p-4 bg-light rounded">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fs-7 text-muted">Own Contribution</span>
                                    <span class="fs-7 fw-bold">৳
                                        {{ number_format($employee->openingBalance->self_contribution) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fs-7 text-muted">Govt Contribution</span>
                                    <span class="fs-7 fw-bold">৳
                                        {{ number_format($employee->openingBalance->government_contribution) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fs-7 text-muted">Bank Interest</span>
                                    <span class="fs-7 fw-bold">৳
                                        {{ number_format($employee->openingBalance->interest_amount) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fs-7 text-muted">Advance Balance</span>
                                    <span class="fs-7 fw-bold text-danger">৳
                                        {{ number_format($employee->openingBalance->outstanding_advance) }}</span>
                                </div>
                                <div class="separator separator-dashed my-2"></div>
                                <div class="d-flex justify-content-between">
                                    <span class="fs-7 fw-bold">Net Balance</span>
                                    <span class="fs-6 fw-bolder text-success">৳
                                        {{ number_format($employee->openingBalance->net_balance) }}</span>
                                </div>
                            </div>
                        @endif
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

    {{-- JS config --}}
    <script>
        var EmployeeEditConfig = {
            stepsUrl: "{{ route('employees.steps-by-grade') }}",
            updateUrl: "{{ route('employees.update', $employee) }}",
            showUrl: "{{ route('employees.show', $employee) }}",
            csrfToken: "{{ csrf_token() }}",
            employee: {
                grade: {{ $employee->grade ?? 'null' }},
                pay_scale_step_id: {{ $employee->pay_scale_step_id ?? 'null' }},
            }
        };
    </script>

@endsection

@push('page-js')
    <script src="{{ asset('js/employees/edit.js') }}"></script>
@endpush
