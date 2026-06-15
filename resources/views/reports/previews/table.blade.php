{{--
    On-screen preview of a summary report (one page at a time).

    The full envelope is built server-side; this view renders a single page of
    rows ($pageRows) plus an AJAX pagination bar. Serial numbers are already
    embedded in each row by ReportService, so they stay continuous across pages.

    $report   : ['title','subtitle','meta','headings','aligns','rows','summary']
    $pageRows : the slice of rows for the current page
    $page     : current page (1-based)
    $perPage  : rows per page
    $total    : total row count
    $lastPage : last page number
    $offset   : index of the first row on this page (0-based)
--}}
@php
    $pageRows = $pageRows ?? ($report['rows'] ?? []);
    $page = $page ?? 1;
    $perPage = $perPage ?? 20;
    $total = $total ?? count($pageRows);
    $lastPage = $lastPage ?? 1;
    $offset = $offset ?? 0;

    $headings = $report['headings'] ?? [];
    $colCount = count($headings);
    $aligns = $report['aligns'] ?? [];
    $alignCls = fn($i) => match ($aligns[$i] ?? 'left') {
        'num', 'right' => 'text-end',
        'center' => 'text-center',
        default => 'text-start',
    };
    $isSerial = fn($i) => ($headings[$i] ?? '') === '#';

    $from = $total ? $offset + 1 : 0;
    $to = min($offset + $perPage, $total);

    $appendRows = $appendRows ?? [];
@endphp

<div class="rpt-preview" data-preview-title="{{ $report['title'] ?? 'Report' }}"
    data-preview-subtitle="{{ $report['subtitle'] ?? '' }}">

    @if (!empty($report['meta']))
        <div class="d-flex flex-wrap gap-6 mb-5">
            @foreach ($report['meta'] as $m)
                <div>
                    <div class="text-muted fs-8 text-uppercase">{{ $m['label'] ?? '' }}</div>
                    <div class="fw-bold fs-6">{{ $m['value'] ?? '' }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($total === 0)
        <div class="rpt-empty text-muted text-center py-15">
            <i class="ki-duotone ki-information-5 fs-3x text-gray-300 mb-3">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            <div class="fs-6">No records match the selected options.</div>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-row-bordered table-row-dashed align-middle gy-3 fs-7 ashik-table mb-0">
                <thead>
                    <tr class="fw-bold fs-8 text-uppercase text-gray-600 gs-0 border-bottom border-gray-200">
                        @foreach ($headings as $i => $h)
                            <th class="{{ $alignCls($i) }} {{ $isSerial($i) ? 'w-40px text-center' : '' }}">
                                {{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pageRows as $row)
                        <tr>
                            @foreach ($row as $i => $cell)
                                <td
                                    class="{{ $alignCls($i) }} {{ $isSerial($i) ? 'w-40px text-center text-muted' : '' }}">
                                    {{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    @foreach ($appendRows as $row)
                        <tr class="fw-bold bg-light-primary">
                            @foreach ($row as $i => $cell)
                                <td class="{{ $alignCls($i) }} {{ $isSerial($i) ? 'w-40px text-center' : '' }}">
                                    {{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                @if (!empty($report['summary']))
                    <tfoot>
                        @foreach ($report['summary'] as $s)
                            <tr class="fw-bold bg-light-primary">
                                <td colspan="{{ max(1, $colCount - 1) }}" class="text-end">{{ $s['label'] ?? '' }}
                                </td>
                                <td class="text-end">{{ $s['value'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Footer: record count + AJAX pagination --}}
        <div class="d-flex flex-stack flex-wrap gap-3 pt-5">
            <div class="text-muted fs-7">
                Showing <span class="fw-semibold text-gray-800">{{ number_format($from) }}</span>–<span
                    class="fw-semibold text-gray-800">{{ number_format($to) }}</span>
                of <span class="fw-semibold text-gray-800">{{ number_format($total) }}</span>
            </div>

            @if ($lastPage > 1)
                @php
                    $window = 2;
                    $start = max(1, $page - $window);
                    $end = min($lastPage, $page + $window);
                @endphp
                <ul class="pagination rpt-pagination mb-0">
                    <li class="page-item previous {{ $page <= 1 ? 'disabled' : '' }}">
                        <a href="#" class="page-link" data-rpt-page="{{ max(1, $page - 1) }}">
                            <i class="previous"></i>
                        </a>
                    </li>

                    @if ($start > 1)
                        <li class="page-item"><a href="#" class="page-link" data-rpt-page="1">1</a></li>
                        @if ($start > 2)
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        @endif
                    @endif

                    @for ($n = $start; $n <= $end; $n++)
                        <li class="page-item {{ $n === $page ? 'active' : '' }}">
                            <a href="#" class="page-link"
                                data-rpt-page="{{ $n }}">{{ $n }}</a>
                        </li>
                    @endfor

                    @if ($end < $lastPage)
                        @if ($end < $lastPage - 1)
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        @endif
                        <li class="page-item"><a href="#" class="page-link"
                                data-rpt-page="{{ $lastPage }}">{{ $lastPage }}</a></li>
                    @endif

                    <li class="page-item next {{ $page >= $lastPage ? 'disabled' : '' }}">
                        <a href="#" class="page-link" data-rpt-page="{{ min($lastPage, $page + 1) }}">
                            <i class="next"></i>
                        </a>
                    </li>
                </ul>
            @endif
        </div>
    @endif
</div>
