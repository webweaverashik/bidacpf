@extends('layouts.app')

@section('title', 'Edit Advance')

@section('header-title')
    @include('cpf-advances.partials.page-header', ['heading' => 'Edit Advance Draft', 'crumbs' => ['CPF Operation', 'CPF Advance/Loan', 'Advance Applications', 'Edit']])
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title"><h2>Edit Advance Draft — {{ $advance->advance_no }}</h2></div>
            <div class="card-toolbar">
                <a href="{{ route('cpf-advances.show', $advance) }}" class="btn btn-sm btn-light">
                    <i class="ki-duotone ki-arrow-left fs-3"><span class="path1"></span><span class="path2"></span></i>Back
                </a>
            </div>
        </div>

        <form id="adv_form" action="{{ route('cpf-advances.update', $advance) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="row g-9">
                    <!--begin::Employee (fixed)-->
                    <div class="col-md-6">
                        <label class="form-label">Employee</label>
                        <div class="form-control form-control-solid">
                            {{ $advance->employee->name }} ({{ $advance->employee->cpf_account_no }})
                        </div>
                    </div>
                    <!--end::Employee-->

                    <!--begin::Application date-->
                    <div class="col-md-6 fv-row">
                        <label class="required form-label">Application Date</label>
                        <input id="adv_application_date" name="application_date" class="form-control"
                            value="{{ old('application_date', $advance->application_date?->toDateString()) }}" />
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
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">Advance Amount (BDT)</label>
                        <input id="adv_requested_amount" type="number" name="requested_amount" class="form-control"
                            min="1" step="1" value="{{ old('requested_amount', $advance->requested_amount) }}" />
                    </div>
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">Interest Rate (%)</label>
                        <input id="adv_interest_rate" type="number" name="interest_rate" class="form-control"
                            min="0" max="100" step="0.01" value="{{ old('interest_rate', $advance->interest_rate) }}" />
                    </div>
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">Installments</label>
                        <input id="adv_installment_count" type="number" name="installment_count" class="form-control"
                            min="1" step="1" value="{{ old('installment_count', $advance->installment_count) }}" />
                    </div>
                </div>

                @include('cpf-advances.partials.schedule-preview')

                <div class="row g-9 mt-1">
                    <div class="col-md-6 fv-row">
                        <label class="form-label">Replace Loan Application (PDF)</label>
                        <input type="file" name="application" class="form-control" accept="application/pdf" />
                        @if ($advance->firstAttachment())
                            <div class="adv-file-hint mt-1">
                                Current:
                                <a href="{{ asset($advance->firstAttachment()->file_path) }}" target="_blank">
                                    {{ $advance->firstAttachment()->file_name }}
                                </a> — leave empty to keep it.
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6 fv-row">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $advance->remarks) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-3">
                <a href="{{ route('cpf-advances.show', $advance) }}" class="btn btn-light">Cancel</a>
                <button id="adv_submit_btn" type="submit" class="btn btn-primary">
                    <span class="indicator-label">Update Draft</span>
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
            isEdit: true,
            employeeId: {{ (int) $advance->employee_id }},
            maxInstallments: {{ (int) $defaults['max_installments'] }},
            eligibleAmount: {{ (int) $defaults['eligible_amount'] }}
        };
    </script>
    <script src="{{ asset('js/cpf-advances/bida-advance-form.js') }}"></script>
@endpush
