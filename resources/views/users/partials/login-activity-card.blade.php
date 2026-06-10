@php
    $active = $active ?? false;
@endphp
<div class="tab-pane fade {{ $active ? 'show active' : '' }}" id="{{ $tabId }}" role="tabpanel">
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" data-{{ $searchAttr }}-filter="search"
                        class="form-control form-control-solid w-250px ps-13" placeholder="Search login activity..." />
                </div>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end gap-3">
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-filter fs-2"></i>Filter
                    </button>
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true"
                        id="{{ $menuId }}">
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                        </div>
                        <div class="separator border-gray-200"></div>
                        <div class="px-7 py-5" data-{{ $searchAttr }}-filter="form">
                            <div class="mb-5">
                                <label class="form-label fs-6 fw-semibold">Device:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="All Devices" data-allow-clear="true"
                                    data-{{ $searchAttr }}-filter="device" data-hide-search="true">
                                    <option></option>
                                    <option value="Desktop">Desktop</option>
                                    <option value="Mobile">Mobile</option>
                                    <option value="Tablet">Tablet</option>
                                </select>
                            </div>
                            <div class="row mb-5">
                                <div class="col-6">
                                    <label class="form-label fs-6 fw-semibold">From:</label>
                                    <input type="text"
                                        class="form-control form-control-solid fw-bold flatpickr-input"
                                        data-{{ $searchAttr }}-date="from" placeholder="Select date" readonly />
                                </div>
                                <div class="col-6">
                                    <label class="form-label fs-6 fw-semibold">To:</label>
                                    <input type="text"
                                        class="form-control form-control-solid fw-bold flatpickr-input"
                                        data-{{ $searchAttr }}-date="to" placeholder="Select date" readonly />
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="reset"
                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-{{ $searchAttr }}-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6"
                                    data-{{ $searchAttr }}-filter="filter">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body py-4">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ashik-table"
                id="{{ $tableId }}">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-50px">#</th>
                        <th class="min-w-125px">IP Address</th>
                        <th class="min-w-250px">User Agent</th>
                        <th class="w-100px">Device</th>
                        <th class="w-150px">Time</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">{{-- AJAX --}}</tbody>
            </table>
        </div>
    </div>
</div>
