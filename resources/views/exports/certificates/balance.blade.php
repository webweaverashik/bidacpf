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
            <td>Ref: CPF/BAL/{{ $employee->cpf_account_no }}</td>
            <td class="right">Date: {{ $generatedAt->format('d-M-Y') }}</td>
        </tr>
    </table>

    <div class="doc-title">CPF Balance Certificate</div>

    <p class="lead">
        This is to certify that <strong>{{ $employee->name }}</strong>,
        {{ $employee->designation ?: 'an employee of BIDA' }}, bearing CPF Account No.
        <strong>{{ $employee->cpf_account_no }}</strong>, is a member of the Contributory Provident
        Fund of the Bangladesh Investment Development Authority.
    </p>

    <p>
        As per the official records of the Fund, the member's CPF account balance as of
        <strong>{{ $asOf->format('d F Y') }}</strong> stands as follows:
    </p>

    <div class="amount-box">
        CPF Balance as of {{ $asOf->format('d-M-Y') }} : Tk {{ number_format((int) $balance) }}
    </div>

    @if ((int) $outstanding > 0)
        <table class="kv">
            <tr>
                <td class="label">Accumulated CPF Balance</td>
                <td>Tk {{ number_format((int) $balance) }}</td>
            </tr>
            <tr>
                <td class="label">Outstanding Advance (Dr.)</td>
                <td class="text-danger">Tk {{ number_format((int) $outstanding) }}</td>
            </tr>
            <tr>
                <td class="label">Net Position</td>
                <td><strong>Tk {{ number_format((int) $balance - (int) $outstanding) }}</strong></td>
            </tr>
        </table>
        <p class="note">
            The member currently carries an outstanding CPF advance of
            Tk {{ number_format((int) $outstanding) }}, recoverable in subsequent installments.
        </p>
    @else
        <p class="note">The member has no outstanding CPF advance as of the certified date.</p>
    @endif

    <p>This certificate is issued upon the member's request for official purposes.</p>

    <table class="sign-row">
        <tr>
            <td><span class="muted">Issued at Dhaka</span></td>
            <td class="right"><span class="sign-line">CPF Authority, BIDA</span></td>
        </tr>
    </table>
</body>

</html>
