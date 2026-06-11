<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">@include('exports.cpf-ledger._pdf-styles')
</head>

<body>
    <div class="org">Bangladesh Investment Development Authority (BIDA)</div>
    <div class="subtitle">Contributory Provident Fund — Transaction Log</div>
    <div class="generated">Generated on {{ $generatedAt->format('d-M-Y h:i A') }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Employee</th>
                <th>Type</th>
                <th>Reference</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th class="num">Balance</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $t)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $t->transaction_date->format('d-M-Y') }}</td>
                    <td>{{ $t->emp_name }}<br><span style="color:#6b7280;">{{ $t->emp_acc }}</span></td>
                    <td>{{ $t->transaction_type->label() }}</td>
                    <td>{{ $t->reference_no }}</td>
                    <td class="num text-danger">{{ $t->debit > 0 ? number_format($t->debit) : '' }}</td>
                    <td class="num text-success">{{ $t->credit > 0 ? number_format($t->credit) : '' }}</td>
                    <td class="num">{{ number_format($t->balance) }}</td>
                    <td>{{ $t->remarks }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
