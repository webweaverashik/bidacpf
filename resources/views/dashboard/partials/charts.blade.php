{{-- The core charts shared by every role. Members-by-grade is placed by the
     individual role views so its width can vary. Each card is gated by the same
     permission that governs its data source. The five fiscal-year series carry a
     Chart/List toggle (tabs); the Advance Portfolio donut is a point-in-time
     snapshot and has no list view. All FY-scoped charts repaint when the
     fiscal-year selector changes. --}}

{{-- Fund growth + ledger composition --}}
@can('cpf_ledger.view')
    <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
        <div class="col-xl-8">
            @include('dashboard.partials.chart-card', [
                'id' => 'bida_chart_fund_growth',
                'title' => 'CPF Fund Growth',
                'subtitle' => 'Month-end fund balance across the selected fiscal year',
                'height' => 350,
                'tabs' => true,
            ])
        </div>
        <div class="col-xl-4">
            @include('dashboard.partials.chart-card', [
                'id' => 'bida_chart_composition',
                'title' => 'Ledger Composition',
                'subtitle' => 'Credits by transaction type',
                'height' => 350,
                'tabs' => true,
            ])
        </div>
    </div>
@endcan

{{-- Contribution & Recovery overview + advance portfolio --}}
<div class="row g-5 g-xl-8 mb-5 mb-xl-8">
    @canany(['cpf_contribution.view', 'cpf_advance.view'])
        <div class="col-xl-8">
            @include('dashboard.partials.chart-card', [
                'id' => 'bida_chart_comparison',
                'title' => 'Contribution & Recovery Overview',
                'subtitle' => 'Monthly contributions, recoveries and loans disbursed',
                'height' => 340,
                'tabs' => true,
            ])
        </div>
    @endcanany
    @can('cpf_advance.view')
        <div class="col-xl-4">
            @include('dashboard.partials.chart-card', [
                'id' => 'bida_chart_advance_portfolio',
                'title' => 'Advance Portfolio',
                'subtitle' => 'Advances by status (as of today)',
                'height' => 340,
            ])
        </div>
    @endcan
</div>

{{-- Monthly contributions split + interest distribution --}}
<div class="row g-5 g-xl-8 mb-5 mb-xl-8">
    @can('cpf_contribution.view')
        <div class="col-xl-6">
            @include('dashboard.partials.chart-card', [
                'id' => 'bida_chart_contributions',
                'title' => 'Monthly Contributions',
                'subtitle' => 'Employee vs Government share, by month',
                'height' => 320,
                'tabs' => true,
            ])
        </div>
    @endcan
    @can('bank_interest.view')
        <div class="col-xl-6">
            @include('dashboard.partials.chart-card', [
                'id' => 'bida_chart_interest',
                'title' => 'Interest Distribution',
                'subtitle' => 'Bank interest distributed to members, by month',
                'height' => 320,
                'tabs' => true,
            ])
        </div>
    @endcan
</div>
