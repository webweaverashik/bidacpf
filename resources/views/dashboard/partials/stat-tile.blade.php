{{-- Reusable headline stat card.
     Params:
       $label    string   tile caption
       $value    int      numeric value
       $icon     string   ki-outline icon name (e.g. 'ki-people')
       $color    string   theme colour (primary|success|warning|danger|info)
       $money    bool     prefix with the Taka sign + format as currency (default false)
       $link     string   optional href for the footer link
       $linkText string   optional footer link label (default 'View details')
       $subtitle string   optional small caption under the value
--}}
@php
    $money = $money ?? false;
    $color = $color ?? 'primary';
    $link = $link ?? null;
    $linkText = $linkText ?? 'View details';
    $subtitle = $subtitle ?? null;
    $statKey = $statKey ?? null;
    $subStatKey = $subStatKey ?? null;
    $prefix = $money ? '৳ ' : '';
@endphp
<div class="card card-flush h-100 bida-stat-card">
    <div class="card-body d-flex flex-column justify-content-between p-6">
        <div class="d-flex align-items-center">
            <div class="symbol symbol-50px me-4">
                <span class="symbol-label bg-light-{{ $color }}">
                    <i class="ki-outline {{ $icon }} fs-2x text-{{ $color }}"></i>
                </span>
            </div>
            <div class="d-flex flex-column">
                <span class="fs-2hx fw-bold text-gray-900 lh-1 bida-countup" data-bida-value="{{ (int) $value }}"
                    data-bida-money="{{ $money ? 1 : 0 }}"
                    @if ($statKey) data-bida-stat="{{ $statKey }}" @endif>{{ $prefix }}{{ number_format((int) $value) }}</span>
                <span class="fs-7 fw-semibold text-gray-500 mt-2">{{ $label }}</span>
            </div>
        </div>

        @if ($subtitle)
            <div class="fs-8 text-muted mt-3"
                @if ($subStatKey) data-bida-substat="{{ $subStatKey }}" @endif>{{ $subtitle }}
            </div>
        @endif

        @if ($link)
            <a href="{{ $link }}"
                class="text-primary text-hover-primary fw-semibold fs-7 mt-4 d-inline-flex align-items-center">
                {{ $linkText }}
                <i class="ki-outline ki-arrow-right fs-6 ms-1"></i>
            </a>
        @endif
    </div>
</div>
