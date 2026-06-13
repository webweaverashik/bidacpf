{{-- The four core charts shared by every role. Members-by-grade is placed by the
     individual role views so its width can vary. Each card is gated by the same
     permission that governs its data source. --}}

{{-- Fiscal-year-scoped: fund growth + ledger composition --}}
@can('cpf_ledger.view')
    <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
        <div class="col-xl-8">
            @include('dashboard.partials.chart-card', [
                'id' => 'bida_chart_fund_growth',
                'title' => 'CPF Fund Growth',
                'subtitle' => 'Month-end fund balance across the selected fiscal year',
                'height' => 350,
            ])
        </div>
        <div class="col-xl-4">
            @include('dashboard.partials.chart-card', [
                'id' => 'bida_chart_composition',
                'title' => 'Ledger Composition',
                'subtitle' => 'Credits by transaction type',
                'height' => 350,
            ])
        </div>
    </div>
@endcan

{{-- Monthly contributions + advance portfolio --}}
@canany(['cpf_contribution.view', 'cpf_advance.view'])
    <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
        @can('cpf_contribution.view')
            <div class="col-xl-8">
                @include('dashboard.partials.chart-card', [
                    'id' => 'bida_chart_contributions',
                    'title' => 'Monthly Contributions',
                    'subtitle' => 'Employee vs Government share, by month',
                    'height' => 320,
                ])
            </div>
        @endcan
        @can('cpf_advance.view')
            <div class="col-xl-4">
                @include('dashboard.partials.chart-card', [
                    'id' => 'bida_chart_advance_portfolio',
                    'title' => 'Advance Portfolio',
                    'subtitle' => 'Advances by status',
                    'height' => 320,
                ])
            </div>
        @endcan
    </div>
@endcanany
