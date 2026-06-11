@extends('layouts.app')

@section('title', 'Record Recovery')

@section('header-title')
    @include('cpf-advances.partials.page-header', ['heading' => 'Record Recovery', 'crumbs' => ['CPF Operation', 'CPF Advance/Loan', 'Recovery Posting', 'New']])
@endsection

@section('content')
    <div class="card mb-6">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">Record Recovery — {{ $advance->advance_no }}</h2>
                <div class="text-gray-700 fw-semibold">{{ $advance->employee->name }} <span class="text-muted">({{ $advance->employee->cpf_account_no }})</span></div>
            </div>
            <div class="text-end">
                <div class="text-muted fs-7 text-uppercase">Outstanding Balance</div>
                <div class="fs-2 fw-bold text-danger">BDT {{ number_format($advance->outstanding_amount) }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title"><h3>Recovery Details</h3></div></div>

        <form id="rec_form" action="{{ route('cpf-advances.recovery.store', $advance) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row g-9">
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">Recovery Date</label>
                        <input id="rec_recovery_date" name="recovery_date" class="form-control"
                            value="{{ old('recovery_date', now()->toDateString()) }}" />
                    </div>
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">Amount (BDT)</label>
                        <input id="rec_amount" type="number" name="amount" class="form-control" min="1"
                            max="{{ $advance->outstanding_amount }}" step="1" value="{{ old('amount') }}" />
                        <div class="form-text">Max {{ number_format($advance->outstanding_amount) }}.</div>
                    </div>
                    <div class="col-md-4 fv-row">
                        <label class="form-label">Deposit Date</label>
                        <input id="rec_deposit_date" name="deposit_date" class="form-control"
                            value="{{ old('deposit_date') }}" />
                    </div>
                </div>

                <div class="row g-9 mt-1">
                    <div class="col-md-4 fv-row">
                        <label class="form-label">Deposit Reference / Slip No.</label>
                        <input type="text" name="deposit_reference" class="form-control" value="{{ old('deposit_reference') }}" />
                    </div>
                    <div class="col-md-4 fv-row">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" />
                    </div>
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">Deposit Slip</label>
                        <input type="file" name="deposit_slip" class="form-control" accept=".pdf,.jpg,.jpeg,.png" />
                        <div class="adv-file-hint mt-1">PDF or image, max 5 MB.</div>
                    </div>
                </div>

                <div class="row g-9 mt-1">
                    <div class="col-12 fv-row">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-3">
                <a href="{{ route('cpf-advances.show', $advance) }}" class="btn btn-light">Cancel</a>
                <button id="rec_submit_btn" type="submit" class="btn btn-primary">
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
        var BidaRecoveryFormConfig = { outstanding: {{ (int) $advance->outstanding_amount }}, isEdit: false };
    </script>
    <script src="{{ asset('js/cpf-advances/bida-recovery-form.js') }}"></script>
@endpush
