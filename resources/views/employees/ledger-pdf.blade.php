<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>CPF Ledger - {{ $employee->name }}</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
        }

        body {
            font-size: 10px;
            color: #1f2937;
            margin: 0;
        }

        h1 {
            font-size: 15px;
            margin: 0;
            text-align: center;
        }

        h2 {
            font-size: 11px;
            margin: 2px 0 0;
            text-align: center;
            font-weight: normal;
            color: #4b5563;
        }

        .meta {
            width: 100%;
            margin: 14px 0 8px;
            border-collapse: collapse;
        }

        .meta td {
            padding: 2px 6px;
            font-size: 10px;
        }

        .meta .label {
            color: #6b7280;
            width: 110px;
        }

        .meta .value {
            font-weight: bold;
        }

        .filterbar {
            font-size: 9px;
            color: #4b5563;
            margin: 0 6px 8px;
        }

        .filterbar .chip {
            display: inline-block;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 1px 7px;
            margin-right: 4px;
        }

        .summary {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .summary td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: center;
        }

        .summary .cap {
            color: #6b7280;
            font-size: 9px;
        }

        .summary .num {
            font-weight: bold;
            font-size: 12px;
        }

        table.ledger {
            width: 100%;
            border-collapse: collapse;
        }

        table.ledger th,
        table.ledger td {
            border: 1px solid #d1d5db;
            padding: 4px 5px;
        }

        table.ledger th {
            background: #f3f4f6;
            font-size: 9px;
            text-transform: uppercase;
        }

        table.ledger td {
            font-size: 9.5px;
        }

        .num-col {
            text-align: right;
        }

        .debit {
            color: #b91c1c;
        }

        .credit {
            color: #047857;
        }

        .bal {
            font-weight: bold;
        }

        .muted {
            color: #9ca3af;
        }

        .footer {
            margin-top: 14px;
            font-size: 8.5px;
            color: #6b7280;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
        $hasFilter =
            !empty($filters['fiscal_year']) ||
            !empty($filters['type']) ||
            !empty($filters['month']) ||
            !empty($filters['search']);
        $exportCredits = $ledgers->sum('credit');
        $exportDebits = $ledgers->sum('debit');
        $basicSalary = $employee->current_basic_salary;
    @endphp

    <h1>Bangladesh Investment Development Authority (BIDA)</h1>
    <h2>Contributory Provident Fund &mdash; Employee Ledger</h2>

    <table class="meta">
        <tr>
            <td class="label">Name:</td>
            <td class="value">{{ $employee->name }}</td>
            <td class="label">CPF Account No.:</td>
            <td class="value">{{ $employee->cpf_account_no }}</td>
        </tr>
        <tr>
            <td class="label">Designation:</td>
            <td class="value">{{ $employee->designation }}</td>
            <td class="label">Grade / Step:</td>
            <td class="value">
                @if ($employee->payScaleStep)
                    Grade {{ $employee->grade }} / Step {{ $employee->current_step }}
                @else
                    -
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Basic Salary:</td>
            <td class="value">
                @if ($basicSalary)
                    BDT {{ number_format($basicSalary) }}
                @else
                    -
                @endif
            </td>
            <td class="label">Pay Scale:</td>
            <td class="value">{{ $employee->payScaleStep?->payScale?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Generated:</td>
            <td class="value">{{ now()->format('d-M-Y h:i A') }}</td>
            <td class="label">Current Balance:</td>
            <td class="value">BDT {{ number_format($currentBalance) }}</td>
        </tr>
    </table>

    @if ($hasFilter)
        <div class="filterbar">
            <b>Filtered export:</b>
            @if (!empty($filters['fiscal_year']))
                <span class="chip">FY: {{ $filters['fiscal_year'] }}</span>
            @endif
            @if (!empty($filters['type']))
                <span class="chip">Type: {{ \Illuminate\Support\Str::headline($filters['type']) }}</span>
            @endif
            @if (!empty($filters['month']))
                <span class="chip">Month: {{ $months[(int) $filters['month']] ?? $filters['month'] }}</span>
            @endif
            @if (!empty($filters['search']))
                <span class="chip">Search: &ldquo;{{ $filters['search'] }}&rdquo;</span>
            @endif
        </div>
    @endif

    <table class="summary">
        <tr>
            <td>
                <div class="cap">{{ $hasFilter ? 'Credits (filtered)' : 'Total Credits' }}</div>
                <div class="num">BDT {{ number_format($exportCredits) }}</div>
            </td>
            <td>
                <div class="cap">{{ $hasFilter ? 'Debits (filtered)' : 'Total Debits' }}</div>
                <div class="num">BDT {{ number_format($exportDebits) }}</div>
            </td>
            <td>
                <div class="cap">Basic Salary</div>
                <div class="num">{{ $basicSalary ? 'BDT ' . number_format($basicSalary) : '—' }}</div>
            </td>
            <td>
                <div class="cap">Current Balance (overall)</div>
                <div class="num">BDT {{ number_format($currentBalance) }}</div>
            </td>
        </tr>
    </table>

    <table class="ledger">
        <thead>
            <tr>
                <th style="width:24px;">#</th>
                <th style="width:62px;">Date</th>
                <th>Type</th>
                <th>Source</th>
                <th>Reference</th>
                <th style="width:72px;">Basic Salary</th>
                <th style="width:64px;">Debit</th>
                <th style="width:64px;">Credit</th>
                <th style="width:72px;">Balance</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ledgers as $i => $ledger)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ optional($ledger->transaction_date)->format('d-M-Y') }}</td>
                    <td>{{ \Illuminate\Support\Str::headline($ledger->transaction_type?->value ?? '') }}</td>
                    <td>{{ $ledger->source_label ?: '-' }}</td>
                    <td>{{ $ledger->reference_no ?: '-' }}</td>
                    <td class="num-col">{{ $basicSalary ? number_format($basicSalary) : '-' }}</td>
                    <td class="num-col debit">{{ $ledger->debit > 0 ? number_format($ledger->debit) : '' }}</td>
                    <td class="num-col credit">{{ $ledger->credit > 0 ? number_format($ledger->credit) : '' }}</td>
                    <td class="num-col bal">{{ number_format($ledger->balance) }}</td>
                    <td>{{ $ledger->remarks ?: '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align:center;" class="muted">No ledger entries match the current
                        filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        This statement is system-generated by the BIDA CPF Management System. Errors must be reported within 07 (seven)
        days.
    </div>
</body>

</html>
