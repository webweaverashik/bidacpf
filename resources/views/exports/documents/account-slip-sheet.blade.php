{{--
    One CPF Account Slip rendered as an HTML table for a single Excel worksheet
    (maatwebsite/excel FromView). One worksheet per member.

    $slip, $fiscalYear, $asOfLabel, $generatedAt
--}}
@php $n = fn($v) => number_format((int) $v); @endphp
<table>
    <thead>
        <tr><th colspan="14" style="font-weight:bold;text-align:center;">Bangladesh Investment Development Authority (BIDA)</th></tr>
        <tr><th colspan="14" style="text-align:center;">Contributory Provident Fund (CPF) — Head Office</th></tr>
        <tr><th colspan="14" style="font-weight:bold;text-align:center;">CPF Account Slip</th></tr>
        <tr>
            <th colspan="14" style="text-align:center;">
                Account Name: CPF | Account Year: {{ $fiscalYear }}@if ($asOfLabel && $asOfLabel !== 'Full Year') | As of: {{ $asOfLabel }}@endif
            </th>
        </tr>
        <tr><th colspan="14"></th></tr>
        <tr>
            <th rowspan="2">Account No.</th>
            <th rowspan="2">Contributor Name &amp; Designation</th>
            <th colspan="3">Opening Balance</th>
            <th rowspan="2">Total Opening Balance</th>
            <th colspan="2">Deposited This Year ({{ $fiscalYear }})</th>
            <th rowspan="2">Bank Interest This Year</th>
            <th rowspan="2">Total Deposit</th>
            <th rowspan="2">Advance Taken</th>
            <th rowspan="2">Advance Recovery</th>
            <th rowspan="2">Remaining Advance</th>
            <th rowspan="2">Net Deposit</th>
        </tr>
        <tr>
            <th>Own</th>
            <th>Govt.</th>
            <th>Interest</th>
            <th>Own</th>
            <th>Govt.</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $slip['account_no'] }}</td>
            <td>{{ $slip['name'] }} ({{ $slip['designation'] }})</td>
            <td>{{ $n($slip['open_own']) }}</td>
            <td>{{ $n($slip['open_govt']) }}</td>
            <td>{{ $n($slip['open_int']) }}</td>
            <td>{{ $n($slip['open_total']) }}</td>
            <td>{{ $n($slip['year_own']) }}</td>
            <td>{{ $n($slip['year_govt']) }}</td>
            <td>{{ $n($slip['year_int']) }}</td>
            <td>{{ $n($slip['total_deposit']) }}</td>
            <td>{{ $n($slip['adv_taken']) }}</td>
            <td>{{ $n($slip['adv_recovery']) }}</td>
            <td>{{ $n($slip['adv_remaining']) }}</td>
            <td>{{ $n($slip['net_deposit']) }}</td>
        </tr>
        <tr><td colspan="14"></td></tr>
        <tr><td colspan="14">In words: {{ $slip['in_words'] }}</td></tr>
        <tr><td colspan="14">If any error is found in this statement, please notify within 07 (seven) days. Otherwise the statement will be treated as correct.</td></tr>
        <tr><td colspan="14"></td></tr>
        <tr>
            <td colspan="7" style="text-align:center;">Trustee</td>
            <td colspan="7" style="text-align:center;">Assistant Director (Audit)</td>
        </tr>
    </tbody>
</table>
