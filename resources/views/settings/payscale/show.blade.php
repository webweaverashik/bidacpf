@push('page-css')
    <link href="{{ asset('css/settings/payscale.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')
@section('title', 'Pay Scale Details')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">Pay Scale</h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">Settings</li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item">
                <a href="{{ route('payscale.index') }}" class="text-muted text-hover-primary">Pay Scales</a>
            </li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">{{ $payScale->name }}</li>
        </ul>
    </div>
@endsection

@section('content')
    <!--begin::Header-->
    <div class="card mb-6">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between py-6">
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center mb-1">
                    <span class="fs-2 fw-bold text-gray-900 me-3">{{ $payScale->name }}</span>
                    <span class="badge {{ $payScale->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                        {{ $payScale->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="fs-6 text-gray-500">
                    Effective Year {{ $payScale->effective_year }}
                    @if ($payScale->effective_from)
                        · From {{ $payScale->effective_from->format('d M Y') }}
                    @endif
                    @if ($payScale->effective_to)
                        to {{ $payScale->effective_to->format('d M Y') }}
                    @endif
                    · {{ $payScale->total_grades }} grades
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
                <a href="{{ route('payscale.index') }}" class="btn btn-light">
                    <i class="ki-outline ki-arrow-left fs-3"></i> Back
                </a>
                <button type="button"
                    class="btn {{ $payScale->is_active ? 'btn-light-warning' : 'btn-light-success' }} js-toggle-payscale"
                    data-url="{{ route('payscale.toggle', $payScale) }}" data-active="{{ $payScale->is_active ? 1 : 0 }}"
                    data-name="{{ $payScale->name }}">
                    <i class="ki-outline {{ $payScale->is_active ? 'ki-cross-circle' : 'ki-check-circle' }} fs-3"></i>
                    {{ $payScale->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </div>
        </div>
    </div>
    <!--end::Header-->

    <!--begin::Grid-->
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="fw-bold text-gray-900 fs-4 m-0">Grade &amp; Step Basic Salaries (৳)</h3>
            </div>
        </div>
        <div class="card-body py-4">
            @if ($matrix->isEmpty())
                <div class="text-center text-muted py-10">This pay scale has no steps.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-row-bordered align-middle text-nowrap gy-2 ashik-table">
                        <thead>
                            <tr class="fw-bold text-muted fs-8 text-uppercase text-center bg-light">
                                <th class="min-w-60px">Grade</th>
                                <th class="min-w-160px text-start">Pay Range</th>
                                @for ($n = 1; $n <= $maxStep; $n++)
                                    <th class="min-w-90px text-end">Step {{ $n }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-800">
                            @foreach ($matrix as $grade => $steps)
                                @php
                                    $min = $steps->min();
                                    $max = $steps->max();
                                    $range =
                                        $min === $max
                                            ? number_format($min) . ' (fixed)'
                                            : number_format($min) . ' – ' . number_format($max);
                                @endphp
                                <tr>
                                    <td class="text-center fw-bold bg-light-primary">{{ $grade }}</td>
                                    <td class="text-gray-600">{{ $range }}</td>
                                    @for ($n = 1; $n <= $maxStep; $n++)
                                        <td class="text-end">
                                            @if (isset($steps[$n]))
                                                {{ number_format($steps[$n]) }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    <!--end::Grid-->
@endsection

@push('page-js')
    <script>
        var BidaPayScaleConfig = {
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    <script src="{{ asset('js/settings/payscale-index.js') }}"></script>
    <script>
        var __ps = document.getElementById("settings_payscale_link");
        if (__ps) __ps.classList.add("active");
    </script>
@endpush
