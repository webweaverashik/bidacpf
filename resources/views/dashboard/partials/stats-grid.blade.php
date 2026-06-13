{{-- Two bands of stat tiles:
       1. "As of today" — point-in-time figures that do NOT change with the FY switch.
       2. "Selected fiscal year" — figures scoped to the chosen FY; BidaDashboard
          repaints these (and the FY label) when the fiscal year changes.
     Each tile is gated by the permission governing the module it summarises. --}}
@php $fyStats = $chart['stats']; @endphp

{{-- Band 1 — point in time --}}
<div class="d-flex align-items-center mb-4">
    <span class="fs-6 fw-bold text-gray-800">Overview</span>
    <span class="fs-8 text-muted ms-2">as of {{ now()->format('d M Y') }}</span>
</div>
<div class="row g-5 g-xl-8 mb-4">
    @can('employee.view')
        <div class="col-sm-6 col-xl-4">
            @include('dashboard.partials.stat-tile', [
                'label' => 'Active CPF Members',
                'value' => $stats['members'],
                'icon' => 'ki-people',
                'color' => 'primary',
                'link' => Route::has('employees.index') ? route('employees.index') : null,
                'linkText' => 'All employees',
            ])
        </div>
    @endcan

    @can('cpf_ledger.view')
        <div class="col-sm-6 col-xl-4">
            @include('dashboard.partials.stat-tile', [
                'label' => 'Total CPF Fund Balance',
                'value' => $stats['fund'],
                'icon' => 'ki-bank',
                'color' => 'success',
                'money' => true,
                'link' => Route::has('cpf-ledger.index') ? route('cpf-ledger.index') : null,
                'linkText' => 'View ledger',
            ])
        </div>
    @endcan

    @can('cpf_advance.view')
        <div class="col-sm-6 col-xl-4">
            @include('dashboard.partials.stat-tile', [
                'label' => 'Outstanding Advances',
                'value' => $stats['outstanding'],
                'icon' => 'ki-handcart',
                'color' => 'warning',
                'money' => true,
                'link' => Route::has('cpf-advances.outstanding') ? route('cpf-advances.outstanding') : null,
                'linkText' => 'Outstanding loans',
            ])
        </div>
    @endcan
</div>

{{-- Band 2 — selected fiscal year (live) --}}
<div class="d-flex align-items-center mb-4 mt-2">
    <span class="fs-6 fw-bold text-gray-800">Fiscal year</span>
    <span class="badge badge-light-primary ms-2" data-bida-dashboard="fy-label">FY {{ $currentFy }}</span>
</div>
<div class="row g-5 g-xl-8 mb-5 mb-xl-8">
    @can('cpf_contribution.view')
        <div class="col-sm-6 col-xl-3">
            @include('dashboard.partials.stat-tile', [
                'label' => 'Contributions Collected',
                'value' => $fyStats['contributions'],
                'icon' => 'ki-wallet',
                'color' => 'info',
                'money' => true,
                'statKey' => 'contributions',
            ])
        </div>
    @endcan

    @can('cpf_advance.view')
        <div class="col-sm-6 col-xl-3">
            @include('dashboard.partials.stat-tile', [
                'label' => 'Loans Taken',
                'value' => $fyStats['loans_taken_amount'],
                'icon' => 'ki-handcart',
                'color' => 'warning',
                'money' => true,
                'statKey' => 'loans',
                'subtitle' => number_format($fyStats['loans_taken_count']) . ' advances disbursed',
                'subStatKey' => 'loans_count',
            ])
        </div>
    @endcan

    @can('cpf_advance.view')
        <div class="col-sm-6 col-xl-3">
            @include('dashboard.partials.stat-tile', [
                'label' => 'Advances Recovered',
                'value' => $fyStats['recovered'],
                'icon' => 'ki-arrow-down',
                'color' => 'success',
                'money' => true,
                'statKey' => 'recovered',
            ])
        </div>
    @endcan

    @can('bank_interest.view')
        <div class="col-sm-6 col-xl-3">
            @include('dashboard.partials.stat-tile', [
                'label' => 'Interest Distributed',
                'value' => $fyStats['interest_distributed'],
                'icon' => 'ki-percentage',
                'color' => 'primary',
                'money' => true,
                'statKey' => 'interest',
            ])
        </div>
    @endcan
</div>
