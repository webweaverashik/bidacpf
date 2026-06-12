@push('page-css')
    <link href="{{ asset('css/cpf-settlements/cpf-settlements.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'New Final Settlement')

@section('header-title')
    @include('cpf-settlements.partials.page-header', [
        'heading' => 'New Settlement',
        'crumbs' => ['Final Settlement', 'New Settlement'],
    ])
@endsection

@section('content')
    <div class="row g-6">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <h2>Create Final Settlement</h2>
                    </div>
                </div>

                <form id="bida_settlement_form" class="form" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed mb-9 p-6">
                            <i class="ki-outline ki-information-5 fs-2tx text-primary me-4"></i>
                            <div class="fw-semibold fs-6 text-gray-700">
                                A final settlement closes the member's CPF account. On approval the system posts the
                                closing entry, writes off any open advance, and marks the member retired / resigned /
                                deceased. The result is saved as a <span class="fw-bold">Draft</span> for review.
                            </div>
                        </div>

                        {{-- Employee --}}
                        <div class="row mb-7">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Member</label>
                            <div class="col-lg-8">
                                <select name="employee_id" id="stl_employee" class="form-select"
                                    data-control="select2" data-placeholder="Select a member…">
                                    <option></option>
                                    @foreach ($employees as $e)
                                        <option value="{{ $e->id }}" data-name="{{ $e->name }}">
                                            {{ $e->name }} ({{ $e->cpf_account_no }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Only active members are listed.</div>
                            </div>
                        </div>

                        {{-- Settlement type --}}
                        <div class="row mb-7">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Type</label>
                            <div class="col-lg-8">
                                <select name="settlement_type" id="stl_type" class="form-select"
                                    data-control="select2" data-hide-search="true" data-placeholder="Select type…">
                                    <option></option>
                                    @foreach ($types as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Application date --}}
                        <div class="row mb-7">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Application Date</label>
                            <div class="col-lg-8">
                                <input type="text" name="application_date" id="stl_application_date"
                                    class="form-control" placeholder="YYYY-MM-DD" />
                            </div>
                        </div>

                        {{-- Settlement date --}}
                        <div class="row mb-7">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Settlement Date</label>
                            <div class="col-lg-8">
                                <input type="text" name="settlement_date" id="stl_settlement_date"
                                    class="form-control" placeholder="YYYY-MM-DD" />
                                <div class="form-text">Effective date of separation. The closing entry posts on this date.
                                </div>
                            </div>
                        </div>

                        {{-- Payee (nominee for deceased) --}}
                        <div class="row mb-7">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">
                                Payee <span class="stl-warn text-danger" id="stl_payee_required">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="payee_name" id="stl_payee_name"
                                    class="form-control mb-3" placeholder="Defaults to the member" />
                                <input type="text" name="payee_relation" id="stl_payee_relation"
                                    class="form-control mb-3"
                                    placeholder="Relation (e.g. Self, Spouse, Son)" />
                                <textarea name="payee_detail" class="form-control" rows="2"
                                    placeholder="Address / bank account / notes (optional)"></textarea>
                                <div class="form-text" id="stl_payee_hint">
                                    Leave blank to pay the member. For a deceased member, name the nominee.
                                </div>
                            </div>
                        </div>

                        {{-- Supporting document --}}
                        <div class="row mb-7">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Supporting Document</label>
                            <div class="col-lg-8">
                                <input type="file" name="document" id="stl_document"
                                    class="form-control" accept="application/pdf" />
                                <div class="form-text">Retirement order / resignation letter / death certificate (PDF, max 5
                                    MB).</div>
                            </div>
                        </div>

                        {{-- Remarks --}}
                        <div class="row mb-2">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Remarks</label>
                            <div class="col-lg-8">
                                <textarea name="remarks" class="form-control" rows="3"
                                    placeholder="Optional notes for this settlement"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <a href="{{ route('cpf-settlements.index') }}"
                            class="btn btn-light btn-active-light-primary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="bida_settlement_submit">
                            <span class="indicator-label"><i class="ki-outline ki-check fs-3"></i>Create Draft</span>
                            <span class="indicator-progress">
                                Saving… <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============================ Live preview ============================ --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <h2>Payout Preview</h2>
                    </div>
                </div>
                <div class="card-body">
                    <div class="stl-preview" id="stl_preview">
                        <div class="stl-preview-row">
                            <span class="label">Closing Balance</span>
                            <span class="value" id="stl_pv_closing">৳ 0</span>
                        </div>
                        <div class="stl-preview-row">
                            <span class="label">Outstanding Advance</span>
                            <span class="value" id="stl_pv_outstanding">৳ 0</span>
                        </div>
                        <div class="stl-preview-row total">
                            <span class="label">Total Payable</span>
                            <span class="value" id="stl_pv_payable">৳ 0</span>
                        </div>
                    </div>

                    <div class="alert alert-warning align-items-center mt-5 d-none" id="stl_eligibility_warn">
                        <i class="ki-outline ki-information fs-2 text-warning me-3"></i>
                        <span id="stl_eligibility_text"></span>
                    </div>

                    <div class="text-muted fs-8 mt-5">
                        Figures are computed from the CPF ledger as of the selected settlement date and are
                        re-checked at approval time.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-js')
@endpush

@push('page-js')
    <script>
        var BidaSettlementFormConfig = {
            mode: 'create',
            formId: 'bida_settlement_form',
            submitId: 'bida_settlement_submit',
            actionUrl: "{{ route('cpf-settlements.store') }}",
            spoofMethod: null,
            employeeId: null,
            employeeSelectId: 'stl_employee',
            typeSelectId: 'stl_type',
            applicationDateId: 'stl_application_date',
            settlementDateId: 'stl_settlement_date',
            payee: {
                nameId: 'stl_payee_name',
                relationId: 'stl_payee_relation',
                requiredMarkId: 'stl_payee_required',
                hintId: 'stl_payee_hint'
            },
            previewUrlBase: "{{ url('cpf-settlements/preview') }}",
            preview: {
                closingId: 'stl_pv_closing',
                outstandingId: 'stl_pv_outstanding',
                payableId: 'stl_pv_payable',
                warnId: 'stl_eligibility_warn',
                warnTextId: 'stl_eligibility_text'
            },
            csrf: "{{ csrf_token() }}"
        };
    </script>
    <script src="{{ asset('js/cpf-settlements/bida-settlement-form.js') }}"></script>
@endpush
