{{-- Drives the three fiscal-year-scoped charts. BidaDashboard listens for changes
     on [data-bida-dashboard="fy-select"] and repaints the charts via AJAX. --}}
<div class="d-flex align-items-center flex-wrap gap-2">
    <span class="fs-7 fw-semibold text-muted">Fiscal year</span>
    <div class="w-175px">
        <select class="form-select form-select-sm form-select-solid fw-semibold" data-bida-dashboard="fy-select"
            data-control="select2" data-hide-search="true" data-placeholder="Select fiscal year"
            aria-label="Select fiscal year">
            @foreach ($fiscalYears as $fy)
                <option value="{{ $fy }}" @selected($fy === $currentFy)>FY {{ $fy }}</option>
            @endforeach
        </select>
    </div>
    <span class="spinner-border spinner-border-sm text-primary d-none" data-bida-dashboard="fy-spinner"
        role="status"></span>
</div>
