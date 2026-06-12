<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #1f2937; }
        .head { text-align: center; margin-bottom: 6px; }
        .head h1 { font-size: 15px; margin: 0; }
        .head h2 { font-size: 11px; margin: 2px 0 0; color: #374151; }
        .head .meta { font-size: 9.5px; color: #4b5563; margin-top: 3px; }
        .head .gen { font-size: 8.5px; color: #6b7280; font-style: italic; margin-top: 1px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 0.5px solid #d1d5db; padding: 4px 6px; }
        th { background: #f3f4f6; text-transform: uppercase; font-size: 8.5px; }
        td.num, th.num { text-align: right; }
        td.center, th.center { text-align: center; }
        .desig { color: #6b7280; font-size: 8.5px; }
        tfoot td { font-weight: bold; background: #f9fafb; }
    </style>
</head>
<body>
    <div class="head">
        <h1>Bangladesh Investment Development Authority (BIDA)</h1>
        <h2>Bank Interest Distribution — {{ $batch->reference_no }}</h2>
        <div class="meta">
            Cut-off {{ $batch->distribution_date->format('d-M-Y') }}
            · FY {{ $batch->fiscal_year }}
            · Interest Received: {{ number_format((int) $batch->total_interest_amount) }} BDT
            · Distributed: {{ number_format($batch->totalDistributed()) }} BDT
            · Status: {{ $batch->status->label() }}
        </div>
        <div class="gen">Generated {{ $generatedAt->format('d-M-Y h:i A') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="center">#</th>
                <th>CPF A/C No</th>
                <th>Member</th>
                <th class="num">Balance @ Cut-off</th>
                <th class="num">Ratio (%)</th>
                <th class="num">Calculated</th>
                <th class="num">Allocated</th>
            </tr>
        </thead>
        <tbody>
            @php $sumBalance = 0; $sumAllocated = 0; @endphp
            @forelse ($rows as $idx => $d)
                @php
                    $sumBalance   += (int) $d->eligible_balance;
                    $sumAllocated += (int) $d->interest_amount;
                @endphp
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td>{{ $d->emp_acc }}</td>
                    <td>
                        {{ $d->emp_name }}
                        @if ($d->emp_designation)
                            <span class="desig">— {{ $d->emp_designation }}</span>
                        @endif
                    </td>
                    <td class="num">{{ number_format((int) $d->eligible_balance) }}</td>
                    <td class="num">{{ number_format($d->ratio * 100, 4) }}</td>
                    <td class="num">{{ number_format($d->calculated_interest, 2) }}</td>
                    <td class="num">{{ number_format((int) $d->interest_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">No distribution rows found.</td></tr>
            @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="3" class="num">Total</td>
                    <td class="num">{{ number_format($sumBalance) }}</td>
                    <td class="num">100.0000</td>
                    <td></td>
                    <td class="num">{{ number_format($sumAllocated) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
