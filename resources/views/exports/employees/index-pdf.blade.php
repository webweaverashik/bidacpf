<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Employee List — BIDA CPF</title>
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

        .filterbar {
            font-size: 9px;
            color: #4b5563;
            margin: 10px 4px 8px;
        }

        .filterbar .chip {
            display: inline-block;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 1px 7px;
            margin-right: 4px;
        }

        table.emp {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table.emp th,
        table.emp td {
            border: 1px solid #d1d5db;
            padding: 4px 5px;
        }

        table.emp th {
            background: #f3f4f6;
            font-size: 8.5px;
            text-transform: uppercase;
        }

        table.emp td {
            font-size: 9px;
        }

        .num-col {
            text-align: right;
        }

        .muted {
            color: #9ca3af;
        }

        .badge {
            font-size: 8px;
            padding: 1px 5px;
            border-radius: 6px;
        }

        .b-active {
            color: #047857;
        }

        .b-inactive {
            color: #b91c1c;
        }

        .b-settled {
            color: #374151;
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
        $hasFilter =
            !empty($filters['search']) ||
            (isset($filters['grade']) && $filters['grade'] !== '' && $filters['grade'] !== null) ||
            !empty($filters['active_status']) ||
            !empty($filters['service_status']);
    @endphp

    <h1>Bangladesh Investment Development Authority (BIDA)</h1>
    <h2>Contributory Provident Fund &mdash; Employee List</h2>

    <div class="filterbar">
        <b>Generated:</b> {{ $generatedAt->format('d-M-Y h:i A') }}
        &nbsp;&middot;&nbsp; <b>Records:</b> {{ $employees->count() }}
        @if ($hasFilter)
            <br><b>Filtered:</b>
            @if (!empty($filters['search']))
                <span class="chip">Search: &ldquo;{{ $filters['search'] }}&rdquo;</span>
            @endif
            @if (isset($filters['grade']) && $filters['grade'] !== '' && $filters['grade'] !== null)
                <span class="chip">Grade: {{ \Illuminate\Support\Str::after($filters['grade'], 'grade_') }}</span>
            @endif
            @if (!empty($filters['active_status']))
                <span class="chip">Activation: {{ ucfirst($filters['active_status']) }}</span>
            @endif
            @if (!empty($filters['service_status']))
                <span class="chip">Service: {{ ucfirst($filters['service_status']) }}</span>
            @endif
        @endif
    </div>

    <table class="emp">
        <thead>
            <tr>
                <th style="width:22px;">#</th>
                <th style="width:80px;">CPF A/C No.</th>
                <th>Name</th>
                <th>Designation</th>
                <th style="width:70px;">Mobile</th>
                <th style="width:62px;">Joining</th>
                <th>Pay Scale</th>
                <th style="width:34px;">Grade</th>
                <th style="width:72px;">Basic Salary</th>
                <th style="width:78px;">Balance</th>
                <th style="width:60px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $i => $e)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $e->cpf_account_no }}</td>
                    <td>{{ $e->name }}</td>
                    <td>{{ $e->designation }}</td>
                    <td>{{ $e->mobile_number ?: '-' }}</td>
                    <td>{{ optional($e->joining_date)->format('d-M-Y') ?? '-' }}</td>
                    <td>{{ $e->ps_name ?? '-' }}</td>
                    <td>{{ $e->ps_grade ?? '-' }}</td>
                    <td class="num-col">{{ $e->ps_basic !== null ? number_format((int) $e->ps_basic) : '-' }}</td>
                    <td class="num-col">{{ number_format((int) $e->current_balance) }}</td>
                    <td>
                        @if ((bool) $e->is_settled)
                            <span class="badge b-settled">Settled</span>
                        @elseif ($e->is_active)
                            <span class="badge b-active">Active</span>
                        @else
                            <span class="badge b-inactive">Inactive</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center;" class="muted">No employees match the current filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        This list is system-generated by the BIDA CPF Management System.
    </div>
</body>

</html>
