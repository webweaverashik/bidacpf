@push('page-css')
    <link href="{{ asset('css/settings/payscale.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')
@section('title', 'Pay Scales')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">Pay Scales</h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">Settings</li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">Pay Scales</li>
        </ul>
    </div>
@endsection

@section('content')
    @include('settings.partials.hero')

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <span class="d-inline-flex align-items-center">
                    <i class="ki-outline ki-parcel fs-2x me-2 text-primary"></i>
                    <h3 class="fw-bold text-gray-900 fs-3 m-0">National Pay Scales</h3>
                </span>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('payscale.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-cloud-add fs-2"></i>
                    Upload Pay Scale
                </a>
            </div>
        </div>

        <div class="card-body py-4">
            <div
                class="notice d-flex align-items-center bg-light-primary rounded border-primary border border-dashed p-4 mb-6">
                <i class="ki-outline ki-information-5 fs-2tx text-primary me-3"></i>
                <div class="fw-semibold fs-7 text-gray-700">
                    Pay scales are created by upload and cannot be edited or deleted afterwards. At most one pay
                    scale is active at a time &mdash; activating one deactivates the rest.
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-row-bordered align-middle gy-4 ashik-table">
                    <thead>
                        <tr class="fw-bold text-muted fs-7 text-uppercase">
                            <th class="min-w-200px">Name</th>
                            <th class="text-center w-100px">Year</th>
                            <th class="min-w-110px">Effective From</th>
                            <th class="min-w-110px">Effective To</th>
                            <th class="text-center w-90px">Grades</th>
                            <th class="text-center w-90px">Steps</th>
                            <th class="text-center w-110px">Status</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-700">
                        @forelse ($payScales as $ps)
                            <tr>
                                <td>
                                    <a href="{{ route('payscale.show', $ps) }}"
                                        class="text-gray-900 text-hover-primary fw-bold">{{ $ps->name }}</a>
                                </td>
                                <td class="text-center">{{ $ps->effective_year }}</td>
                                <td>{{ optional($ps->effective_from)->format('d M Y') ?? '—' }}</td>
                                <td>{{ optional($ps->effective_to)->format('d M Y') ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="badge badge-light-primary">{{ $ps->grades_count }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light-info">{{ $ps->steps_count }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $ps->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                                        {{ $ps->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('payscale.show', $ps) }}" class="btn btn-sm btn-light-primary me-1"
                                        title="View grid">
                                        <i class="ki-outline ki-eye fs-4"></i> View
                                    </a>
                                    <button type="button"
                                        class="btn btn-sm {{ $ps->is_active ? 'btn-light-warning' : 'btn-light-success' }} js-toggle-payscale"
                                        data-url="{{ route('payscale.toggle', $ps) }}"
                                        data-active="{{ $ps->is_active ? 1 : 0 }}" data-name="{{ $ps->name }}">
                                        <i
                                            class="ki-outline {{ $ps->is_active ? 'ki-cross-circle' : 'ki-check-circle' }} fs-4"></i>
                                        {{ $ps->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-10">
                                    No pay scales yet. Click <strong>Upload Pay Scale</strong> to add one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('page-js')
    <script>
        var BidaPayScaleConfig = {
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    <script src="{{ asset('js/settings/payscale-index.js') }}"></script>
    <script>
        document.getElementById("settings_payscale_link").classList.add("active");
    </script>
@endpush
