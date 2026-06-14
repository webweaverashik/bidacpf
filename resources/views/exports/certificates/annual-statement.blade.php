<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    @include('exports.certificates._styles')
</head>

<body>
    @include('exports.certificates._letterhead')

    <table class="doc-meta">
        <tr>
            <td>Ref: CPF/AS/{{ $employee->cpf_account_no }}/{{ $fiscalYear }}</td>
            <td class="right">Date: {{ $generatedAt->format('d-M-Y') }}</td>
        </tr>
    </table>

    <div class="doc-title">Annual CPF Statement — FY {{ $fiscalYear }}</div>

    <table class="kv">
        <tr>
            <td class="label">Member Name</td>
            <td>{{ $employee->name }}</td>
        </tr>
        <tr>
            <td class="label">CPF Account No.</td>
            <td>{{ $employee->cpf_account_no }}</td>
        </tr>
        <tr>
            <td class="label">Designation</td>
            <td>{{ $employee->designation ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Pay Scale / Grade</td>
            <td>{{ $employee->payScaleStep?->payScale?->name ?? '—' }}{{ $employee->payScaleStep ? ' · Grade ' . $employee->grade : '' }}</td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th class="ctr" style="width:6%;">#</th>
                <th style="width:16%;">Date</th>
                <th>Transaction</th>
                <th class="num" style="width:16%;">Debit (Tk)</th>
                <th class="num" style="width:16%;">Credit (Tk)</th>
                <th class="num" style="width:16%;">Balance (Tk)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="ctr">—</td>
                <td>Opening</td>
                <td>Balance brought forward</td>
                <td class="num"></td>
                <td class="num"></td>
                <td class="num">{{ number_format((int) $opening) }}</td>
            </tr>
            @forelse ($entries as $e)
                <tr>
                    <td class="ctr">{{ $loop->iteration }}</td>
                    <td>{{ $e->transaction_date->format('d-M-Y') }}</td>
                    <td>{{ $e->transaction_type->label() }}</td>
                    <td class="num text-danger">{{ $e->debit > 0 ? number_format((int) $e->debit) : '' }}</td>
                    <td class="num text-success">{{ $e->credit > 0 ? number_format((int) $e->credit) : '' }}</td>
                    <td class="num">{{ number_format((int) $e->balance) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="ctr"></td>
                    <td colspan="5" style="text-align:center; color:#6b7280;">No transactions recorded in this fiscal year.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="num">Closing Balance (Tk)</td>
                <td class="num">{{ number_format((int) $closing) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th>Movement During the Year</th>
                <th class="num" style="width:28%;">Amount (Tk)</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Employee Contribution</td><td class="num">{{ number_format((int) ($totals['employee'] ?? 0)) }}</td></tr>
            <tr><td>Government Contribution</td><td class="num">{{ number_format((int) ($totals['government'] ?? 0)) }}</td></tr>
            <tr><td>Bank Interest Credited</td><td class="num">{{ number_format((int) ($totals['interest'] ?? 0)) }}</td></tr>
            <tr><td>Advance Disbursed</td><td class="num text-danger">{{ number_format((int) abs($totals['advance'] ?? 0)) }}</td></tr>
            <tr><td>Advance Recovered</td><td class="num text-success">{{ number_format((int) ($totals['recovery'] ?? 0)) }}</td></tr>
        </tbody>
    </table>

    <p class="note">
        This statement reflects all CPF transactions recorded for the member during fiscal year
        {{ $fiscalYear }} (01 July to 30 June). Figures are derived from the official CPF ledger.
    </p>

    <table class="sign-row">
        <tr>
            <td><span class="sign-line">Prepared By</span></td>
            <td class="right"><span class="sign-line">CPF Authority, BIDA</span></td>
        </tr>
    </table>
</body>

</html>
