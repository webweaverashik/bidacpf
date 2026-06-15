{{--
    CPF Account Slip — one official slip per page (English).

    $fiscalYear, $asOfLabel, $slips[], $generatedAt
    Each slip: account_no, name, designation, open_{own,govt,int,total},
    year_{own,govt,int}, total_deposit, adv_{taken,recovery,remaining},
    net_deposit, in_words
--}}
@php $n = fn($v) => number_format((int) $v); @endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        @page { margin: 28px 26px; }
        body { color: #1f2937; font-size: 9px; }

        .slip { page-break-after: always; }
        .slip:last-child { page-break-after: auto; }

        .head { text-align: center; }
        .head .org { font-size: 14px; font-weight: bold; }
        .head .unit { font-size: 10px; }
        .head .title { font-size: 12px; font-weight: bold; text-decoration: underline; margin-top: 6px; }

        .meta { width: 100%; font-size: 9px; margin: 8px 0 4px; }
        .meta td { padding: 1px 2px; }
        .meta .right { text-align: right; }

        table.slip-grid { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.slip-grid th, table.slip-grid td { border: 0.5px solid #555; padding: 4px 5px; text-align: center; }
        table.slip-grid th { background: #eef0f4; font-size: 8px; }
        table.slip-grid td.num { text-align: right; }
        table.slip-grid td.name { text-align: left; }
        table.slip-grid td.acc { text-align: left; white-space: nowrap; }

        .words { margin-top: 10px; font-size: 10px; }
        .words .label { font-weight: bold; }

        .notice { margin-top: 8px; font-size: 8px; color: #4b5563; font-style: italic; }

        .sign { width: 100%; margin-top: 40px; border-collapse: collapse; }
        .sign td { width: 50%; text-align: center; font-size: 9px; }
        .sign .line { border-top: 0.5px solid #1f2937; display: inline-block; min-width: 150px; padding-top: 3px; }
    </style>
</head>

<body>
    @forelse ($slips as $slip)
        <div class="slip">
            <div class="head">
                <div class="org">Bangladesh Investment Development Authority (BIDA)</div>
                <div class="unit">Contributory Provident Fund (CPF) &middot; Head Office</div>
                <div class="title">CPF Account Slip</div>
            </div>

            <table class="meta">
                <tr>
                    <td>Account Name: CPF &nbsp;|&nbsp; Account Year: {{ $fiscalYear }}@if ($asOfLabel && $asOfLabel !== 'Full Year') &nbsp;|&nbsp; As of: {{ $asOfLabel }} @endif</td>
                    <td class="right">Generated: {{ $generatedAt->format('d-M-Y') }}</td>
                </tr>
            </table>

            <table class="slip-grid">
                <thead>
                    <tr>
                        <th rowspan="2">Account No.</th>
                        <th rowspan="2">Contributor Name &amp; Designation</th>
                        <th colspan="3">Opening Balance</th>
                        <th rowspan="2">Total Opening Balance</th>
                        <th colspan="2">Deposited This Year ({{ $fiscalYear }})</th>
                        <th rowspan="2">Bank Interest This Year</th>
                        <th rowspan="2">Total Deposit</th>
                        <th rowspan="2">Advance Taken</th>
                        <th rowspan="2">Advance Recovery</th>
                        <th rowspan="2">Remaining Advance</th>
                        <th rowspan="2">Net Deposit</th>
                    </tr>
                    <tr>
                        <th>Own</th>
                        <th>Govt.</th>
                        <th>Interest</th>
                        <th>Own</th>
                        <th>Govt.</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="acc">{{ $slip['account_no'] }}</td>
                        <td class="name">{{ $slip['name'] }}<br><span style="color:#6b7280;">{{ $slip['designation'] }}</span></td>
                        <td class="num">{{ $n($slip['open_own']) }}</td>
                        <td class="num">{{ $n($slip['open_govt']) }}</td>
                        <td class="num">{{ $n($slip['open_int']) }}</td>
                        <td class="num">{{ $n($slip['open_total']) }}</td>
                        <td class="num">{{ $n($slip['year_own']) }}</td>
                        <td class="num">{{ $n($slip['year_govt']) }}</td>
                        <td class="num">{{ $n($slip['year_int']) }}</td>
                        <td class="num">{{ $n($slip['total_deposit']) }}</td>
                        <td class="num">{{ $n($slip['adv_taken']) }}</td>
                        <td class="num">{{ $n($slip['adv_recovery']) }}</td>
                        <td class="num">{{ $n($slip['adv_remaining']) }}</td>
                        <td class="num"><strong>{{ $n($slip['net_deposit']) }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="words">
                <span class="label">In words:</span> {{ $slip['in_words'] }}
            </div>

            <div class="notice">
                If any error is found in this statement, please notify within 07 (seven) days. Otherwise the
                statement will be treated as correct.
            </div>

            <table class="sign">
                <tr>
                    <td><span class="line">Trustee</span></td>
                    <td><span class="line">Assistant Director (Audit)</span></td>
                </tr>
            </table>
        </div>
    @empty
        <div class="head"><div class="title">No members match the selected options.</div></div>
    @endforelse
</body>

</html>
