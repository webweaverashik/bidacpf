@extends('layouts.app')

@section('title', 'Employee Details')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/employees/show.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">Employee Details</h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('employees.index') }}" class="text-muted text-hover-primary">Employees</a>
            </li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">{{ $employee->name }}</li>
        </ul>
    </div>
@endsection

@section('content')
    @php
        $statusLabel = $employee->status?->value
            ? \Illuminate\Support\Str::headline($employee->status->value)
            : 'Active';
        $statusBadge = match (strtolower($employee->status?->value ?? 'active')) {
            'active' => 'success',
            'retired', 'resigned', 'deceased', 'inactive' => 'danger',
            default => 'secondary',
        };
    @endphp

    <!--begin::Layout-->
    <div class="d-flex flex-column flex-xl-row" id="kt_employee_show" data-employee-id="{{ $employee->id }}"
        data-is-active="{{ $employee->is_active ? 1 : 0 }}" data-toggle-url="{{ route('employees.toggleActive') }}"
        data-destroy-url="{{ route('employees.destroy', $employee) }}"
        data-activities-url="{{ route('employees.activities', $employee) }}"
        data-can-delete="{{ $currentBalance === 0 ? 1 : 0 }}">

        <!--begin::Sidebar-->
        <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
            <div class="card card-flush mb-0 @if (!$employee->is_active) border border-dashed border-danger @endif">
                <div class="card-header">
                    <div class="card-title">
                        <h2>Employee Info</h2>
                    </div>
                </div>

                <div class="card-body pt-0 fs-6">
                    <div class="mb-7">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-60px symbol-circle me-3">
                                <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" />
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fs-4 fw-bold text-gray-900 me-2">{{ $employee->name }}</span>
                                <span class="fw-bold text-gray-600">{{ $employee->cpf_account_no }}</span>
                                <span class="text-muted fs-7">{{ $employee->designation }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-7">
                        <span class="text-gray-500 fw-semibold">Activation</span>
                        <span data-kt-employee-badge="active"
                            class="badge rounded-pill badge-light-{{ $employee->is_active ? 'success' : 'danger' }}">
                            {{ $employee->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed mb-7 p-5">
                        <div class="d-flex flex-column w-100">
                            <span class="fs-7 fw-semibold text-gray-600">Current CPF Balance</span>
                            <span
                                class="fs-2hx fw-bold text-primary lh-1 mt-1">৳{{ number_format($currentBalance) }}</span>
                        </div>
                    </div>

                    <div class="separator separator-dashed mb-7"></div>

                    <div class="mb-7">
                        <h5 class="mb-4">Employment</h5>
                        <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                            <tr>
                                <td class="text-gray-500">Service Status:</td>
                                <td><span class="badge badge-light-{{ $statusBadge }}">{{ $statusLabel }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-gray-500">Grade / Step:</td>
                                <td class="text-gray-800">
                                    @if ($employee->payScaleStep)
                                        Grade {{ $employee->grade }} &middot; Step {{ $employee->current_step }}
                                    @else
                                        <span class="text-muted">Not assigned</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-gray-500">Basic Salary:</td>
                                <td class="text-gray-800">
                                    @if ($employee->current_basic_salary)
                                        ৳{{ number_format($employee->current_basic_salary) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-gray-500">Pay Scale:</td>
                                <td class="text-gray-800">{{ $employee->payScaleStep?->payScale?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-gray-500">Joining Date:</td>
                                <td class="text-gray-800">{{ optional($employee->joining_date)->format('d-M-Y') ?? '-' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-gray-500">Retirement Date:</td>
                                <td class="text-gray-800">
                                    {{ optional($employee->retirement_date)->format('d-M-Y') ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="separator separator-dashed mb-7"></div>

                    <div class="mb-7">
                        <h5 class="mb-4">Contact</h5>
                        <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                            <tr>
                                <td class="text-gray-500">Email:</td>
                                <td class="text-gray-800">{{ $employee->email ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-gray-500">Mobile:</td>
                                <td class="text-gray-800">{{ $employee->mobile_number ?: '-' }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="separator separator-dashed mb-7"></div>

                    <div class="mb-0">
                        <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                            <tr>
                                <td class="text-gray-500">Created By:</td>
                                <td class="text-gray-800">{{ $employee->creator?->name ?? 'System' }}</td>
                            </tr>
                            <tr>
                                <td class="text-gray-500">Created At:</td>
                                <td class="text-gray-800">
                                    {{ $employee->created_at->format('d-M-Y') }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $employee->created_at->format('h:i:s A, d-M-Y') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Sidebar-->

        <!--begin::Content-->
        <div class="flex-lg-row-fluid ms-lg-10">
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
                <li class="nav-item"><a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                        href="#kt_emp_tab_overview"><i class="ki-outline ki-profile-circle fs-3 me-2"></i> Overview</a></li>
                <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_emp_tab_ledger"><i class="ki-outline ki-book fs-3 me-2"></i> Ledger</a></li>
                <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_emp_tab_contributions"><i class="ki-outline ki-dollar fs-3 me-2"></i> Contributions</a>
                </li>
                <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_emp_tab_advances"><i class="ki-outline ki-handcart fs-3 me-2"></i> Advances</a></li>
                <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_emp_tab_interest"><i class="ki-outline ki-bank fs-3 me-2"></i> Interest</a></li>
                <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_emp_tab_salary"><i class="ki-outline ki-chart-line-up fs-3 me-2"></i> Salary History</a>
                </li>
                <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_emp_tab_activity"><i class="ki-outline ki-time fs-3 me-2"></i> Activity</a></li>
                <li class="nav-item ms-auto">
                    <a href="#" class="btn btn-primary ps-7" data-kt-menu-trigger="click"
                        data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                        Actions <i class="ki-outline ki-down fs-2 me-0"></i>
                    </a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 w-250px fs-6"
                        data-kt-menu="true">
                        @can('employee.update')
                            <div class="menu-item px-5">
                                <a href="{{ route('employees.edit', $employee) }}" class="menu-link px-5"><i
                                        class="ki-outline ki-pencil fs-3 me-2"></i> Edit Employee</a>
                            </div>
                        @endcan
                        @can('employee.update')
                            <div class="menu-item px-5">
                                <a href="#"
                                    class="menu-link px-5 {{ $employee->is_active ? 'text-hover-warning' : 'text-hover-success' }}"
                                    data-kt-employee-action="toggle-active">
                                    <i class="bi {{ $employee->is_active ? 'bi-person-slash' : 'bi-person-check' }} fs-3 me-2"
                                        data-kt-employee-icon="toggle"></i>
                                    <span
                                        data-kt-employee-label="toggle">{{ $employee->is_active ? 'Deactivate Employee' : 'Activate Employee' }}</span>
                                </a>
                            </div>
                        @endcan
                        @canany(['cpf_advance.create', 'cpf_advance.view', 'bank_interest.create', 'bank_interest.view'])
                            <div class="separator my-3"></div>
                            <div class="menu-item px-5">
                                <div class="menu-content text-muted px-2 fs-7 text-uppercase">Quick Links</div>
                            </div>
                            @can('cpf_advance.create')
                                <div class="menu-item px-5"><a href="{{ route('cpf-advances.create') }}"
                                        class="menu-link px-5"><i class="ki-outline ki-handcart fs-3 me-2"></i> New CPF
                                        Advance</a></div>
                            @endcan
                            @can('cpf_advance.view')
                                <div class="menu-item px-5"><a href="{{ route('cpf-advances.index') }}"
                                        class="menu-link px-5"><i class="ki-outline ki-document fs-3 me-2"></i> All CPF
                                        Advances</a></div>
                            @endcan
                            @can('bank_interest.create')
                                <div class="menu-item px-5"><a href="{{ route('bank-interest.distribute') }}"
                                        class="menu-link px-5"><i class="ki-outline ki-bank fs-3 me-2"></i> Bank Interest
                                        Distribution</a></div>
                            @endcan
                            @can('bank_interest.view')
                                <div class="menu-item px-5"><a href="{{ route('bank-interest.index') }}"
                                        class="menu-link px-5"><i class="ki-outline ki-chart-pie-simple fs-3 me-2"></i> Interest
                                        Distributions</a></div>
                            @endcan
                        @endcanany
                        @can('employee.delete')
                            <div class="separator my-3"></div>
                            <div class="menu-item px-5">
                                <a href="#" class="menu-link px-5 text-hover-danger"
                                    data-kt-employee-action="delete"><i class="ki-outline ki-trash fs-3 me-2"></i> Delete
                                    Employee</a>
                            </div>
                        @endcan
                    </div>
                </li>

            </ul>

            <div class="tab-content" id="kt_employee_tab_content">

                {{-- ════════════ OVERVIEW ════════════ --}}
                <div class="tab-pane fade show active" id="kt_emp_tab_overview" role="tabpanel">
                    <div class="card mb-5 mb-xl-10">
                        <div class="card-header cursor-pointer">
                            <div class="card-title m-0">
                                <h3 class="fw-bold m-0">Profile Details</h3>
                            </div>
                        </div>
                        <div class="card-body p-9">
                            <div class="row mb-5"><label class="col-lg-4 fw-semibold text-muted fs-6">Full Name</label>
                                <div class="col-lg-8"><span
                                        class="fw-bold fs-6 text-gray-800">{{ $employee->name }}</span></div>
                            </div>
                            <div class="row mb-5"><label class="col-lg-4 fw-semibold text-muted fs-6">CPF Account
                                    No.</label>
                                <div class="col-lg-8"><span
                                        class="fw-bold fs-6 text-gray-800">{{ $employee->cpf_account_no }}</span></div>
                            </div>
                            <div class="row mb-5"><label class="col-lg-4 fw-semibold text-muted fs-6">Designation</label>
                                <div class="col-lg-8"><span
                                        class="fw-bold fs-6 text-gray-800">{{ $employee->designation }}</span></div>
                            </div>
                            <div class="row mb-5"><label class="col-lg-4 fw-semibold text-muted fs-6">Email</label>
                                <div class="col-lg-8"><span
                                        class="fw-bold fs-6 text-gray-800">{{ $employee->email ?: '-' }}</span></div>
                            </div>
                            <div class="row mb-5"><label class="col-lg-4 fw-semibold text-muted fs-6">Mobile</label>
                                <div class="col-lg-8"><span
                                        class="fw-bold fs-6 text-gray-800">{{ $employee->mobile_number ?: '-' }}</span>
                                </div>
                            </div>
                            <div class="row mb-5">
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Grade / Step</label>
                                <div class="col-lg-8"><span class="fw-bold fs-6 text-gray-800">
                                        @if ($employee->payScaleStep)
                                            Grade {{ $employee->grade }} &middot; Step {{ $employee->current_step }}<span
                                                class="text-muted fw-semibold ms-2">(৳{{ number_format($employee->current_basic_salary) }})</span>
                                        @else
                                            -
                                        @endif
                                    </span></div>
                            </div>
                            <div class="row mb-5"><label class="col-lg-4 fw-semibold text-muted fs-6">Service
                                    Status</label>
                                <div class="col-lg-8"><span
                                        class="badge badge-light-{{ $statusBadge }}">{{ $statusLabel }}</span></div>
                            </div>
                            <div class="row mb-5"><label class="col-lg-4 fw-semibold text-muted fs-6">Joining Date</label>
                                <div class="col-lg-8"><span
                                        class="fw-bold fs-6 text-gray-800">{{ optional($employee->joining_date)->format('d-M-Y') ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="row mb-0"><label class="col-lg-4 fw-semibold text-muted fs-6">Retirement
                                    Date</label>
                                <div class="col-lg-8"><span
                                        class="fw-bold fs-6 text-gray-800">{{ optional($employee->retirement_date)->format('d-M-Y') ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-5 mb-xl-10">
                        <div class="card-header">
                            <div class="card-title">
                                <h3>Opening Balance</h3>
                            </div>
                            @if ($employee->openingBalance)
                                <div class="card-toolbar"><span class="badge badge-light-primary fs-7">Effective
                                        {{ optional($employee->openingBalance->effective_date)->format('d-M-Y') }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="card-body">
                            @if ($ob = $employee->openingBalance)
                                <div class="row g-5">
                                    <div class="col-md-4">
                                        <div class="border border-dashed border-gray-300 rounded p-4"><span
                                                class="fs-6 fw-semibold text-muted d-block">Self Contribution</span><span
                                                class="fs-3 fw-bold text-gray-800">৳{{ number_format($ob->self_contribution) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border border-dashed border-gray-300 rounded p-4"><span
                                                class="fs-6 fw-semibold text-muted d-block">Govt. Contribution</span><span
                                                class="fs-3 fw-bold text-gray-800">৳{{ number_format($ob->government_contribution) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border border-dashed border-gray-300 rounded p-4"><span
                                                class="fs-6 fw-semibold text-muted d-block">Interest Amount</span><span
                                                class="fs-3 fw-bold text-gray-800">৳{{ number_format($ob->interest_amount) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border border-dashed border-warning rounded p-4"><span
                                                class="fs-6 fw-semibold text-muted d-block">Outstanding Advance</span><span
                                                class="fs-3 fw-bold text-gray-800">৳{{ number_format($ob->outstanding_advance) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="border border-dashed border-success rounded p-4"><span
                                                class="fs-6 fw-semibold text-muted d-block">Net Opening Balance</span><span
                                                class="fs-2 fw-bold text-success">৳{{ number_format($ob->net_balance) }}</span>
                                        </div>
                                    </div>
                                </div>
                                @if ($ob->remarks)
                                    <div class="text-gray-600 fs-7 mt-5"><span class="fw-semibold">Remarks:</span>
                                        {{ $ob->remarks }}</div>
                                @endif
                            @else
                                <div class="text-center py-10"><i
                                        class="ki-outline ki-information-5 fs-3x text-gray-400 mb-3"></i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">No Opening Balance</h4>
                                    <p class="text-gray-500 fs-6 mb-0">No onboarding opening balance has been recorded for
                                        this employee.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ════════════ LEDGER ════════════ --}}
                <div class="tab-pane fade" id="kt_emp_tab_ledger" role="tabpanel">
                    <div class="card mb-6 mb-xl-9">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>CPF Ledger Summary</h2>
                            </div>
                        </div>
                        <div class="card-body py-0">
                            <div class="d-flex flex-wrap mb-5">
                                <div class="border border-dashed border-gray-300 w-150px rounded my-3 p-4 me-6">
                                    <span
                                        class="fs-2 fw-bold text-gray-800 lh-1">৳{{ number_format($currentBalance) }}</span>
                                    <span class="fs-7 fw-semibold text-muted d-block lh-1 pt-2">Current Balance</span>
                                </div>
                                <div class="border border-dashed border-success w-150px rounded my-3 p-4 me-6">
                                    <span
                                        class="fs-2 fw-bold text-gray-800 lh-1">৳{{ number_format($ledgerCredits) }}</span>
                                    <span class="fs-7 fw-semibold text-muted d-block lh-1 pt-2">Total Credits</span>
                                </div>
                                <div class="border border-dashed border-danger w-150px rounded my-3 p-4 me-6">
                                    <span
                                        class="fs-2 fw-bold text-gray-800 lh-1">৳{{ number_format($ledgerDebits) }}</span>
                                    <span class="fs-7 fw-semibold text-muted d-block lh-1 pt-2">Total Debits</span>
                                </div>
                                <div class="border border-dashed border-warning w-150px rounded my-3 p-4 me-6">
                                    <span
                                        class="fs-2 fw-bold text-gray-800 lh-1">৳{{ number_format($outstandingAdvance) }}</span>
                                    <span class="fs-7 fw-semibold text-muted d-block lh-1 pt-2">Outstanding Advance</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0 pt-6">
                            <!--begin::Card title (search)-->
                            <div class="card-title">
                                <div class="d-flex align-items-center position-relative my-1">
                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                                    <input type="text" data-kt-ledger-filter="search"
                                        class="form-control form-control-solid w-md-300px ps-12"
                                        placeholder="Search ledger">
                                </div>
                            </div>
                            <!--end::Card title-->

                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Filter-->
                                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                                    data-kt-menu-placement="bottom-end">
                                    <i class="ki-outline ki-filter fs-2"></i>Filter
                                </button>
                                <!--begin::Filter menu-->
                                <div id="kt_ledger_filter_menu" class="menu menu-sub menu-sub-dropdown w-300px w-md-325px"
                                    data-kt-menu="true">
                                    <div class="px-7 py-5">
                                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                                    </div>
                                    <div class="separator border-gray-200"></div>
                                    <div class="px-7 py-5" data-kt-ledger-filter="form">
                                        <div class="mb-5">
                                            <label class="form-label fs-6 fw-semibold">Fiscal Year:</label>
                                            <select id="kt_ledger_filter_fy" class="form-select form-select-solid fw-bold"
                                                data-placeholder="Select fiscal year">
                                                <option value="">All Fiscal Years</option>
                                                @foreach ($ledgerFiscalYears as $fy)
                                                    <option value="{{ $fy }}">{{ $fy }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-5">
                                            <label class="form-label fs-6 fw-semibold">Transaction Type:</label>
                                            <select id="kt_ledger_filter_type"
                                                class="form-select form-select-solid fw-bold"
                                                data-placeholder="Select type">
                                                <option value="">All Types</option>
                                                @foreach ($ledgerTypes as $type)
                                                    <option value="{{ $type }}">
                                                        {{ \Illuminate\Support\Str::headline($type) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-5">
                                            <label class="form-label fs-6 fw-semibold">Month:</label>
                                            <select id="kt_ledger_filter_month"
                                                class="form-select form-select-solid fw-bold">
                                                <option value="">All Months</option>
                                                <option value="01">January</option>
                                                <option value="02">February</option>
                                                <option value="03">March</option>
                                                <option value="04">April</option>
                                                <option value="05">May</option>
                                                <option value="06">June</option>
                                                <option value="07">July</option>
                                                <option value="08">August</option>
                                                <option value="09">September</option>
                                                <option value="10">October</option>
                                                <option value="11">November</option>
                                                <option value="12">December</option>
                                            </select>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="reset"
                                                class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                                data-kt-menu-dismiss="true" data-kt-ledger-filter="reset">Reset</button>
                                            <button type="button" class="btn btn-primary fw-semibold px-6"
                                                data-kt-menu-dismiss="true" data-kt-ledger-filter="filter">Apply</button>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Filter menu-->

                                @can('cpf_ledger.view')
                                    <!--begin::Export dropdown-->
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                                            data-kt-menu-placement="bottom-end">
                                            <i class="ki-outline ki-exit-up fs-2"></i>Export
                                        </button>
                                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                                            data-kt-menu="true">
                                            <div class="menu-item px-3">
                                                <a href="{{ route('employees.ledger.excel', $employee) }}"
                                                    data-kt-ledger-export
                                                    data-base-url="{{ route('employees.ledger.excel', $employee) }}"
                                                    class="menu-link px-3">Export as Excel</a>
                                            </div>
                                            <div class="menu-item px-3">
                                                <a href="{{ route('employees.ledger.pdf', $employee) }}" data-kt-ledger-export
                                                    data-base-url="{{ route('employees.ledger.pdf', $employee) }}"
                                                    class="menu-link px-3">Export as PDF</a>
                                            </div>
                                        </div>
                                    </div>
                                    <!--end::Export dropdown-->
                                @endcan
                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Card header-->
                        <div class="card-body pb-5">
                            <table id="kt_employee_ledger_table"
                                class="table ashik-table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4 w-100">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-30px">#</th>
                                        <th class="min-w-100px">Date</th>
                                        <th class="min-w-150px">Type</th>
                                        <th class="min-w-120px">Source</th>
                                        <th class="min-w-100px">Reference</th>
                                        <th class="text-end min-w-100px">Debit</th>
                                        <th class="text-end min-w-100px">Credit</th>
                                        <th class="text-end min-w-110px">Balance</th>
                                        <th class="min-w-150px">Remarks</th>
                                        <th class="min-w-100px">By</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    @foreach ($employee->ledgers as $i => $ledger)
                                        <tr data-fy="{{ $ledger->transaction_date ? \App\Support\FiscalYearService::fromDate($ledger->transaction_date) : '' }}"
                                            data-type="{{ $ledger->transaction_type?->value }}"
                                            data-month="{{ optional($ledger->transaction_date)->format('m') }}">
                                            <td>{{ $i + 1 }}</td>
                                            <td data-order="{{ optional($ledger->transaction_date)->timestamp }}">
                                                {{ optional($ledger->transaction_date)->format('d-M-Y') }}</td>
                                            <td><span
                                                    class="badge badge-light-primary">{{ \Illuminate\Support\Str::headline($ledger->transaction_type?->value ?? '') }}</span>
                                            </td>
                                            <td class="text-gray-600">{{ $ledger->source_label ?: '-' }}</td>
                                            <td class="text-gray-600">{{ $ledger->reference_no ?: '-' }}</td>
                                            <td class="text-end" data-order="{{ $ledger->debit }}">
                                                @if ($ledger->debit > 0)
                                                    <span
                                                    class="text-danger fw-bold">৳{{ number_format($ledger->debit) }}</span>@else<span
                                                        class="text-muted">–</span>
                                                @endif
                                            </td>
                                            <td class="text-end" data-order="{{ $ledger->credit }}">
                                                @if ($ledger->credit > 0)
                                                    <span
                                                    class="text-success fw-bold">৳{{ number_format($ledger->credit) }}</span>@else<span
                                                        class="text-muted">–</span>
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold text-gray-900"
                                                data-order="{{ $ledger->balance }}">
                                                ৳{{ number_format($ledger->balance) }}</td>
                                            <td class="text-gray-600">{{ $ledger->remarks ?: '-' }}</td>
                                            <td class="text-gray-600">{{ $ledger->creator?->name ?? 'System' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ════════════ CONTRIBUTIONS ════════════ --}}
                <div class="tab-pane fade" id="kt_emp_tab_contributions" role="tabpanel">
                    @php $contributions = $employee->contributions->sortByDesc(fn($c) => $c->batch?->contribution_month)->values(); @endphp
                    <div class="card mb-6 mb-xl-9">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>Monthly Contributions</h2>
                            </div>
                            <div class="card-toolbar"><span
                                    class="badge badge-light-primary fs-7">{{ $contributions->count() }} entries</span>
                            </div>
                        </div>
                        <div class="card-body pb-5">
                            <table id="kt_employee_contributions_table"
                                class="table ashik-table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4 w-100">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-30px">#</th>
                                        <th class="min-w-120px">Month</th>
                                        <th>Fiscal Year</th>
                                        <th class="text-end">Basic Salary</th>
                                        <th class="text-end">Employee (10%)</th>
                                        <th class="text-end">Govt. (8.33%)</th>
                                        <th class="text-end">Total</th>
                                        <th>Batch Status</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    @foreach ($contributions as $i => $c)
                                        @php
                                            $bStatus = $c->batch?->status?->value;
                                            $bBadge = match (strtolower($bStatus ?? '')) {
                                                'submitted' => 'success',
                                                'draft' => 'warning',
                                                'reversed' => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td data-order="{{ optional($c->batch?->contribution_month)->timestamp }}"
                                                class="text-gray-800">{{ $c->batch?->month_label ?? '-' }}</td>
                                            <td>{{ $c->batch?->fiscal_year ?? '-' }}</td>
                                            <td class="text-end">৳{{ number_format($c->basic_salary) }}</td>
                                            <td class="text-end text-success">
                                                ৳{{ number_format($c->employee_contribution) }}</td>
                                            <td class="text-end text-success">
                                                ৳{{ number_format($c->government_contribution) }}</td>
                                            <td class="text-end fw-bold text-gray-900">
                                                ৳{{ number_format($c->totalContribution()) }}</td>
                                            <td><span
                                                    class="badge badge-light-{{ $bBadge }}">{{ $bStatus ? \Illuminate\Support\Str::headline($bStatus) : '-' }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold text-gray-800 border-top border-gray-200">
                                        <td colspan="4" class="text-end">Totals</td>
                                        <td class="text-end text-success">৳{{ number_format($totalEmployeeContribution) }}
                                        </td>
                                        <td class="text-end text-success">৳{{ number_format($totalGovtContribution) }}
                                        </td>
                                        <td class="text-end">
                                            ৳{{ number_format($totalEmployeeContribution + $totalGovtContribution) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ════════════ ADVANCES ════════════ --}}
                <div class="tab-pane fade" id="kt_emp_tab_advances" role="tabpanel">
                    <div class="d-flex justify-content-end gap-2 mb-5">
                        @can('cpf_advance.view')
                            <a href="{{ route('cpf-advances.index') }}" class="btn btn-sm btn-light"><i
                                    class="ki-outline ki-document fs-3"></i> All Advances</a>
                        @endcan
                        @can('cpf_advance.create')
                            <a href="{{ route('cpf-advances.create') }}" class="btn btn-sm btn-light-primary"><i
                                    class="ki-outline ki-plus fs-3"></i> New Advance</a>
                        @endcan
                    </div>

                    @forelse ($employee->advances as $advance)
                        @php
                            $aStatus = $advance->status?->value;
                            $aBadge = match (strtolower($aStatus ?? '')) {
                                'approved', 'disbursed' => 'primary',
                                'completed' => 'success',
                                'pending' => 'warning',
                                'rejected', 'cancelled' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <div class="card mb-6 mb-xl-9">
                            <div class="card-header border-0">
                                <div class="card-title">
                                    <h3>{{ $advance->advance_no }}</h3>
                                    <span
                                        class="badge badge-light-{{ $aBadge }} ms-3">{{ $aStatus ? \Illuminate\Support\Str::headline($aStatus) : '-' }}</span>
                                </div>
                                <div class="card-toolbar">
                                    @can('cpf_advance.view')
                                        <a href="{{ route('cpf-advances.show', $advance) }}"
                                            class="btn btn-sm btn-light me-2"><i class="ki-outline ki-eye fs-4"></i>
                                            Details</a>
                                    @endcan
                                    @can('cpf_advance.recovery')
                                        @if (!$advance->isCompleted())
                                            <a href="{{ route('cpf-advances.recovery.create', $advance) }}"
                                                class="btn btn-sm btn-light-primary"><i class="ki-outline ki-plus fs-4"></i>
                                                Add Recovery</a>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                            <div class="card-body py-0">
                                <div class="d-flex flex-wrap mb-5">
                                    <div class="border border-dashed border-gray-300 rounded my-3 p-4 me-6 min-w-125px">
                                        <span
                                            class="fs-3 fw-bold text-gray-800 lh-1">৳{{ number_format($advance->approved_amount) }}</span><span
                                            class="fs-7 fw-semibold text-muted d-block lh-1 pt-2">Approved</span></div>
                                    <div class="border border-dashed border-success rounded my-3 p-4 me-6 min-w-125px">
                                        <span
                                            class="fs-3 fw-bold text-gray-800 lh-1">৳{{ number_format($advance->totalRecovered()) }}</span><span
                                            class="fs-7 fw-semibold text-muted d-block lh-1 pt-2">Recovered</span></div>
                                    <div class="border border-dashed border-warning rounded my-3 p-4 me-6 min-w-125px">
                                        <span
                                            class="fs-3 fw-bold text-gray-800 lh-1">৳{{ number_format($advance->outstanding_amount) }}</span><span
                                            class="fs-7 fw-semibold text-muted d-block lh-1 pt-2">Outstanding</span></div>
                                    <div class="border border-dashed border-gray-300 rounded my-3 p-4 me-6 min-w-125px">
                                        <span
                                            class="fs-3 fw-bold text-gray-800 lh-1">{{ $advance->installment_count }}</span><span
                                            class="fs-7 fw-semibold text-muted d-block lh-1 pt-2">Installments</span></div>
                                    <div class="border border-dashed border-gray-300 rounded my-3 p-4 me-6 min-w-125px">
                                        <span
                                            class="fs-3 fw-bold text-gray-800 lh-1">{{ $advance->interest_rate }}%</span><span
                                            class="fs-7 fw-semibold text-muted d-block lh-1 pt-2">Interest Rate</span>
                                    </div>
                                </div>
                                <div class="text-gray-600 fs-7 mb-5">
                                    Applied {{ optional($advance->application_date)->format('d-M-Y') ?? '-' }} &middot;
                                    Approved {{ optional($advance->approval_date)->format('d-M-Y') ?? '-' }}
                                    @if ($advance->approver)
                                        &middot; by {{ $advance->approver->name }}
                                    @endif
                                    @if ($advance->remarks)
                                        <br><span class="fw-semibold">Remarks:</span> {{ $advance->remarks }}
                                    @endif
                                </div>
                                <h5 class="mb-3">Recovery Installments</h5>
                                <table class="table ashik-table table-row-dashed align-middle fs-6 fw-semibold gy-3">
                                    <thead>
                                        <tr class="fw-bold fs-7 text-uppercase text-muted gs-0">
                                            <th class="w-30px">#</th>
                                            <th>Date</th>
                                            <th class="text-end">Amount</th>
                                            <th>Remarks</th>
                                            <th>By</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-700">
                                        @forelse ($advance->recoveries->sortBy('recovery_date') as $j => $rec)
                                            <tr>
                                                <td>{{ $j + 1 }}</td>
                                                <td>{{ optional($rec->recovery_date)->format('d-M-Y') }}</td>
                                                <td class="text-end text-success">৳{{ number_format($rec->amount) }}</td>
                                                <td class="text-gray-600">{{ $rec->remarks ?: '-' }}</td>
                                                <td class="text-gray-600">{{ $rec->creator?->name ?? 'System' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-5">No recoveries
                                                    posted yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="card mb-6 mb-xl-9">
                            <div class="card-body text-center py-15"><i
                                    class="ki-outline ki-handcart fs-3x text-gray-400 mb-3"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">No CPF Advances</h4>
                                <p class="text-gray-500 fs-6 mb-0">This employee has not taken any CPF advances.</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- ════════════ INTEREST ════════════ --}}
                <div class="tab-pane fade" id="kt_emp_tab_interest" role="tabpanel">
                    <div class="card mb-6 mb-xl-9">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>Bank Interest Distributions</h2>
                            </div>
                            <div class="card-toolbar">
                                <span class="badge badge-light-primary fs-7 me-3">Total
                                    ৳{{ number_format($totalBankInterest) }}</span>
                                @can('bank_interest.view')
                                    <a href="{{ route('bank-interest.index') }}" class="btn btn-sm btn-light me-2"><i
                                            class="ki-outline ki-chart-pie-simple fs-3"></i> All Distributions</a>
                                @endcan
                                @can('bank_interest.create')
                                    <a href="{{ route('bank-interest.distribute') }}"
                                        class="btn btn-sm btn-light-primary"><i class="ki-outline ki-plus fs-3"></i> New
                                        Distribution</a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body pb-5">
                            <table id="kt_employee_interest_table"
                                class="table ashik-table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4 w-100">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-30px">#</th>
                                        <th class="min-w-120px">Distribution Date</th>
                                        <th>Fiscal Year</th>
                                        <th class="text-end">Eligible Balance</th>
                                        <th class="text-end">Interest Credited</th>
                                        <th>Batch Status</th>
                                        <th class="text-end w-80px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    @foreach ($employee->interestDistributions->sortByDesc(fn($d) => $d->batch?->distribution_date)->values() as $i => $dist)
                                        @php
                                            $iStatus = $dist->batch?->status?->value;
                                            $iBadge = match (strtolower($iStatus ?? '')) {
                                                'submitted' => 'success',
                                                'draft' => 'warning',
                                                'reversed' => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td data-order="{{ optional($dist->batch?->distribution_date)->timestamp }}"
                                                class="text-gray-800">
                                                {{ optional($dist->batch?->distribution_date)->format('d-M-Y') ?? '-' }}
                                            </td>
                                            <td>{{ $dist->batch?->fiscal_year ?? '-' }}</td>
                                            <td class="text-end">৳{{ number_format($dist->eligible_balance) }}</td>
                                            <td class="text-end text-success fw-bold">
                                                ৳{{ number_format($dist->interest_amount) }}</td>
                                            <td><span
                                                    class="badge badge-light-{{ $iBadge }}">{{ $iStatus ? \Illuminate\Support\Str::headline($iStatus) : '-' }}</span>
                                            </td>
                                            <td class="text-end">
                                                @can('bank_interest.view')
                                                    @if ($dist->bank_interest_batch_id)
                                                        <a href="{{ route('bank-interest.show', $dist->bank_interest_batch_id) }}"
                                                            class="btn btn-icon btn-sm btn-light-primary"
                                                            data-bs-toggle="tooltip" title="View distribution batch"><i
                                                                class="ki-outline ki-eye fs-4"></i></a>
                                                    @endif
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ════════════ SALARY HISTORY ════════════ --}}
                <div class="tab-pane fade" id="kt_emp_tab_salary" role="tabpanel">
                    <div class="card mb-6 mb-xl-9">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>Salary &amp; Step History</h2>
                            </div>
                        </div>
                        <div class="card-body pb-5">
                            <table id="kt_employee_salary_table"
                                class="table ashik-table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4 w-100">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-30px">#</th>
                                        <th class="min-w-110px">Effective Date</th>
                                        <th>Grade</th>
                                        <th>Step</th>
                                        <th class="text-end">Basic Salary</th>
                                        <th>Change Type</th>
                                        <th class="min-w-160px">Recorded</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    @foreach ($employee->salaryHistories->sortByDesc('effective_date')->values() as $i => $h)
                                        @php
                                            $ctBadge = match (strtolower($h->change_type ?? '')) {
                                                'initial' => 'primary',
                                                'increment' => 'success',
                                                'revision', 'promotion' => 'info',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td data-order="{{ optional($h->effective_date)->timestamp }}"
                                                class="text-gray-800">{{ optional($h->effective_date)->format('d-M-Y') }}
                                            </td>
                                            <td>{{ $h->payScaleStep?->grade ?? '-' }}</td>
                                            <td>{{ $h->payScaleStep?->step ?? '-' }}</td>
                                            <td class="text-end">
                                                @if ($h->payScaleStep)
                                                    ৳{{ number_format($h->payScaleStep->basic_salary) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td><span
                                                    class="badge badge-light-{{ $ctBadge }}">{{ \Illuminate\Support\Str::headline($h->change_type ?? '') }}</span>
                                            </td>
                                            <td data-order="{{ optional($h->created_at)->timestamp }}">
                                                {{ optional($h->created_at)->format('d-M-Y h:i A') }}
                                                <span
                                                    class="text-muted fs-8 d-block">{{ optional($h->created_at)->diffForHumans() }}</span>
                                            </td>
                                            <td class="text-gray-600">{{ $h->remarks ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ════════════ ACTIVITY (AJAX / server-side) ════════════ --}}
                <div class="tab-pane fade" id="kt_emp_tab_activity" role="tabpanel">
                    <div class="card mb-6 mb-xl-9">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <div class="d-flex align-items-center position-relative my-1">
                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                                    <input type="text" data-kt-activity-filter="search"
                                        class="form-control form-control-solid w-md-300px ps-12"
                                        placeholder="Search activity">
                                </div>
                            </div>
                            <div class="card-toolbar">
                                <span class="badge badge-light-primary fs-7 me-3">{{ $activityCount }} records</span>
                                <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                                    data-kt-menu-placement="bottom-end"><i
                                        class="ki-outline ki-filter fs-2"></i>Filter</button>
                                <div id="kt_activity_filter_menu"
                                    class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                                    <div class="px-7 py-5">
                                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                                    </div>
                                    <div class="separator border-gray-200"></div>
                                    <div class="px-7 py-5" data-kt-activity-filter="form">
                                        <div class="mb-5">
                                            <label class="form-label fs-6 fw-semibold">Event:</label>
                                            <select id="kt_activity_filter_event"
                                                class="form-select form-select-solid fw-bold">
                                                <option value="">All Events</option>
                                                <option value="created">Created</option>
                                                <option value="updated">Updated</option>
                                                <option value="deleted">Deleted</option>
                                            </select>
                                        </div>
                                        <div class="mb-5">
                                            <label class="form-label fs-6 fw-semibold">Subject:</label>
                                            <select id="kt_activity_filter_subject"
                                                class="form-select form-select-solid fw-bold">
                                                <option value="">All Subjects</option>
                                                <option value="employee">Employee</option>
                                                <option value="opening_balance">Opening Balance</option>
                                                <option value="salary">Salary History</option>
                                                <option value="advance">Advance</option>
                                                <option value="recovery">Advance Recovery</option>
                                            </select>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="reset"
                                                class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                                data-kt-menu-dismiss="true" data-kt-activity-filter="reset">Reset</button>
                                            <button type="button" class="btn btn-primary fw-semibold px-6"
                                                data-kt-menu-dismiss="true"
                                                data-kt-activity-filter="filter">Apply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pb-5">
                            <table id="kt_employee_activity_table"
                                class="table ashik-table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4 w-100">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-30px">#</th>
                                        <th>Description</th>
                                        <th>Event</th>
                                        <th>Subject</th>
                                        <th class="min-w-200px">Changes</th>
                                        <th>By</th>
                                        <th>When</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Content-->
    </div>
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script src="{{ asset('js/employees/show.js') }}"></script>
@endpush
