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
    </style>
</head>
<body>
    <div class="head">
        <h1>Bangladesh Investment Development Authority (BIDA)</h1>
        <div class="sub">CPF Advance Recoveries · Generated {{ $generatedAt->format('d-M-Y h:i A') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="center">#</th><th>Recovery No</th><th>Advance No</th><th>CPF A/C</th><th>Employee</th>
                <th>Date</th><th class="num">Amount</th><th>Deposit Ref</th><th>Bank</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $idx => $r)
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td>{{ $r->recovery_no }}</td>
                    <td>{{ $r->adv_no }}</td>
                    <td>{{ $r->emp_acc }}</td>
                    <td>{{ $r->emp_name }}</td>
                    <td>{{ $r->recovery_date?->format('d-M-Y') }}</td>
                    <td class="num">{{ number_format((int) $r->amount) }}</td>
                    <td>{{ $r->deposit_reference ?? '—' }}</td>
                    <td>{{ $r->bank_name ?? '—' }}</td>
                    <td>{{ $r->status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="center">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
