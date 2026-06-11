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
    @php $isOutstanding = $outstanding ?? false; @endphp

    <div class="head">
        <h1>Bangladesh Investment Development Authority (BIDA)</h1>
        <div class="sub">
            {{ $isOutstanding ? 'Outstanding CPF Advances' : 'CPF Advances — Summary' }}
            · Generated {{ $generatedAt->format('d-M-Y h:i A') }}
        </div>
    </div>

    <table>
        <thead>
            @if ($isOutstanding)
                <tr>
                    <th class="center">#</th><th>Advance No</th><th>CPF A/C</th><th>Employee</th>
                    <th class="num">Approved</th><th class="num">Outstanding</th><th class="num">Per Inst.</th><th class="num">Progress %</th>
                </tr>
            @else
                <tr>
                    <th class="center">#</th><th>Advance No</th><th>CPF A/C</th><th>Employee</th><th>App. Date</th>
                    <th class="num">Amount</th><th class="num">Rate %</th><th class="center">Inst.</th><th class="num">Outstanding</th><th>Status</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @forelse ($rows as $idx => $a)
                @if ($isOutstanding)
                    <tr>
                        <td class="center">{{ $idx + 1 }}</td>
                        <td>{{ $a->advance_no }}</td>
                        <td>{{ $a->emp_acc }}</td>
                        <td>{{ $a->emp_name }}</td>
                        <td class="num">{{ number_format((int) $a->approved_amount) }}</td>
                        <td class="num">{{ number_format((int) $a->outstanding_amount) }}</td>
                        <td class="num">{{ number_format((int) $a->installment_amount) }}</td>
                        <td class="num">{{ $a->progressPercent() }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="center">{{ $idx + 1 }}</td>
                        <td>{{ $a->advance_no }}</td>
                        <td>{{ $a->emp_acc }}</td>
                        <td>{{ $a->emp_name }}</td>
                        <td>{{ $a->application_date?->format('d-M-Y') }}</td>
                        <td class="num">{{ number_format((int) ($a->approved_amount ?? $a->requested_amount)) }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($a->interest_rate, 2), '0'), '.') }}</td>
                        <td class="center">{{ $a->installment_count }}</td>
                        <td class="num">{{ number_format((int) $a->outstanding_amount) }}</td>
                        <td>{{ $a->status->label() }}</td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="{{ $isOutstanding ? 8 : 10 }}" class="center">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
