@extends('layouts.app')

@section('title', 'Edit Recovery')

@section('header-title')
    @include('cpf-advances.partials.page-header', ['heading' => 'Edit Recovery', 'crumbs' => ['CPF Operation', 'CPF Advance/Loan', 'Recovery Posting', 'Edit']])
@endsection

@section('content')
    <div class="card mb-6">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">Edit Recovery — {{ $recovery->recovery_no }}</h2>
                <div class="text-gray-700 fw-semibold">{{ $advance->advance_no }} · {{ $advance->employee->name }}</div>
            </div>
            <div class="text-end">
                <div class="text-muted fs-7 text-uppercase">Outstanding Balance</div>
                <div class="fs-2 fw-bold text-danger">BDT {{ number_format($advance->outstanding_amount) }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title"><h3>Recovery Details</h3></div></div>

        <form id="rec_form" action="{{ route('cpf-advances.recovery.update', [$advance, $recovery]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="row g-9">
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">Recovery Date</label>
                        <input id="rec_recovery_date" name="recovery_date" class="form-control"
                            value="{{ old('recovery_date', $recovery->recovery_date?->toDateString()) }}" />
                    </div>
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">Amount (BDT)</label>
                        <input id="rec_amount" type="number" name="amount" class="form-control" min="1"
                            max="{{ $advance->outstanding_amount }}" step="1"
                            value="{{ old('amount', $recovery->amount) }}" />
                        <div class="form-text">Max {{ number_format($advance->outstanding_amount) }}.</div>
                    </div>
                    <div class="col-md-4 fv-row">
                        <label class="form-label">Deposit Date</label>
                        <input id="rec_deposit_date" name="deposit_date" class="form-control"
                            value="{{ old('deposit_date', $recovery->deposit_date?->toDateString()) }}" />
                    </div>
                </div>

                <div class="row g-9 mt-1">
                    <div class="col-md-4 fv-row">
                        <label class="form-label">Deposit Reference / Slip No.</label>
                        <input type="text" name="deposit_reference" class="form-control"
                            value="{{ old('deposit_reference', $recovery->deposit_reference) }}" />
                    </div>
                    <div class="col-md-4 fv-row">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control"
                            value="{{ old('bank_name', $recovery->bank_name) }}" />
                    </div>
                    <div class="col-md-4 fv-row">
                        <label class="form-label">Replace Deposit Slip</label>
                        <input type="file" name="deposit_slip" class="form-control" accept=".pdf,.jpg,.jpeg,.png" />
                        @if ($recovery->firstAttachment())
                            <div class="adv-file-hint mt-1">
                                Current:
                                <a href="{{ asset($recovery->firstAttachment()->file_path) }}" target="_blank">{{ $recovery->firstAttachment()->file_name }}</a>
                                — leave empty to keep it.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="row g-9 mt-1">
                    <div class="col-12 fv-row">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $recovery->remarks) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-3">
                <a href="{{ route('cpf-advances.recovery.show', [$advance, $recovery]) }}" class="btn btn-light">Cancel</a>
                <button id="rec_submit_btn" type="submit" class="btn btn-primary">
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
        var BidaRecoveryFormConfig = { outstanding: {{ (int) $advance->outstanding_amount }}, isEdit: true };
    </script>
    <script src="{{ asset('js/cpf-advances/bida-recovery-form.js') }}"></script>
@endpush
