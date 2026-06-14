{{--
    Generic PDF for every "summary" (tabular) report.

    Consumes the same ReportService envelope used by the on-screen preview and the
    Excel export, so all three stay in lock-step. Self-contained styles (DomPDF
    does not see the app stylesheet); DejaVu Sans for Bangla / currency glyphs.

    $report      : ['title','subtitle','meta','headings','aligns','rows','summary']
    $generatedAt : Carbon
--}}
@php
    $headings = $report['headings'] ?? [];
    $aligns = $report['aligns'] ?? [];
    $rows = $report['rows'] ?? [];
    $colCount = count($headings);
    $alignCls = fn($i) => match ($aligns[$i] ?? 'left') {
        'num', 'right' => 'num',
        'center' => 'ctr',
        default => '',
    };
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
        }

        body {
            color: #1f2937;
            font-size: 9px;
        }

        .org {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
        }

        .title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin-top: 2px;
        }

        .subtitle {
            text-align: center;
            font-size: 10px;
            color: #4b5563;
            margin-bottom: 2px;
        }

        .generated {
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 0.5px solid #d1d5db;
            padding: 4px 6px;
        }

        th {
            background: #f3f4f6;
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
        }

        td.num,
        th.num {
            text-align: right;
        }

        td.ctr,
        th.ctr {
            text-align: center;
        }

        tfoot td {
            background: #eef2ff;
            font-weight: bold;
        }

        .meta {
            margin-bottom: 10px;
        }

        .meta td {
            border: none;
            padding: 2px 4px;
            font-size: 9px;
        }

        .meta .label {
            color: #6b7280;
        }

        .meta .value {
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 14px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="org">Bangladesh Investment Development Authority (BIDA)</div>
    <div class="title">{{ $report['title'] ?? 'Report' }}</div>
    @if (!empty($report['subtitle']))
        <div class="subtitle">{{ $report['subtitle'] }}</div>
    @endif
    <div class="generated">Generated on {{ $generatedAt->format('d-M-Y h:i A') }}</div>

    @if (!empty($report['meta']))
        <table class="meta">
            @foreach (array_chunk($report['meta'], 2) as $pair)
                <tr>
                    @foreach ($pair as $m)
                        <td class="label" style="width:16%;">{{ $m['label'] ?? '' }}:</td>
                        <td class="value" style="width:34%;">{{ $m['value'] ?? '' }}</td>
                    @endforeach
                    @if (count($pair) === 1)
                        <td></td>
                        <td></td>
                    @endif
                </tr>
            @endforeach
        </table>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($headings as $i => $h)
                    <th class="{{ $alignCls($i) }}">{{ $h }}</th>
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
        @if (!empty($report['summary']))
            <tfoot>
                @foreach ($report['summary'] as $s)
                    <tr>
                        <td colspan="{{ max(1, $colCount - 1) }}" class="num">{{ $s['label'] ?? '' }}</td>
                        <td class="num">{{ $s['value'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tfoot>
        @endif
    </table>
</body>

</html>
