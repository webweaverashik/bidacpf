@extends('layouts.app')

@section('title', 'Dashboard')

@section('header-title')
    @include('dashboard.partials.page-header', ['heading' => 'Dashboard', 'crumbs' => ['Dashboard']])
@endsection

@section('content')
    <!--begin::Intro + fiscal-year selector-->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-5 mb-xl-8 gap-3">
        <div>
            <h2 class="fs-2 fw-bold text-gray-900 mb-1">Welcome back, {{ auth()->user()->name }}</h2>
            <span class="text-muted fs-7">A read-only overview of the BIDA CPF system — as of
                {{ now()->format('d M Y') }}.</span>
        </div>
        @include('dashboard.partials.fy-selector')
    </div>
    <!--end::Intro-->

    @include('dashboard.partials.stats-grid')

    @include('dashboard.partials.charts')

    <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
        <div class="col-12">
            @include('dashboard.partials.chart-card', [
                'id' => 'bida_chart_members_grade',
                'title' => 'Members by Grade',
                'subtitle' => 'Active CPF members across pay-scale grades',
                'height' => 320,
            ])
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
        <div class="col-12">
            @include('dashboard.partials.irregular-recoveries')
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
        <div class="col-xl-8">
            @include('dashboard.partials.recent-transactions')
        </div>
        <div class="col-xl-4">
            @include('dashboard.partials.recent-advances')
        </div>
    </div>
@endsection

@include('dashboard.partials.scripts')
