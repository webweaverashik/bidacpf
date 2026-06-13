{{-- Generic strip of count tiles, used for both the admin approval queue and the
     officer work list. Each item may carry an optional 'permission' key; when
     present the tile is only shown if the viewer holds it.

     Params:
       $title string
       $items array<int, array{label,count,route,icon,color, permission?}>
       $emptyText string optional message when all counts are zero
--}}
@php
    $items = $items ?? [];
    $visible = collect($items)->filter(
        fn($i) => empty($i['permission']) || auth()->user()->can($i['permission']),
    );
    $totalCount = $visible->sum('count');
    $emptyText = $emptyText ?? 'Nothing needs your attention right now.';
@endphp

<div class="card card-flush mb-5 mb-xl-8">
    <div class="card-header pt-5">
        <div class="card-title d-flex align-items-center">
            <span class="fs-4 fw-bold text-gray-900">{{ $title }}</span>
            @if ($totalCount > 0)
                <span class="badge badge-circle badge-danger ms-3">{{ number_format($totalCount) }}</span>
            @else
                <span class="badge badge-light-success ms-3">All clear</span>
            @endif
        </div>
    </div>
    <div class="card-body pt-2">
        @if ($visible->isEmpty())
            <div class="text-muted fs-7 py-4">{{ $emptyText }}</div>
        @else
            <div class="row g-5">
                @foreach ($visible as $item)
                    <div class="col-sm-6 col-xl-3">
                        <a href="{{ route($item['route']) }}"
                            class="card-rounded d-flex align-items-center p-5 h-100 bida-mini-tile bg-light-{{ $item['color'] }}">
                            <i class="ki-outline {{ $item['icon'] }} fs-2x text-{{ $item['color'] }} me-4"></i>
                            <div class="d-flex flex-column">
                                <span class="fs-2 fw-bold text-gray-900 lh-1">{{ number_format($item['count']) }}</span>
                                <span class="fs-8 fw-semibold text-gray-600 mt-1">{{ $item['label'] }}</span>
                            </div>
                            @if ($item['count'] > 0)
                                <i class="ki-outline ki-arrow-right fs-2 text-{{ $item['color'] }} ms-auto"></i>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
