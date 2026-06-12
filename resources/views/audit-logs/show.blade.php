@extends('layouts.app')

@section('title', 'Audit Log Detail')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Audit Log Detail
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('audit-logs.index') }}" class="text-muted text-hover-primary">Audit Logs</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">#{{ $log->id }}</li>
        </ul>
    </div>
@endsection

@section('content')
    @php
        $eventClass = match ($log->event) {
            'created' => 'badge-light-success',
            'updated' => 'badge-light-warning',
            'deleted' => 'badge-light-danger',
            'restored' => 'badge-light-info',
            default => 'badge-light',
        };
        $subjectType = class_basename($log->subject_type ?? '');
    @endphp

    <div class="card mb-5">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2 class="fw-bold">{{ Str::ucfirst($log->description ?: '—') }}</h2>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('audit-logs.index') }}" class="btn btn-light-primary">
                    <i class="ki-duotone ki-arrow-left fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span></i>Back to Audit Logs
                </a>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="row gy-5">
                <div class="col-md-3 col-6">
                    <div class="text-muted fw-semibold fs-7 text-uppercase mb-1">Event</div>
                    <span class="badge {{ $eventClass }}">{{ Str::headline($log->event ?? '—') }}</span>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-muted fw-semibold fs-7 text-uppercase mb-1">Log Name</div>
                    <span class="fw-bold text-gray-800">{{ Str::headline($log->log_name ?? '—') }}</span>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-muted fw-semibold fs-7 text-uppercase mb-1">Subject</div>
                    @if ($subjectName)
                        @if ($isEmployeeSubject)
                            <a href="{{ route('employees.show', $log->subject_id) }}" target="_blank"
                                class="fw-bold text-gray-800 text-hover-primary">{{ $subjectName }}</a>
                        @else
                            <span class="fw-bold text-gray-800">{{ $subjectName }}</span>
                        @endif
                        <div class="text-muted fs-8">
                            {{ $subjectType ?: '—' }}@if ($log->subject_id)
                                #{{ $log->subject_id }}
                            @endif
                        </div>
                    @else
                        <span class="fw-bold text-gray-800">
                            {{ $subjectType ?: '—' }}
                            @if ($log->subject_id)
                                <span class="text-muted fs-8">#{{ $log->subject_id }}</span>
                            @endif
                        </span>
                    @endif
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-muted fw-semibold fs-7 text-uppercase mb-1">Causer</div>
                    @if ($log->causer)
                        @can('user.view')
                            <a href="{{ route('users.show', $log->causer_id) }}" target="_blank"
                                class="fw-bold text-gray-800 text-hover-primary">{{ $log->causer->name }}</a>
                        @else
                            <span class="fw-bold text-gray-800">{{ $log->causer->name }}</span>
                        @endcan
                    @else
                        <span class="badge badge-light-dark">System</span>
                    @endif
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-muted fw-semibold fs-7 text-uppercase mb-1">When</div>
                    <span class="fw-bold text-gray-800">
                        {{ optional($log->created_at)->format('h:i:s A, d M Y') ?? '—' }}
                    </span>
                    <div class="text-muted fs-8">{{ optional($log->created_at)->diffForHumans() }}</div>
                </div>
                @if ($log->batch_uuid)
                    <div class="col-md-9 col-6">
                        <div class="text-muted fw-semibold fs-7 text-uppercase mb-1">Batch</div>
                        <span class="fw-semibold text-gray-700 fs-7">{{ $log->batch_uuid }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="fw-bold">Changes</h3>
            </div>
        </div>
        <div class="card-body pt-0">
            {!! $changesHtml !!}
        </div>
    </div>
@endsection
