{{--
    Renders the parameter inputs for the selected report.
    Each control carries data-report-param="{key}" so the JS can collect values
    generically. Select2 / Flatpickr are (re)initialised by bida-report.js after
    this partial is injected.

    $report  : the report definition (key, label, kind, ...)
    $params  : Collection of ['key','label','type','required','options'=>array|null]
    $currentFy, $defaultFrom, $defaultTo : sensible defaults
--}}
@if ($params->isEmpty())
    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4">
        <i class="ki-outline ki-information fs-2 text-primary me-3"></i>
        <div class="fs-7">This report takes no options. Click <span class="fw-semibold">Preview</span> or
            <span class="fw-semibold">Download</span> to run it.</div>
    </div>
@else
    <div class="row g-5">
        @foreach ($params as $param)
            @php
                $key      = $param['key'];
                $label    = $param['label'];
                $required = $param['required'] ?? false;
                $type     = $param['type'];
                $options  = $param['options'] ?? null;
            @endphp

            <div class="col-12">
                <label class="form-label fw-semibold {{ $required ? 'required' : '' }}">{{ $label }}</label>

                @switch($type)
                    {{-- Employee / generic option Select2 --}}
                    @case('employee')
                    @case('select')
                        <select class="form-select form-select-solid" data-report-param="{{ $key }}"
                            data-rpt-select2="true"
                            data-placeholder="{{ $required ? 'Select ' . strtolower($label) : 'All' }}"
                            {{ $required ? '' : 'data-allow-clear=true' }}>
                            @unless ($required)
                                <option value=""></option>
                            @else
                                <option value=""></option>
                            @endunless
                            @foreach ((array) $options as $value => $text)
                                <option value="{{ $value }}">{{ $text }}</option>
                            @endforeach
                        </select>
                    @break

                    {{-- Fiscal year (option list, defaults to current FY) --}}
                    @case('fiscal_year')
                        <select class="form-select form-select-solid" data-report-param="{{ $key }}"
                            data-rpt-select2="true" data-placeholder="{{ $required ? 'Select fiscal year' : 'All fiscal years' }}"
                            {{ $required ? '' : 'data-allow-clear=true' }}>
                            <option value=""></option>
                            @foreach ((array) $options as $value => $text)
                                <option value="{{ $value }}" {{ $value === $currentFy ? 'selected' : '' }}>{{ $text }}</option>
                            @endforeach
                        </select>
                    @break

                    {{-- Single date / as-of --}}
                    @case('date')
                        <input type="text" class="form-control form-control-solid" data-report-param="{{ $key }}"
                            data-rpt-flatpickr="true" placeholder="Pick a date"
                            value="{{ $defaultTo }}" />
                    @break

                    {{-- Date range bounds --}}
                    @case('date_from')
                        <input type="text" class="form-control form-control-solid" data-report-param="{{ $key }}"
                            data-rpt-flatpickr="true" placeholder="From date" value="{{ $defaultFrom }}" />
                    @break

                    @case('date_to')
                        <input type="text" class="form-control form-control-solid" data-report-param="{{ $key }}"
                            data-rpt-flatpickr="true" placeholder="To date" value="{{ $defaultTo }}" />
                    @break

                    @default
                        <input type="text" class="form-control form-control-solid" data-report-param="{{ $key }}"
                            placeholder="{{ $label }}" />
                @endswitch
            </div>
        @endforeach
    </div>
@endif
