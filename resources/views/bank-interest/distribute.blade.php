@push('page-css')
    <link href="{{ asset('css/bank-interest/bank-interest.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'New Interest Distribution')

@section('header-title')
    @include('bank-interest.partials.page-header', [
        'heading' => 'New Distribution',
        'crumbs' => ['CPF Operation', 'Bank Interest', 'New Distribution'],
    ])
@endsection

@php
    // Valid cut-off dates only: 30 June / 31 December, most recent first,
    // never in the future. Covers the current and previous few years.
    $cutoffs = [];
    $currentYear = (int) now()->year;
    for ($y = $currentYear; $y >= $currentYear - 4; $y--) {
        foreach ([['m' => 12, 'd' => 31, 'lbl' => '31 December'], ['m' => 6, 'd' => 30, 'lbl' => '30 June']] as $cd) {
            $date = \Carbon\Carbon::create($y, $cd['m'], $cd['d']);
            if ($date->isFuture()) {
                continue;
            }
            $cutoffs[] = [
                'value' => $date->toDateString(),
                'label' => $cd['lbl'] . ' ' . $y,
                'fy' => \App\Support\FiscalYearService::fromDate($date),
            ];
        }
    }
@endphp

@section('content')
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <h2>Create Interest Distribution Batch</h2>
                    </div>
                </div>

                <form id="bida_interest_form" class="form">
                    <div class="card-body">
                        <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed mb-9 p-6">
                            <i class="ki-outline ki-information-5 fs-2tx text-primary me-4"></i>
                            <div class="d-flex flex-stack flex-grow-1">
                                <div class="fw-semibold">
                                    <div class="fs-6 text-gray-700">
                                        Bank interest is distributed twice a year against the CPF balances as of the
                                        cut-off date (30 June / 31 December). The system computes each member's
                                        proportional share. The result is saved as a <span class="fw-bold">Draft</span>
                                        for you to review before submitting for approval.
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Cut-off date (constrained to 30 Jun / 31 Dec) --}}
                        <div class="row mb-7">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Cut-off Date</label>
                            <div class="col-lg-8">
                                <select name="distribution_date" id="bi_distribution_date"
                                    class="form-select form-select-solid">
                                    <option value="" data-fy="">Select cut-off date…</option>
                                    @foreach ($cutoffs as $c)
                                        <option value="{{ $c['value'] }}" data-fy="{{ $c['fy'] }}">
                                            {{ $c['label'] }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Only the bi-annual cut-offs (30 June / 31 December) can be selected.
                                </div>
                            </div>
                        </div>

                        {{-- Fiscal year (auto from cut-off) --}}
                        <div class="row mb-7">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Fiscal Year</label>
                            <div class="col-lg-8">
                                <input type="text" name="fiscal_year" id="bi_fiscal_year"
                                    class="form-control form-control-solid" placeholder="YYYY-YYYY" readonly />
                                <div class="form-text">Derived automatically from the selected cut-off date.</div>
                            </div>
                        </div>

                        {{-- Total interest received --}}
                        <div class="row mb-7">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Total Bank Interest
                                (Tk)</label>
                            <div class="col-lg-8">
                                <input type="number" name="total_interest_amount" id="bi_total_interest"
                                    class="form-control form-control-solid" placeholder="0" min="1" step="1" />
                                <div class="form-text">Whole BDT amount received from the CPF bank account.</div>
                            </div>
                        </div>

                        {{-- Remarks --}}
                        <div class="row mb-2">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">Remarks</label>
                            <div class="col-lg-8">
                                <textarea name="remarks" class="form-control form-control-solid" rows="3"
                                    placeholder="Optional notes for this distribution"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <a href="{{ route('bank-interest.index') }}"
                            class="btn btn-light btn-active-light-primary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="bida_interest_submit">
                            <span class="indicator-label">
                                <i class="ki-outline ki-calculator fs-3"></i>Generate Distribution
                            </span>
                            <span class="indicator-progress">
                                Computing… <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('page-js')
    <script>
        var BidaInterestFormConfig = {
            formId: 'bida_interest_form',
            submitId: 'bida_interest_submit',
            dateId: 'bi_distribution_date',
            fiscalYearId: 'bi_fiscal_year',
            storeUrl: "{{ route('bank-interest.store') }}",
            csrf: "{{ csrf_token() }}"
        };
    </script>
    <script src="{{ asset('js/bank-interest/bida-interest-form.js') }}"></script>
@endpush
