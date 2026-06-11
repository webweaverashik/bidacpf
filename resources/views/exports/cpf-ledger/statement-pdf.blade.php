<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">@include('exports.cpf-ledger._pdf-styles')
</head>

<body>
    <div class="org">Bangladesh Investment Development Authority (BIDA)</div>
    <div class="subtitle">Contributory Provident Fund — Member Statement</div>
    <div class="generated">Generated on {{ $generatedAt->format('d-M-Y h:i A') }}</div>

    <table class="meta" style="margin-bottom: 10px;">
        <tr>
            <td class="label" style="width:18%;">Name:</td>
            <td class="value" style="width:32%;">{{ $employee->name }}</td>
            <td class="label" style="width:18%;">CPF A/C No.:</td>
            <td class="value" style="width:32%;">{{ $employee->cpf_account_no }}</td>
        </tr>
        <tr>
            <td class="label">Designation:</td>
            <td class="value">{{ $employee->designation }}</td>
            <td class="label">Pay Scale:</td>
            <td class="value">{{ $employee->payScaleStep?->payScale?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Grade / Step:</td>
            <td class="value">
                {{ $employee->payScaleStep ? 'Grade ' . $employee->grade . ' / Step ' . $employee->current_step : '-' }}
            </td>
            <td class="label">Basic Salary:</td>
            <td class="value">
                {{ $employee->current_basic_salary ? 'BDT ' . number_format($employee->current_basic_salary) : '-' }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Type</th>
                <th>Reference</th>
                <th>Remarks</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $e)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $e->transaction_date->format('d-M-Y') }}</td>
                    <td>{{ $e->transaction_type->label() }}</td>
                    <td>{{ $e->reference_no }}</td>
                    <td>{{ $e->remarks }}</td>
                    <td class="num text-danger">{{ $e->debit > 0 ? number_format($e->debit) : '' }}</td>
                    <td class="num text-success">{{ $e->credit > 0 ? number_format($e->credit) : '' }}</td>
                    <td class="num">{{ number_format($e->balance) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:14px;">No ledger entries.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="balance-box">Current Balance: BDT {{ number_format($balance) }}</div>
</body>

</html>
