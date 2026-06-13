@push('page-css')
    <link href="{{ asset('css/settings/index.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')
@section('title', 'System Settings')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            System Settings
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">CPF</li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Settings</li>
        </ul>
    </div>
@endsection

@section('content')
    @include('settings.partials.hero')

    @php
        $get = fn($key, $default = null) => optional($settings[$key] ?? null)->value ?? $default;
    @endphp

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="fw-bold text-gray-900 fs-3 m-0">
                    <span class="d-inline-flex align-items-center">
                        <i class="ki-outline ki-setting-3 fs-2x me-2 text-primary"></i>
                        CPF System Settings
                    </span>
                </h3>
            </div>
        </div>

        <div class="card-body py-10">
            <div class="notice d-flex align-items-center bg-light-info rounded border-info border border-dashed p-4 mb-8">
                <i class="ki-outline ki-information-5 fs-2tx text-info me-3"></i>
                <div class="fw-semibold fs-7 text-gray-700">
                    Contribution rates, advance limits, and interest distribution. Changes apply to
                    <strong>future calculations only</strong> and do not affect historical ledger entries.
                </div>
            </div>

            <form id="kt_settings_form" class="w-100" novalidate="novalidate">
                @csrf

                <div class="row">
                    {{-- Employee Contribution Rate --}}
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Employee Contribution Rate</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                    name="employee_contribution_rate" class="form-control"
                                    value="{{ $get('employee_contribution_rate', 10) }}" />
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="fv-feedback text-danger fs-7 mt-2"></div>
                            <div class="form-text">Percentage of basic salary contributed by the employee.</div>
                        </div>
                    </div>

                    {{-- Government Contribution Rate --}}
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Government Contribution Rate</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                    name="government_contribution_rate" class="form-control"
                                    value="{{ $get('government_contribution_rate', 8.33) }}" />
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="fv-feedback text-danger fs-7 mt-2"></div>
                            <div class="form-text">Percentage of basic salary contributed by the government.</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Advance Limit Percentage --}}
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Advance Limit</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                    name="advance_limit_percentage" class="form-control"
                                    value="{{ $get('advance_limit_percentage', 80) }}" />
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="fv-feedback text-danger fs-7 mt-2"></div>
                            <div class="form-text">Maximum advance allowed as a percentage of available CPF balance.</div>
                        </div>
                    </div>

                    {{-- Advance Interest Rate --}}
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Advance Interest Rate</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                    name="advance_interest_rate" class="form-control"
                                    value="{{ $get('advance_interest_rate', 5) }}" />
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="fv-feedback text-danger fs-7 mt-2"></div>
                            <div class="form-text">Interest charged on advance amount, credited back after full repayment.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Max Installments --}}
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Maximum Installments</label>
                            <input type="number" step="1" min="1" max="120" name="max_installments"
                                class="form-control" value="{{ $get('max_installments', 48) }}" />
                            <div class="fv-feedback text-danger fs-7 mt-2"></div>
                            <div class="form-text">Maximum number of installments for advance recovery.</div>
                        </div>
                    </div>

                    {{-- Interest Distribution — fixed, read-only --}}
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Interest Distribution Dates</label>
                            <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                <span class="badge badge-primary fs-7 py-2 px-3">30 June</span>
                                <span class="badge badge-info fs-7 py-2 px-3">31 December</span>
                            </div>
                            <div class="form-text">
                                Bank interest is credited to employee accounts proportionately at each fiscal
                                half-year end. These dates are fixed and not configurable.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="separator separator-dashed my-8"></div>

                <div class="d-flex justify-content-end">
                    <button type="button" id="btn_save_settings" class="btn btn-primary">
                        <span class="indicator-label">
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
        var BidaCpfSettingConfig = {
            updateUrl: "{{ route('settings.update') }}",
            csrfToken: "{{ csrf_token() }}",
        };
    </script>
@endsection

@push('page-js')
    <script src="{{ asset('js/settings/index.js') }}"></script>
@endpush
