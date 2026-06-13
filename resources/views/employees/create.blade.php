@push('page-css')
    <link href="{{ asset('css/employees/create.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'New Employee')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Employee Creation Form
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('employees.index') }}" class="text-muted text-hover-primary">Employees</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">New Employee</li>
        </ul>
    </div>
@endsection

@section('content')
    <div id="error-container"></div>

    {{-- ==================================================================
     Stepper
     ================================================================== --}}
    <div class="stepper stepper-pills stepper-column d-flex flex-column flex-xl-row flex-row-fluid gap-10"
        id="kt_create_employee_stepper">

        {{-- ── Aside / Nav ──────────────────────────────────────────────── --}}
        <div class="card d-flex justify-content-center justify-content-xl-start flex-row-auto w-100 w-xl-300px w-xxl-400px">
            <div class="card-body px-6 px-lg-10 px-xxl-15 py-20">
                <div class="stepper-nav">

                    {{-- Step 1 --}}
                    <div class="stepper-item current" data-kt-stepper-element="nav">
                        <div class="stepper-wrapper">
                            <div class="stepper-icon w-40px h-40px">
                                <i class="ki-outline ki-check fs-2 stepper-check"></i>
                                <span class="stepper-number">1</span>
                            </div>
                            <div class="stepper-label">
                                <h3 class="stepper-title">Employee Information</h3>
                                <div class="stepper-desc fw-semibold">Personal details &amp; pay scale</div>
                            </div>
                        </div>
                        <div class="stepper-line h-40px"></div>
                    </div>

                    {{-- Step 2 --}}
                    <div class="stepper-item" data-kt-stepper-element="nav">
                        <div class="stepper-wrapper">
                            <div class="stepper-icon w-40px h-40px">
                                <i class="ki-outline ki-check fs-2 stepper-check"></i>
                                <span class="stepper-number">2</span>
                            </div>
                            <div class="stepper-label">
                                <h3 class="stepper-title">CPF Opening Balance</h3>
                                <div class="stepper-desc fw-semibold">Set initial CPF balances</div>
                            </div>
                        </div>
                        <div class="stepper-line h-40px"></div>
                    </div>

                    {{-- Step 3 --}}
                    <div class="stepper-item" data-kt-stepper-element="nav">
                        <div class="stepper-wrapper">
                            <div class="stepper-icon w-40px h-40px">
                                <i class="ki-outline ki-check fs-2 stepper-check"></i>
                                <span class="stepper-number">3</span>
                            </div>
                            <div class="stepper-label">
                                <h3 class="stepper-title">Registration Complete</h3>
                                <div class="stepper-desc fw-semibold">Employee registered</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        {{-- ── end Aside ────────────────────────────────────────────────── --}}

        {{-- ── Main Content ─────────────────────────────────────────────── --}}
        <div class="card d-flex flex-row-fluid flex-center">
            <form class="card-body py-20 w-100 px-9" novalidate="novalidate" enctype="multipart/form-data"
                id="kt_create_employee_form">
                @csrf

                {{-- ====================================================== --}}
                {{-- STEP 1 — Employee Information                           --}}
                {{-- ====================================================== --}}
                <div data-kt-stepper-element="content" class="current">
                    <div class="w-100">

                        <div class="pb-10 pb-lg-15">
                            <h2 class="fw-bold d-flex align-items-center text-gray-900">
                                Employee Personal Information
                            </h2>
                            <div class="text-muted fw-semibold fs-6">
                                Fill in employee details and assign a pay scale grade.
                            </div>
                        </div>

                        <div class="row">

                            {{-- ── Left Column ──────────────────────────── --}}
                            <div class="col-lg-8">

                                {{-- CPF Account No & Name --}}
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="fv-row mb-7">
                                            <label class="required fw-semibold fs-6 mb-2">CPF Account No.</label>
                                            <input type="text" name="cpf_account_no" class="form-control"
                                                placeholder="e.g. CPF/2026/001" />
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="fv-row mb-7">
                                            <label class="required fw-semibold fs-6 mb-2">Full Name</label>
                                            <input type="text" name="name" class="form-control"
                                                placeholder="Enter employee full name" />
                                        </div>
                                    </div>
                                </div>

                                {{-- Designation --}}
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Designation</label>
                                    <input type="text" name="designation" class="form-control"
                                        placeholder="e.g. Assistant Director, Investment Officer" />
                                </div>

                                {{-- Email & Mobile --}}
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="fv-row mb-7">
                                            <label class="fw-semibold fs-6 mb-2">
                                                Email <span class="text-muted">(optional)</span>
                                            </label>
                                            <input type="email" name="email" class="form-control"
                                                placeholder="Enter email address" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="fv-row mb-7">
                                            <label class="fw-semibold fs-6 mb-2">
                                                Mobile Number <span class="text-muted">(optional)</span>
                                            </label>
                                            <input type="text" name="mobile_number" maxlength="20" class="form-control"
                                                placeholder="e.g. 01712345678" />
                                        </div>
                                    </div>
                                </div>

                                {{-- Joining Date & Retirement Date --}}
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="fv-row mb-7">
                                            <label class="required fw-semibold fs-6 mb-2">Joining Date</label>
                                            <input name="joining_date" id="joining_date_input" class="form-control"
                                                placeholder="Select joining date" autocomplete="off" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="fv-row mb-7">
                                            <label class="fw-semibold fs-6 mb-2">
                                                Retirement Date <span class="text-muted">(optional)</span>
                                            </label>
                                            <input name="retirement_date" id="retirement_date_input" class="form-control"
                                                placeholder="Select retirement date" autocomplete="off" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- ── end Left Column ──────────────────────── --}}

                            {{-- ── Right Column ─────────────────────────── --}}
                            <div class="col-lg-4">

                                {{-- Photo --}}
                                <div class="fv-row mb-7">
                                    <label class="d-block fw-semibold fs-6 mb-5">
                                        Profile Photo <span class="text-muted">(optional)</span>
                                    </label>
                                    <style>
                                        .image-input-placeholder {
                                            background-image: url('{{ asset('assets/media/svg/files/blank-image.svg') }}');
                                        }

                                        [data-bs-theme="dark"] .image-input-placeholder {
                                            background-image: url('{{ asset('assets/media/svg/files/blank-image-dark.svg') }}');
                                        }
                                    </style>
                                    <div class="image-input image-input-circle image-input-empty image-input-outline image-input-placeholder"
                                        data-kt-image-input="true" id="kt_employee_photo_input">
                                        <div class="image-input-wrapper w-125px h-125px"></div>
                                        <label
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                            title="Change photo">
                                            <i class="ki-outline ki-pencil fs-7"></i>
                                            <input type="file" name="photo" id="photo_file_input"
                                                accept=".png,.jpg,.jpeg" />
                                            <input type="hidden" name="photo_remove" />
                                        </label>
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel">
                                            <i class="ki-outline ki-cross fs-2"></i>
                                        </span>
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove">
                                            <i class="ki-outline ki-cross fs-2"></i>
                                        </span>
                                    </div>
                                    <div class="form-text">Allowed: png, jpg, jpeg. Max 512 KB.</div>
                                </div>

                                <div class="separator separator-dashed my-6"></div>

                                {{-- ── Pay Scale Section ───────────────────────────────────────
                                 When there is only ONE active pay scale: show a static badge
                                 (legacy single-scale behaviour — no change for existing setups).
                                 When there are MULTIPLE active pay scales: show a <select> so
                                 the user can pick the scale BEFORE choosing grade + step.
                                 The JS cascades: scale → grades (AJAX) → steps (AJAX).
                            --}}
                                <h4 class="fw-bold text-gray-800 mb-5">
                                    <i class="ki-outline ki-wallet fs-3 me-2 text-primary"></i>
                                    Pay Scale
                                    {{-- Single-scale badge (hidden when multiple scales) --}}
                                    @if ($payScales->count() === 1)
                                        <span class="badge badge-light-success ms-2 fs-8">
                                            {{ $defaultPayScale->name }}
                                        </span>
                                    @endif
                                </h4>

                                {{-- Pay Scale Selector — only rendered for multi-scale setups --}}
                                @if ($payScales->count() > 1)
                                    <div class="fv-row mb-7" id="pay_scale_row">
                                        <label class="required fw-semibold fs-6 mb-2">Pay Scale</label>
                                        <select name="pay_scale_id" id="pay_scale_select" class="form-select"
                                            data-control="select2" data-placeholder="Select a pay scale"
                                            data-hide-search="false">
                                            <option></option>
                                            @foreach ($payScales as $ps)
                                                <option value="{{ $ps->id }}"
                                                    {{ $ps->id === $defaultPayScale?->id ? 'selected' : '' }}>
                                                    {{ $ps->name }}
                                                    @if ($ps->effective_year)
                                                        ({{ $ps->effective_year }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    {{-- Single scale: store its ID as a hidden input (original behaviour) --}}
                                    <input type="hidden" name="pay_scale_id" value="{{ $defaultPayScale?->id }}" />
                                @endif

                                {{-- Grade --}}
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Grade</label>
                                    <select name="grade" id="grade_select" class="form-select" data-control="select2"
                                        data-placeholder="Select a grade" data-hide-search="true"
                                        {{ $payScales->count() > 1 && !$defaultPayScale ? 'disabled' : '' }}>
                                        <option></option>
                                        @foreach ($grades as $grade)
                                            <option value="{{ $grade }}">Grade {{ $grade }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Basic Salary (populated via AJAX) --}}
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Basic Salary</label>
                                    <select name="pay_scale_step_id" id="basic_salary_select" class="form-select"
                                        data-placeholder="Select grade first" disabled>
                                        <option></option>
                                    </select>
                                    <div class="form-text" id="salary_hint"></div>
                                </div>

                            </div>
                            {{-- ── end Right Column ─────────────────────── --}}

                        </div>
                    </div>
                </div>
                {{-- end Step 1 --}}

                {{-- ====================================================== --}}
                {{-- STEP 2 — CPF Opening Balance                           --}}
                {{-- ====================================================== --}}
                <div data-kt-stepper-element="content" class="d-none">
                    <div class="w-100">
                        <div class="pb-10 pb-lg-15">
                            <h2 class="fw-bold text-gray-900">CPF Opening Balance</h2>
                            <div class="text-muted fw-semibold fs-6">
                                Enter the initial CPF balances. Use 0 for a fresh account.
                            </div>
                        </div>

                        <div
                            class="notice d-flex align-items-center bg-light-primary rounded border-primary border border-dashed p-4 mb-8">
                            <i class="ki-outline ki-information-5 fs-2tx text-primary me-4"></i>
                            <div class="fw-semibold">
                                <div class="fs-6 text-gray-700">
                                    <strong>All amounts are integers (BDT).</strong>
                                    Opening balances seed the CPF ledger. Enter 0 if this is a new account.
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">
                                        <i class="ki-outline ki-people fs-5 me-1 text-success"></i>
                                        Employee (Own) Contribution
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">৳</span>
                                        <input type="number" name="opening_employee_contribution" class="form-control"
                                            min="0" value="0" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">
                                        <i class="ki-outline ki-bank fs-5 me-1 text-info"></i>
                                        Government Contribution
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">৳</span>
                                        <input type="number" name="opening_government_contribution" class="form-control"
                                            min="0" value="0" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">
                                        <i class="ki-outline ki-chart-line-up fs-5 me-1 text-warning"></i>
                                        Bank Interest
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">৳</span>
                                        <input type="number" name="opening_bank_interest" class="form-control"
                                            min="0" value="0" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Effective Date</label>
                                    <input name="opening_effective_date" id="opening_effective_date_input"
                                        class="form-control" placeholder="Balance as of date" autocomplete="off" />
                                    <div class="form-text">Date from which these balances apply.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="fv-row mb-7">
                                    <label class="fw-semibold fs-6 mb-2">Net Opening Balance</label>
                                    <div class="d-flex align-items-center bg-light-warning rounded p-4">
                                        <span class="fw-bold fs-4 text-warning" id="net_opening_balance_display">৳
                                            0</span>
                                    </div>
                                    <div class="form-text">Own + Govt + Interest</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                {{-- end Step 2 --}}

                {{-- ====================================================== --}}
                {{-- STEP 3 — Completion                                    --}}
                {{-- ====================================================== --}}
                <div data-kt-stepper-element="content" class="d-none">
                    <div class="w-100">
                        <div class="pb-8 pb-lg-10">
                            <h2 class="fw-bold text-gray-900">Employee Registered!</h2>
                            <div class="text-muted fw-semibold fs-6">
                                The employee has been successfully added to the CPF system.
                            </div>
                        </div>

                        <div class="notice d-flex bg-light-success rounded border-success border border-dashed p-6 mb-8">
                            <i class="ki-outline ki-check-circle fs-2tx text-success me-4"></i>
                            <div class="d-flex flex-stack flex-grow-1">
                                <div class="fw-semibold">
                                    <h4 class="text-gray-900 fw-bold">
                                        <span id="created_employee_name"></span>
                                        &mdash; CPF A/C:
                                        <span id="created_cpf_account" class="text-primary"></span>
                                    </h4>
                                    <div class="fs-6 text-gray-700">
                                        The CPF account has been initialized with the opening balances.
                                        You can now manage contributions and advances from the employee profile.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <a href="#" id="btn_view_employee" class="btn btn-primary btn-sm">
                                <i class="ki-outline ki-eye fs-4 me-1"></i> View Employee
                            </a>
                            <a href="{{ route('employees.create') }}" class="btn btn-light-primary btn-sm">
                                <i class="ki-outline ki-plus fs-4 me-1"></i> Add Another
                            </a>
                        </div>
                    </div>
                </div>
                {{-- end Step 3 --}}

                {{-- ====================================================== --}}
                {{-- Action Buttons                                         --}}
                {{-- ====================================================== --}}
                <div class="d-flex flex-stack pt-10">
                    <div>
                        <button type="button" id="btn_prev" data-kt-stepper-action="previous"
                            class="btn btn-lg btn-light-primary me-3 d-none">
                            <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Back
                        </button>
                    </div>
                    <div>
                        <button type="button" id="btn_submit" class="btn btn-lg btn-primary me-3 d-none">
                            <span class="indicator-label">
                                Submit <i class="ki-outline ki-arrow-right fs-3 ms-2 me-0"></i>
                            </span>
                            <span class="indicator-progress">
                                Please wait&hellip;
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                        <button type="button" id="btn_next" data-kt-stepper-action="next"
                            class="btn btn-lg btn-primary">
                            Continue <i class="ki-outline ki-arrow-right fs-4 ms-1 me-0"></i>
                        </button>
                    </div>
                </div>

            </form>
        </div>
        {{-- ── end Main Content ─────────────────────────────────────────── --}}

    </div>
    {{-- end Stepper --}}

    {{-- JS config object --}}
    <script>
        var EmployeeConfig = {
            stepsUrl: "{{ route('employees.steps-by-grade') }}",
            gradesUrl: "{{ route('employees.grades-by-pay-scale') }}",
            storeUrl: "{{ route('employees.store') }}",
            showUrl: "{{ route('employees.show', ':id') }}",
            csrfToken: "{{ csrf_token() }}",
            multiPayScale: {{ $payScales->count() > 1 ? 'true' : 'false' }},
            defaultPayScaleId: {{ $defaultPayScale?->id ?? 'null' }}
        };
    </script>
@endsection

@push('page-js')
    <script src="{{ asset('js/employees/create.js') }}"></script>
@endpush
