<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    @include('exports.certificates._styles')
</head>

<body>
    @include('exports.certificates._letterhead')

    @php
        $ratioPct = number_format((float) $distribution->ratio * 100, 4);
    @endphp

    <table class="doc-meta">
        <tr>
            <td>Ref: {{ $batch->reference_no }}/{{ $employee->cpf_account_no }}</td>
            <td class="right">Date: {{ $generatedAt->format('d-M-Y') }}</td>
        </tr>
    </table>

    <div class="doc-title">Bank Interest Distribution Certificate</div>

    <p class="lead">
        This is to certify that <strong>{{ $employee->name }}</strong>
        (CPF A/C No. <strong>{{ $employee->cpf_account_no }}</strong>),
        {{ $employee->designation ?: 'an employee of BIDA' }}, has been credited with a share of the
        bank interest distributed by the Contributory Provident Fund for the distribution dated
        <strong>{{ $batch->distribution_date->format('d F Y') }}</strong>
        (FY {{ $batch->fiscal_year }}).
    </p>

    <table class="kv">
        <tr><td class="label">Distribution Reference</td><td>{{ $batch->reference_no }}</td></tr>
        <tr><td class="label">Distribution Date</td><td>{{ $batch->distribution_date->format('d-M-Y') }}</td></tr>
        <tr><td class="label">Fiscal Year</td><td>{{ $batch->fiscal_year }}</td></tr>
        <tr><td class="label">Member's Eligible Balance</td><td>Tk {{ number_format((int) $distribution->eligible_balance) }}</td></tr>
        <tr><td class="label">Share of Fund</td><td>{{ $ratioPct }}%</td></tr>
    </table>

    <div class="amount-box">
        Interest Credited : Tk {{ number_format((int) $distribution->interest_amount) }}
    </div>

    <p class="note">
        The interest amount shown above has been computed proportionate to the member's eligible CPF
        balance against the total distributable interest of Tk
        {{ number_format((int) $batch->total_interest_amount) }} for this distribution, and credited to
        the member's CPF account.
    </p>

    <table class="sign-row">
        <tr>
            <td><span class="muted">Issued at Dhaka</span></td>
            <td class="right"><span class="sign-line">CPF Authority, BIDA</span></td>
        </tr>
    </table>
</body>

</html>
