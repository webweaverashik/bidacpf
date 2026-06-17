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

                {{-- Security & Notifications --}}
                <div class="separator separator-dashed my-8"></div>

                <div class="row">
                    <div class="col-12">
                        <h4 class="fw-bold text-gray-900 mb-1">Security &amp; Notifications</h4>
                        <div class="text-muted fs-7 mb-6">
                            Runtime feature switches. Changes take effect immediately for new logins and notifications.
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Login OTP --}}
                    <div class="col-md-4">
                        <div class="fv-row mb-7">
                            <label class="form-check form-switch form-check-custom form-check-solid align-items-start">
                                <input class="form-check-input me-3" type="checkbox" name="otp_enabled" value="1"
                                    @checked(filter_var($get('otp_enabled', '0'), FILTER_VALIDATE_BOOLEAN))>
                                <span class="d-flex flex-column">
                                    <span class="fw-semibold fs-6 text-gray-900">Login OTP</span>
                                    <span class="fw-semibold fs-7 text-gray-500">Email a one-time code at each login
                                        (two-step verification).</span>
                                </span>
                            </label>
                            <div class="fv-feedback text-danger fs-7 mt-2"></div>
                        </div>
                    </div>

                    {{-- In-App Notifications --}}
                    <div class="col-md-4">
                        <div class="fv-row mb-7">
                            <label class="form-check form-switch form-check-custom form-check-solid align-items-start">
                                <input class="form-check-input me-3" type="checkbox" name="notify_app_enabled"
                                    value="1" @checked(filter_var($get('notify_app_enabled', '1'), FILTER_VALIDATE_BOOLEAN))>
                                <span class="d-flex flex-column">
                                    <span class="fw-semibold fs-6 text-gray-900">In-App Notifications</span>
                                    <span class="fw-semibold fs-7 text-gray-500">Show system events in the header
                                        notification centre.</span>
                                </span>
                            </label>
                            <div class="fv-feedback text-danger fs-7 mt-2"></div>
                        </div>
                    </div>

                    {{-- Email Notifications --}}
                    <div class="col-md-4">
                        <div class="fv-row mb-7">
                            <label class="form-check form-switch form-check-custom form-check-solid align-items-start">
                                <input class="form-check-input me-3" type="checkbox" name="notify_mail_enabled"
                                    value="1" @checked(filter_var($get('notify_mail_enabled', '1'), FILTER_VALIDATE_BOOLEAN))>
                                <span class="d-flex flex-column">
                                    <span class="fw-semibold fs-6 text-gray-900">Email Notifications</span>
                                    <span class="fw-semibold fs-7 text-gray-500">Also deliver system events to recipients
                                        by email.</span>
                                </span>
                            </label>
                            <div class="fv-feedback text-danger fs-7 mt-2"></div>
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

    @role('Admin')
        @php
            $mailScheme = config('mail.mailers.smtp.scheme');
            $mailEnc = $get('mail_encryption', $mailScheme === 'smtps' ? 'ssl' : 'tls');
            $mailer = $get('mailer', config('mail.default'));
            $mailPasswordSet = filled($get('mail_password')) || filled(config('mail.mailers.smtp.password'));
            $testTo = optional(auth()->user())->email;
        @endphp

        <div class="card mt-6">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h3 class="fw-bold text-gray-900 fs-3 m-0">
                        <span class="d-inline-flex align-items-center">
                            <i class="ki-outline ki-sms fs-2x me-2 text-primary"></i>
                            Mail (SMTP) Settings
                        </span>
                    </h3>
                </div>
            </div>

            <div class="card-body py-10">
                <div
                    class="notice d-flex align-items-center bg-light-warning rounded border-warning border border-dashed p-4 mb-8">
                    <i class="ki-outline ki-information-5 fs-2tx text-warning me-3"></i>
                    <div class="fw-semibold fs-7 text-gray-700">
                        These credentials drive OTP and notification emails. After saving, use
                        <strong>Send Test Email</strong> to confirm delivery before relying on it.
                    </div>
                </div>

                <form id="kt_mail_settings_form" class="w-100" novalidate="novalidate">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Mailer</label>
                                <select name="mailer" class="form-select">
                                    <option value="smtp" @selected($mailer === 'smtp')>SMTP</option>
                                    <option value="log" @selected($mailer === 'log')>Log (no real email)</option>
                                </select>
                                <div class="fv-feedback text-danger fs-7 mt-2"></div>
                                <div class="form-text">Use “Log” in development to write emails to the log instead of sending.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Encryption</label>
                                <select name="mail_encryption" class="form-select">
                                    <option value="tls" @selected($mailEnc === 'tls')>TLS (STARTTLS, usually port 587)
                                    </option>
                                    <option value="ssl" @selected($mailEnc === 'ssl')>SSL (implicit TLS, usually port 465)
                                    </option>
                                    <option value="none" @selected($mailEnc === 'none')>None</option>
                                </select>
                                <div class="fv-feedback text-danger fs-7 mt-2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">SMTP Host</label>
                                <input type="text" name="mail_host" class="form-control"
                                    value="{{ $get('mail_host', config('mail.mailers.smtp.host')) }}" />
                                <div class="fv-feedback text-danger fs-7 mt-2"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">SMTP Port</label>
                                <input type="number" name="mail_port" class="form-control"
                                    value="{{ $get('mail_port', config('mail.mailers.smtp.port')) }}" />
                                <div class="fv-feedback text-danger fs-7 mt-2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">SMTP Username</label>
                                <input type="text" name="mail_username" class="form-control" autocomplete="off"
                                    value="{{ $get('mail_username', config('mail.mailers.smtp.username')) }}" />
                                <div class="fv-feedback text-danger fs-7 mt-2"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">SMTP Password</label>
                                <input type="password" name="mail_password" class="form-control" autocomplete="new-password"
                                    placeholder="{{ $mailPasswordSet ? '•••••• (leave blank to keep current)' : 'Enter password' }}" />
                                <div class="fv-feedback text-danger fs-7 mt-2"></div>
                                <div class="form-text">
                                    @if ($mailPasswordSet)
                                        A password is saved. Leave blank to keep it, or type a new one to replace it.
                                    @else
                                        No password is currently set.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">From Address</label>
                                <input type="email" name="mail_from_address" class="form-control"
                                    value="{{ $get('mail_from_address', config('mail.from.address')) }}" />
                                <div class="fv-feedback text-danger fs-7 mt-2"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">From Name</label>
                                <input type="text" name="mail_from_name" class="form-control"
                                    value="{{ $get('mail_from_name', config('mail.from.name')) }}" />
                                <div class="fv-feedback text-danger fs-7 mt-2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div
                        class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-end gap-3">
                        <div class="fv-row mb-0" style="width: 500px;">
                            <label class="fw-semibold fs-7 text-muted mb-2">Send a test email to</label>
                            <div class="input-group">
                                {{-- No name attribute → excluded from the saved payload --}}
                                <input type="email" id="mail_test_to" class="form-control" value="{{ $testTo }}"
                                    placeholder="recipient@example.com" />
                                <button type="button" id="btn_test_mail" class="btn btn-light-primary">
                                    <span class="indicator-label">Send Test</span>
                                    <span class="indicator-progress">Sending&hellip;
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <button type="button" id="btn_save_mail_settings" class="btn btn-primary">
                            <span class="indicator-label">Save Mail Settings</span>
                            <span class="indicator-progress">Please wait&hellip;
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endrole

@endsection

@push('page-js')
    <script>
        var BidaCpfSettingConfig = {
            updateUrl: "{{ route('settings.update') }}",
            mailUpdateUrl: "{{ route('settings.mail.update') }}",
            mailTestUrl: "{{ route('settings.mail.test') }}",
            csrfToken: "{{ csrf_token() }}",
        };
    </script>

    <script src="{{ asset('js/settings/index.js') }}"></script>
    <script>
        document.getElementById("settings_users_link").classList.add("active");
    </script>
@endpush
