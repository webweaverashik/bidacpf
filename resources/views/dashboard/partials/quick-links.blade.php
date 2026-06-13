{{-- Role-aware shortcut buttons. Each is gated by a create/view permission, so a
     read-only Auditor naturally sees none (the auditor view omits this partial). --}}
<div class="card card-flush h-100">
    <div class="card-header pt-5">
        <div class="card-title fs-4 fw-bold text-gray-900">Quick Actions</div>
    </div>
    <div class="card-body pt-2 d-flex flex-column gap-3">
        @can('employee.create')
            <a href="{{ route('employees.create') }}"
                class="btn btn-light-primary btn-sm text-start d-flex align-items-center">
                <i class="ki-outline ki-plus fs-5 me-2"></i>New Employee
            </a>
        @endcan
        @can('cpf_contribution.create')
            <a href="{{ route('cpf-contributions.index') }}"
                class="btn btn-light-info btn-sm text-start d-flex align-items-center">
                <i class="ki-outline ki-wallet fs-5 me-2"></i>Contribution Batches
            </a>
        @endcan
        @can('cpf_advance.create')
            <a href="{{ route('cpf-advances.create') }}"
                class="btn btn-light-warning btn-sm text-start d-flex align-items-center">
                <i class="ki-outline ki-handcart fs-5 me-2"></i>New Advance
            </a>
        @endcan
        @can('cpf_settlement.create')
            <a href="{{ route('cpf-settlements.create') }}"
                class="btn btn-light-danger btn-sm text-start d-flex align-items-center">
                <i class="ki-outline ki-financial-schedule fs-5 me-2"></i>New Final Settlement
            </a>
        @endcan
        @can('bank_interest.create')
            <a href="{{ route('bank-interest.distribute') }}"
                class="btn btn-light-success btn-sm text-start d-flex align-items-center">
                <i class="ki-outline ki-percentage fs-5 me-2"></i>New Interest Distribution
            </a>
        @endcan
        @can('report.view')
            <a href="{{ route('reports.index') }}" class="btn btn-light btn-sm text-start d-flex align-items-center">
                <i class="ki-outline ki-filter fs-5 me-2"></i>Reports
            </a>
        @endcan
    </div>
</div>
