{{-- The four headline tiles. Each is gated by the permission that governs the
     module it summarises, so a role only sees figures it is allowed to. --}}
<div class="row g-5 g-xl-8 mb-5 mb-xl-8">
    @can('employee.view')
        <div class="col-sm-6 col-xl-3">
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
        <div class="col-sm-6 col-xl-3">
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
        <div class="col-sm-6 col-xl-3">
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

    @can('cpf_contribution.view')
        <div class="col-sm-6 col-xl-3">
            @include('dashboard.partials.stat-tile', [
                'label' => 'Contributions (FY ' . $currentFy . ')',
                'value' => $stats['contributions'],
                'icon' => 'ki-wallet',
                'color' => 'info',
                'money' => true,
                'link' => Route::has('cpf-contributions.index') ? route('cpf-contributions.index') : null,
                'linkText' => 'Contribution batches',
            ])
        </div>
    @endcan
</div>
