"use strict";

// =========================================================================
// BidaEmployeeList
// Server-side (AJAX) DataTable for the employee list. Search, grade /
// activation / service-status filtering, sorting, pagination and the
// filter-aware Excel / CSV / PDF exports are all resolved on the server
// (EmployeeController@data and @export).
//
// Mirrors the salary-history module: custom Metronic toolbar search,
// Select2 filter dropdowns applied on Apply/Reset, length menu + record
// count bottom-left, pagination bottom-right.
// =========================================================================
var BidaEmployeeList = (function () {
    // Shared variables
    var table;
    var datatable;
    var searchTimer = null;

    // Init server-side datatable --- more info: https://datatables.net/manual/server-side
    var initDatatable = function () {
        datatable = $(table).DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 500,
            order: [[2, "asc"]], // default: Employee Name A→Z
            stateSave: false,
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            autoWidth: false,
            // Length menu + record count bottom-left, pagination bottom-right.
            // (The built-in search box is omitted — we use the toolbar one.)
            dom:
                "<'table-responsive'tr>" +
                "<'row align-items-center mt-4'" +
                "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start'" +
                "<'me-4'l>i>" +
                "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
            ajax: {
                url: routeEmployeesData,
                data: function (d) {
                    d.grade = filterVal("grade");
                    d.active_status = filterVal("active_status");
                    d.service_status = filterVal("service_status");
                }
            },
            columns: [
                { data: "DT_RowIndex", name: "index", orderable: false, searchable: false },
                { data: "account", name: "cpf_account_no" },
                { data: "name", name: "name" },
                { data: "designation", name: "designation" },
                { data: "mobile", name: "mobile_number" },
                { data: "joining_date", name: "joining_date" },
                { data: "grade", name: "grade" },
                { data: "basic_salary", name: "basic_salary" },
                { data: "balance", name: "balance" },
                { data: "status", name: "status", orderable: false },
                { data: "actions", name: "actions", orderable: false, searchable: false }
            ],
            columnDefs: [
                { targets: 7, className: "text-end" }, // Basic salary
                { targets: 8, className: "text-end" }, // Current balance
                { targets: 10, className: "text-end" } // Actions
            ],
            initComplete: function () {
                $("#bida_employee_table_length select")
                    .addClass("form-select form-select-sm form-select-solid");
            }
        });
    };

    // Read a filter select's current value
    var filterVal = function (name) {
        var el = document.querySelector('[data-employees-table-filter="' + name + '"]');
        return el ? el.value : "";
    };

    // Custom toolbar search --- official docs: https://datatables.net/reference/api/search()
    var handleSearch = function () {
        var filterSearch = document.querySelector('[data-employees-table-filter="search"]');
        if (!filterSearch) {
            return;
        }

        filterSearch.addEventListener("keyup", function (e) {
            var value = e.target.value;
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                datatable.search(value).draw();
            }, 400);
        });
    };

    // Filter dropdown (Apply / Reset)
    var handleFilter = function () {
        var filterForm = document.querySelector('[data-employees-table-filter="form"]');
        if (!filterForm) {
            return;
        }

        var filterButton = filterForm.querySelector('[data-employees-table-filter="filter"]');
        var resetButton = filterForm.querySelector('[data-employees-table-filter="reset"]');

        // Apply: reload datatable with current filter values (sent via ajax.data)
        filterButton.addEventListener("click", function () {
            datatable.ajax.reload();
        });

        // Reset: clear every filter (Select2-aware) then reload
        resetButton.addEventListener("click", function () {
            ["grade", "active_status", "service_status"].forEach(function (name) {
                var $el = $('[data-employees-table-filter="' + name + '"]');
                if ($el.length) {
                    $el.val(null).trigger("change.select2");
                }
            });
            datatable.ajax.reload();
        });
    };

    // Filter-aware exports — build a query string from the current toolbar
    // search + applied filters and hit EmployeeController@export.
    var handleExport = function () {
        var items = document.querySelectorAll('#kt_employee_export_menu [data-row-export]');

        items.forEach(function (item) {
            item.addEventListener("click", function (e) {
                e.preventDefault();

                var format = this.getAttribute("data-row-export"); // xlsx | csv | pdf
                var params = new URLSearchParams();
                params.set("format", format);

                var term = datatable ? datatable.search() : "";
                if (term) {
                    params.set("search", term);
                }

                ["grade", "active_status", "service_status"].forEach(function (name) {
                    var val = filterVal(name);
                    if (val) {
                        params.set(name, val);
                    }
                });

                window.location.href = routeEmployeesExport + "?" + params.toString();
            });
        });
    };

    return {
        // Public functions
        init: function () {
            table = document.getElementById("bida_employee_table");

            if (!table) {
                return;
            }

            initDatatable();
            handleSearch();
            handleFilter();
            handleExport();
        }
    };
})();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    BidaEmployeeList.init();
});