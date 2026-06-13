{{-- Emits the server data the dashboard JS needs and loads the page assets.
     window.BidaDashboardConfig is read by BidaDashboard (bida-dashboard.js, Step 3)
     to render the ApexCharts and to repaint them when the fiscal year changes. --}}
@php
    $bidaConfig = [
        'chartsUrl' => route('dashboard.charts'),
        'currency' => '৳',
        'fiscalYear' => $currentFy,
        'charts' => [
            'months' => $chart['months'],
            'fundGrowth' => $chart['fund_growth'],
            'employeeContribution' => $chart['employee_contribution'],
            'governmentContribution' => $chart['government_contribution'],
            'composition' => $chart['composition'],
            'advancePortfolio' => [
                'labels' => $advancePortfolio['labels'],
                'values' => $advancePortfolio['values'],
            ],
            'membersByGrade' => $membersByGrade,
        ],
    ];
@endphp

@push('page-css')
    <link href="{{ asset('css/dashboard/dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush

@push('page-js')
    <script>
        window.BidaDashboardConfig = @json($bidaConfig);
    </script>
    <script src="{{ asset('js/dashboard/bida-dashboard.js') }}"></script>
@endpush
