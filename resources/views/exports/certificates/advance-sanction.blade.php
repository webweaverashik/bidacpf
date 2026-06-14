<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    @include('exports.certificates._styles')
</head>

<body>
    @include('exports.certificates._letterhead')

    @php
        $principal = (int) $advance->effectiveAmount();
        $interest = (int) $advance->projectedInterest();
        $payable = (int) $advance->totalPayable();
        $count = (int) $advance->installment_count;
        $perInstall = (int) ($advance->installment_amount ?: $advance->projectedInstallment());
        $last = $count > 0 ? max(0, $payable - $perInstall * ($count - 1)) : 0;
    @endphp

    <table class="doc-meta">
        <tr>
            <td>Ref: {{ $advance->advance_no }}</td>
            <td class="right">Date:
                {{ optional($advance->approval_date)->format('d-M-Y') ?? $generatedAt->format('d-M-Y') }}</td>
        </tr>
    </table>

    <div class="doc-title">CPF Advance Sanction Letter</div>

    <p class="lead">
        With reference to the application dated
        <strong>{{ optional($advance->application_date)->format('d F Y') ?? '—' }}</strong>,
        the competent authority has been pleased to <strong>sanction</strong> a Contributory
        Provident Fund advance to <strong>{{ $advance->employee->name }}</strong>
        (CPF A/C No. <strong>{{ $advance->employee->cpf_account_no }}</strong>),
        {{ $advance->employee->designation ?: 'an employee of BIDA' }}, on the following terms.
    </p>

    <table class="kv">
        <tr>
            <td class="label">Advance No.</td>
            <td>{{ $advance->advance_no }}</td>
        </tr>
        <tr>
            <td class="label">Sanctioned Principal</td>
            <td>Tk {{ number_format($principal) }}</td>
        </tr>
        <tr>
            <td class="label">Interest Rate</td>
            <td>{{ rtrim(rtrim(number_format((float) $advance->interest_rate, 2), '0'), '.') }}% per annum</td>
        </tr>
        <tr>
            <td class="label">Projected Interest</td>
            <td>Tk {{ number_format($interest) }}</td>
        </tr>
        <tr>
            <td class="label">Total Repayable</td>
            <td><strong>Tk {{ number_format($payable) }}</strong></td>
        </tr>
        <tr>
            <td class="label">No. of Installments</td>
            <td>{{ $count }}</td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th>Repayment Schedule</th>
                <th class="num" style="width:30%;">Amount (Tk)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ max(0, $count - 1) }} × monthly installment</td>
                <td class="num">{{ number_format($perInstall) }}</td>
            </tr>
            <tr>
                <td>Final installment (adjusting)</td>
                <td class="num">{{ number_format($last) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td class="num">Total Repayable (Tk)</td>
                <td class="num">{{ number_format($payable) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="note">
        The advance shall be recovered in {{ $count }} monthly installment(s) from the member's salary,
        commencing the month following disbursement. Interest is computed at the prevailing CPF advance rate.
    </p>

    <table class="sign-row">
        <tr>
            <td><span class="muted">For office use</span></td>
            <td class="right">
                <span class="sign-line">
                    Sanctioning Authority<br>
                    <span class="muted" style="font-size:9px;">CPF Authority, BIDA</span>
                </span>
            </td>
        </tr>
    </table>
</body>

</html>
