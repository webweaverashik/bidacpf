{{-- Card wrapper around an ApexCharts mount point. The chart itself is drawn by
     BidaDashboard (page JS), which reads its series from window.BidaDashboardConfig
     and targets the #{{ $id }} element.

     Params:
       $id       string  unique mount-element id
       $title    string
       $subtitle string optional
       $height   int     mount min-height in px (default 350)
--}}
@php
    $height = $height ?? 350;
    $subtitle = $subtitle ?? null;
@endphp
<div class="card card-flush h-100">
    <div class="card-header pt-5">
        <div class="card-title d-flex flex-column">
            <span class="fs-4 fw-bold text-gray-900">{{ $title }}</span>
            @if ($subtitle)
                <span class="fs-7 text-muted mt-1">{{ $subtitle }}</span>
            @endif
        </div>
    </div>
    <div class="card-body pt-4">
        <div id="{{ $id }}" class="bida-chart" style="min-height: {{ $height }}px;"></div>
    </div>
</div>
