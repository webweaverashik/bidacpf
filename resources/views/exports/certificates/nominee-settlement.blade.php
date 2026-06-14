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
            <td>Ref: NOM/{{ $settlement->settlement_no }}</td>
            <td class="right">Date:
                {{ optional($settlement->approval_date)->format('d-M-Y') ?? $generatedAt->format('d-M-Y') }}</td>
        </tr>
    </table>

    <div class="doc-title">CPF Nominee Settlement Certificate</div>

    <p class="lead">
        This is to certify that, following the demise of the late
        <strong>{{ $settlement->employee->name }}</strong>
        (CPF A/C No. <strong>{{ $settlement->employee->cpf_account_no }}</strong>),
        {{ $settlement->employee->designation ?: 'an employee of BIDA' }}, the balance standing to the
        member's Contributory Provident Fund account has been settled in favour of the nominee named below,
        effective <strong>{{ $settlement->settlement_date->format('d F Y') }}</strong>.
    </p>

    <table class="kv">
        <tr>
            <td class="label">Settlement No.</td>
            <td>{{ $settlement->settlement_no }}</td>
        </tr>
        <tr>
            <td class="label">Deceased Member</td>
            <td>{{ $settlement->employee->name }} ({{ $settlement->employee->cpf_account_no }})</td>
        </tr>
        <tr>
            <td class="label">Nominee / Payee</td>
            <td><strong>{{ $settlement->payee_name ?: '—' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Relationship</td>
            <td>{{ $settlement->payee_relation ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Settlement Date</td>
            <td>{{ $settlement->settlement_date->format('d-M-Y') }}</td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th>Settlement Computation</th>
                <th class="num" style="width:30%;">Amount (Tk)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Closing CPF Balance</td>
                <td class="num">{{ number_format((int) $settlement->closing_balance) }}</td>
            </tr>
            <tr>
                <td>Less: Outstanding Advance</td>
                <td class="num text-danger">{{ number_format((int) $settlement->outstanding_advance) }}</td>
            </tr>
            <tr>
                <td>Advance Adjustment Applied</td>
                <td class="num">{{ number_format((int) $settlement->advance_adjustment) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td class="num">Net Amount Payable to Nominee (Tk)</td>
                <td class="num">{{ number_format((int) $settlement->total_payable) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="amount-box">
        Amount Payable to Nominee : Tk {{ number_format((int) $settlement->total_payable) }}
    </div>

    @if ($settlement->payee_detail)
        <p class="note"><strong>Nominee / payment details:</strong> {{ $settlement->payee_detail }}</p>
    @endif

    <p class="note">
        Issued upon approval of the settlement by the competent authority, in accordance with the member's
        recorded nomination. This closes the deceased member's CPF account with the Fund.
    </p>

    <table class="sign-row">
        <tr>
            <td><span class="sign-line">Prepared / Verified</span></td>
            <td class="right">
                <span class="sign-line">
                    Approving Authority<br>
                    <span class="muted" style="font-size:9px;">CPF Authority, BIDA</span>
                </span>
            </td>
        </tr>
    </table>
</body>

</html>
