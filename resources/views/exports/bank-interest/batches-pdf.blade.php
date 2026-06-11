<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #1f2937; }
        .head { text-align: center; margin-bottom: 10px; }
        .head h1 { font-size: 15px; margin: 0; }
        .head .sub { font-size: 10px; color: #4b5563; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.5px solid #d1d5db; padding: 5px 6px; }
        th { background: #f3f4f6; text-transform: uppercase; font-size: 8.5px; }
        td.num, th.num { text-align: right; }
        td.center, th.center { text-align: center; }
        tfoot td { font-weight: bold; background: #f9fafb; }
    </style>
</head>
<body>
    <div class="head">
        <h1>Bangladesh Investment Development Authority (BIDA)</h1>
        <div class="sub">
            Bank Interest Distribution — Batches
            · Generated {{ $generatedAt->format('d-M-Y h:i A') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="center">#</th>
                <th>Reference</th>
                <th>Cut-off Date</th>
                <th>Fiscal Year</th>
                <th class="num">Interest Received</th>
                <th class="center">Members</th>
                <th class="num">Distributed</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $sumInterest = 0; $sumDistributed = 0; @endphp
            @forelse ($rows as $idx => $b)
                @php
                    $sumInterest    += (int) $b->total_interest_amount;
                    $sumDistributed += (int) ($b->distributed_total ?? 0);
                @endphp
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td>{{ $b->reference_no }}</td>
                    <td>{{ $b->distribution_date?->format('d-M-Y') }}</td>
                    <td>{{ $b->fiscal_year }}</td>
                    <td class="num">{{ number_format((int) $b->total_interest_amount) }}</td>
                    <td class="center">{{ (int) ($b->distributions_count ?? 0) }}</td>
                    <td class="num">{{ number_format((int) ($b->distributed_total ?? 0)) }}</td>
                    <td>{{ $b->status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center">No records found.</td></tr>
            @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="4" class="num">Total</td>
                    <td class="num">{{ number_format($sumInterest) }}</td>
                    <td></td>
                    <td class="num">{{ number_format($sumDistributed) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
