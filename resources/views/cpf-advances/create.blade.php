@extends('layouts.app')

@section('title', 'New Advance')

@section('header-title')
    @include('cpf-advances.partials.page-header', ['heading' => 'New Advance', 'crumbs' => ['CPF Operation', 'CPF Advance/Loan', 'Advance Applications', 'New Advance']])
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <h2>New CPF Advance</h2>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('cpf-advances.index') }}" class="btn btn-sm btn-light">
                    <i class="ki-duotone ki-arrow-left fs-3"><span class="path1"></span><span class="path2"></span></i>Back
                </a>
            </div>
        </div>

        <form id="adv_form" action="{{ route('cpf-advances.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row g-9">
                    <!--begin::Employee-->
                    <div class="col-md-6 fv-row">
                        <label class="required form-label">Employee</label>
                        <select id="adv_employee_id" name="employee_id" class="form-select"
                            data-placeholder="Select employee">
                            <option></option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                                    {{ $employee->name }} ({{ $employee->cpf_account_no }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!--end::Employee-->

                    <!--begin::Application date-->
                    <div class="col-md-6 fv-row">
                        <label class="required form-label">Application Date</label>
                        <input id="adv_application_date" name="application_date" class="form-control"
                            placeholder="YYYY-MM-DD" value="{{ old('application_date', now()->toDateString()) }}" />
                    </div>
                    <!--end::Application date-->
                </div>

                <!--begin::Eligibility box-->
                <div id="adv_balance_box" class="alert alert-light-primary border border-primary border-dashed d-none mt-7 mb-0">
                    <div class="d-flex flex-wrap gap-7">
                        <div>
                            <div class="text-muted fs-7 text-uppercase">Current CPF Balance</div>
                            <div class="fs-3 fw-bold text-gray-900">BDT <span id="adv_balance_value">0</span></div>
                        </div>
                        <div>
                            <div class="text-muted fs-7 text-uppercase">Maximum Eligible Advance ({{ $defaults['limit_percentage'] }}%)</div>
                            <div class="fs-3 fw-bold text-primary">BDT <span id="adv_eligible_value">0</span></div>
                        </div>
                    </div>
                </div>
                <!--end::Eligibility box-->

                <div class="row g-9 mt-1">
                    <!--begin::Amount-->
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">Advance Amount (BDT)</label>
                        <input id="adv_requested_amount" type="number" name="requested_amount" class="form-control"
                            min="1" step="1" value="{{ old('requested_amount') }}" placeholder="0" />
                        <div class="form-text">Capped at the eligible limit above.</div>
                    </div>
                    <!--end::Amount-->

                    <!--begin::Rate-->
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">Interest Rate (%)</label>
                        <input id="adv_interest_rate" type="number" name="interest_rate" class="form-control"
                            min="0" max="100" step="0.01"
                            value="{{ old('interest_rate', $defaults['interest_rate']) }}" />
                        <div class="form-text">Default loaded from system settings.</div>
                    </div>
                    <!--end::Rate-->

                    <!--begin::Installments-->
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">Installments</label>
                        <input id="adv_installment_count" type="number" name="installment_count" class="form-control"
                            min="1" step="1" value="{{ old('installment_count', $defaults['installment_count']) }}" />
                    </div>
                    <!--end::Installments-->
                </div>

                @include('cpf-advances.partials.schedule-preview')

                <div class="row g-9 mt-1">
                    <!--begin::Application file-->
                    <div class="col-md-6 fv-row">
                        <label class="required form-label">Loan Application (PDF)</label>
                        <input type="file" name="application" class="form-control" accept="application/pdf" />
                        <div class="adv-file-hint mt-1">Scanned application, PDF only, max 5 MB.</div>
                    </div>
                    <!--end::Application file-->

                    <!--begin::Remarks-->
                    <div class="col-md-6 fv-row">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes">{{ old('remarks') }}</textarea>
                    </div>
                    <!--end::Remarks-->
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-3">
                <a href="{{ route('cpf-advances.index') }}" class="btn btn-light">Cancel</a>
                <button id="adv_submit_btn" type="submit" class="btn btn-primary">
                    <span class="indicator-label">Save Draft</span>
                    <span class="indicator-progress">Saving... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('page-css')
    <link href="{{ asset('css/cpf-advances/cpf-advance.css') }}" rel="stylesheet" type="text/css" />
@endpush

@push('page-js')
    <script>
        var BidaAdvanceFormConfig = {
            eligibilityUrl: "{{ route('cpf-advances.eligibility', ['employee' => '__ID__']) }}",
            isEdit: false,
            maxInstallments: {{ (int) $defaults['installment_count'] }},
            eligibleAmount: 0
        };
    </script>
    <script src="{{ asset('js/cpf-advances/bida-advance-form.js') }}"></script>
@endpush
