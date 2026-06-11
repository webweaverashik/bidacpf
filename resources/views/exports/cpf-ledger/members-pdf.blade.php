<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">@include('exports.cpf-ledger._pdf-styles')
</head>

<body>
    <div class="org">Bangladesh Investment Development Authority (BIDA)</div>
    <div class="subtitle">Contributory Provident Fund — Member Balance Summary</div>
    <div class="generated">Generated on {{ $generatedAt->format('d-M-Y h:i A') }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>CPF A/C No.</th>
                <th>Name</th>
                <th>Designation</th>
                <th>Pay Scale</th>
                <th>Grade</th>
                <th class="num">Basic Salary</th>
                <th class="num">Current Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $e)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $e->cpf_account_no }}</td>
                    <td>{{ $e->name }}</td>
                    <td>{{ $e->designation }}</td>
                    <td>{{ $e->ps_name ?? '-' }}</td>
                    <td>{{ $e->ps_grade ?? '-' }}</td>
                    <td class="num">{{ number_format((int) $e->ps_basic) }}</td>
                    <td class="num">{{ number_format((int) $e->current_balance) }}</td>
                    <td>{{ $e->is_active ? 'Active' : 'Inactive' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
