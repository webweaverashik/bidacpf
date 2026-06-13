{{-- Card wrapper around an ApexCharts mount point.

     Params:
       $id        string  unique mount-element id
       $title     string
       $subtitle  string  optional
       $height    int     mount min-height in px (default 350)
       $tabs      bool    when true, render a Chart/List toggle + a list pane
                          (#{{ $id }}_list) that BidaDashboard fills with a table.
       $emptyText string  message shown when the chart/list has no data.

     The chart is drawn by BidaDashboard into #{{ $id }}; when the data is empty
     it hides the canvas and reveals the [data-bida-empty] overlay instead. The
     list table is rendered into #{{ $id }}_list (and shows its own empty state). --}}
@php
    $height = $height ?? 350;
    $subtitle = $subtitle ?? null;
    $tabs = $tabs ?? false;
    $emptyText = $emptyText ?? 'No data to display';
@endphp
<div class="card card-flush h-100">
    <div class="card-header pt-5">
        <div class="card-title d-flex flex-column">
            <span class="fs-4 fw-bold text-gray-900">{{ $title }}</span>
            @if ($subtitle)
                <span class="fs-7 text-muted mt-1">{{ $subtitle }}</span>
            @endif
        </div>

        @if ($tabs)
            <div class="card-toolbar">
                <div class="bida-view-toggle" role="tablist" aria-label="Switch between chart and list">
                    <button type="button" class="bida-view-btn active" data-bida-view="chart"
                        data-bida-target="{{ $id }}" aria-selected="true">
                        <i class="ki-outline ki-chart-simple fs-6"></i><span>Chart</span>
                    </button>
                    <button type="button" class="bida-view-btn" data-bida-view="list"
                        data-bida-target="{{ $id }}" aria-selected="false">
                        <i class="ki-outline ki-row-horizontal fs-6"></i><span>List</span>
                    </button>
                </div>
            </div>
        @endif
    </div>
    <div class="card-body pt-4">
        {{-- Chart view --}}
        <div class="bida-view-pane" data-bida-pane="chart">
            <div class="bida-chart-wrap position-relative" style="min-height: {{ $height }}px;">
                <div id="{{ $id }}" class="bida-chart" style="min-height: {{ $height }}px;"></div>
                <div class="bida-empty d-none" data-bida-empty="{{ $id }}">
                    <div class="bida-empty-inner text-center">
                        <i class="ki-outline ki-chart-simple fs-3x text-gray-300 mb-3"></i>
                        <div class="fw-semibold text-gray-500">{{ $emptyText }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- List view --}}
        @if ($tabs)
            <div id="{{ $id }}_list" class="bida-view-pane bida-chart-list-pane d-none" data-bida-pane="list"
                style="min-height: {{ $height }}px;"></div>
        @endif
    </div>
</div>
