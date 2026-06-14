<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    @include('exports.certificates._styles')
</head>

<body>
    @include('exports.certificates._letterhead')

    @php
        $approved   = $advance->recoveries->where('status', \App\Enums\RecoveryStatus::APPROVED);
        $totalPaid  = (int) $approved->sum('amount');
        $principal  = (int) $approved->sum('principal_applied');
        $interest   = (int) $approved->sum('interest_applied');
    @endphp

    <table class="doc-meta">
        <tr>
            <td>Ref: CLR/{{ $advance->advance_no }}</td>
            <td class="right">Date: {{ $generatedAt->format('d-M-Y') }}</td>
        </tr>
    </table>

    <div class="doc-title">CPF Advance Clearance Certificate</div>

    <p class="lead">
        This is to certify that the CPF advance bearing No. <strong>{{ $advance->advance_no }}</strong>,
        sanctioned to <strong>{{ $advance->employee->name }}</strong>
        (CPF A/C No. <strong>{{ $advance->employee->cpf_account_no }}</strong>), has been
        <strong>fully recovered</strong>. No dues remain outstanding against this advance.
    </p>

    <table class="kv">
        <tr><td class="label">Advance No.</td><td>{{ $advance->advance_no }}</td></tr>
        <tr><td class="label">Sanctioned Principal</td><td>Tk {{ number_format((int) $advance->effectiveAmount()) }}</td></tr>
        <tr><td class="label">Total Recovered</td><td>Tk {{ number_format($totalPaid) }}</td></tr>
        <tr><td class="label">Outstanding Balance</td><td class="text-success"><strong>Tk 0 — Cleared</strong></td></tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th class="ctr" style="width:6%;">#</th>
                <th style="width:16%;">Date</th>
                <th>Recovery No.</th>
                <th class="num" style="width:16%;">Principal (Tk)</th>
                <th class="num" style="width:16%;">Interest (Tk)</th>
                <th class="num" style="width:16%;">Amount (Tk)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($approved->sortBy('recovery_date')->values() as $r)
                <tr>
                    <td class="ctr">{{ $loop->iteration }}</td>
                    <td>{{ $r->recovery_date->format('d-M-Y') }}</td>
                    <td>{{ $r->recovery_no }}</td>
                    <td class="num">{{ number_format((int) $r->principal_applied) }}</td>
                    <td class="num">{{ number_format((int) $r->interest_applied) }}</td>
                    <td class="num">{{ number_format((int) $r->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; color:#6b7280;">No recovery records found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="num">Total</td>
                <td class="num">{{ number_format($principal) }}</td>
                <td class="num">{{ number_format($interest) }}</td>
                <td class="num">{{ number_format($totalPaid) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="note">
        Issued on the basis of approved recovery records held by the CPF authority. This certificate
        confirms the advance is settled in full.
    </p>

    <table class="sign-row">
        <tr>
            <td><span class="muted">Issued at Dhaka</span></td>
            <td class="right"><span class="sign-line">CPF Authority, BIDA</span></td>
        </tr>
    </table>
</body>

</html>
