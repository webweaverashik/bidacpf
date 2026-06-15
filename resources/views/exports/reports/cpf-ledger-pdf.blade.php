{{--
    Official CPF Ledger PDF — compact 18-column landscape grid.

    Consumes the standard ReportService envelope plus an optional `appendRows`
    (the body-footer "Total" row). Self-contained styles; DejaVu Sans for
    currency glyphs. Tight typography so all 18 official columns fit A4 landscape.

    $report      : ['title','subtitle','meta','headings','aligns','rows','appendRows']
    $generatedAt : Carbon
--}}
@php
    $headings = $report['headings'] ?? [];
    $aligns   = $report['aligns'] ?? [];
    $rows     = $report['rows'] ?? [];
    $append   = $report['appendRows'] ?? [];
    $colCount = count($headings);
    $alignCls = fn($i) => match ($aligns[$i] ?? 'left') {
        'num', 'right' => 'num',
        'center'       => 'ctr',
        default        => '',
    };
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        @page { margin: 22px 18px; }
        body { color: #1f2937; font-size: 7px; }

        .org { text-align: center; font-size: 13px; font-weight: bold; }
        .unit { text-align: center; font-size: 9px; color: #374151; }
        .title { text-align: center; font-size: 11px; font-weight: bold; margin-top: 3px; }
        .subtitle { text-align: center; font-size: 9px; color: #4b5563; }
        .generated { text-align: center; font-size: 7px; color: #9ca3af; margin-bottom: 8px; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 0.5px solid #c9ced6; padding: 2px 3px; word-wrap: break-word; overflow-wrap: break-word; }
        th { background: #eef0f4; text-align: center; font-size: 6.5px; line-height: 1.1; }
        td { font-size: 7px; }
        td.num, th.num { text-align: right; }
        td.ctr, th.ctr { text-align: center; }
        tr.colno td { background: #f7f8fa; text-align: center; color: #9ca3af; font-size: 6px; padding: 1px; }

        tfoot td { background: #eef2ff; font-weight: bold; }

        .empty { text-align: center; padding: 12px; color: #6b7280; font-size: 9px; }

        /* Narrow the serial / wide the name & designation a little. */
        col.c-sl { width: 2.4%; }
        col.c-name { width: 9%; }
        col.c-desig { width: 8%; }
    </style>
</head>

<body>
    <div class="org">Bangladesh Investment Development Authority (BIDA)</div>
    <div class="unit">Contributory Provident Fund (CPF)</div>
    <div class="title">{{ $report['title'] ?? 'CPF Ledger' }}</div>
    @if (!empty($report['subtitle']))
        <div class="subtitle">{{ $report['subtitle'] }}</div>
    @endif
    <div class="generated">Generated on {{ $generatedAt->format('d-M-Y h:i A') }}</div>

    <table>
        <colgroup>
            @foreach ($headings as $i => $h)
                <col class="{{ $i === 0 ? 'c-sl' : ($i === 1 ? 'c-name' : ($i === 2 ? 'c-desig' : '')) }}">
            @endforeach
        </colgroup>
        <thead>
            <tr>
                @foreach ($headings as $i => $h)
                    <th class="{{ $alignCls($i) }}">{{ $h }}</th>
                @endforeach
            </tr>
            <tr class="colno">
                @foreach ($headings as $i => $h)
                    <td>{{ $i + 1 }}</td>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $i => $cell)
                        <td class="{{ $alignCls($i) }}">{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ max(1, $colCount) }}">No records match the selected options.</td>
                </tr>
            @endforelse
        </tbody>
        @if (!empty($append))
            <tfoot>
                @foreach ($append as $row)
                    <tr>
                        @foreach ($row as $i => $cell)
                            <td class="{{ $alignCls($i) }}">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tfoot>
        @endif
    </table>
</body>

</html>
