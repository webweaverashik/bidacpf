"use strict";

// =========================================================================
// BidaEmployeeList
// Two independent server-side (AJAX) DataTables, one per tab:
//
//   • Active Service  (#bida_employee_active_table, tab=active)
//       status column → activation (is_active): Active / Inactive
//       filters: grade, active_status
//
//   • Others          (#bida_employee_others_table, tab=others)
//       status column → exact service status: Retired / Resigned / Deceased
//       filters: grade, service_status
//
// Each card is fully self-contained: its own toolbar search, Select2 filter
// dropdown (Apply / Reset), filter-aware Excel/CSV/PDF export, sorting,
// length menu + record count bottom-left, pagination bottom-right.
//
// Search / filter / export controls are scoped to each card root via
// data-emp-card / data-emp-filter / data-row-export so the two tables never
// collide. The tab parameter is sent to EmployeeController@data / @export.
// =========================================================================
var BidaEmployeeList = (function () {
    // Built DataTable instances, keyed by tab.
    var tables = {};

    // Shared DataTable DOM: length menu + record count bottom-left,
    // pagination bottom-right. (Built-in search box omitted — toolbar one used.)
    var TABLE_DOM =
        "<'table-responsive'tr>" +
        "<'row align-items-center mt-4'" +
        "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start'" +
        "<'me-4'l>i>" +
        "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-end'p>>";

    // Read a scoped filter control's current value.
    var filterVal = function (root, name) {
        var el = root.querySelector('[data-emp-filter="' + name + '"]');
        return el ? el.value : "";
    };

    // Build one server-side DataTable for a tab. opts = { root, tableId, tab, filters }.
    var createTable = function (opts) {
        var table = document.getElementById(opts.tableId);
        if (!table) {
            return null;
        }

        var datatable = $(table).DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 500,
            order: [[2, "asc"]], // default: Employee Name A→Z
            stateSave: false,
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            autoWidth: false,
            dom: TABLE_DOM,
            ajax: {
                url: routeEmployeesData,
                data: function (d) {
                    d.tab = opts.tab;
                    opts.filters.forEach(function (name) {
                        d[name] = filterVal(opts.root, name);
                    });
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
                { data: "status", name: "status" },
                { data: "actions", name: "actions", orderable: false, searchable: false }
            ],
            columnDefs: [
                { targets: 7, className: "text-end" }, // Basic salary
                { targets: 8, className: "text-end" }, // Current balance
                { targets: 10, className: "text-end" } // Actions
            ],
            initComplete: function () {
                $("#" + opts.tableId + "_length select")
                    .addClass("form-select form-select-sm form-select-solid");
            }
        });

        handleSearch(opts.root, datatable);
        handleFilter(opts.root, datatable, opts.filters);
        handleExport(opts.root, datatable, opts);

        return datatable;
    };

    // Custom toolbar search (scoped to the card).
    var handleSearch = function (root, datatable) {
        var input = root.querySelector('[data-emp-filter="search"]');
        if (!input) {
            return;
        }

        var timer = null;
        input.addEventListener("keyup", function (e) {
            var value = e.target.value;
            clearTimeout(timer);
            timer = setTimeout(function () {
                datatable.search(value).draw();
            }, 400);
        });
    };

    // Filter dropdown — Apply / Reset (scoped to the card, Select2-aware).
    var handleFilter = function (root, datatable, filters) {
        var form = root.querySelector('[data-emp-filter="form"]');
        if (!form) {
            return;
        }

        var applyButton = form.querySelector('[data-emp-filter="apply"]');
        var resetButton = form.querySelector('[data-emp-filter="reset"]');

        if (applyButton) {
            applyButton.addEventListener("click", function () {
                datatable.ajax.reload();
            });
        }

        if (resetButton) {
            resetButton.addEventListener("click", function () {
                filters.forEach(function (name) {
                    var $el = $(root).find('[data-emp-filter="' + name + '"]');
                    if ($el.length) {
                        $el.val(null).trigger("change.select2");
                    }
                });
                datatable.ajax.reload();
            });
        }
    };

    // Filter-aware exports — current search + applied filters + tab → @export.
    var handleExport = function (root, datatable, opts) {
        var items = root.querySelectorAll("[data-row-export]");

        items.forEach(function (item) {
            item.addEventListener("click", function (e) {
                e.preventDefault();

                var format = this.getAttribute("data-row-export"); // xlsx | csv | pdf
                var params = new URLSearchParams();
                params.set("format", format);
                params.set("tab", opts.tab);

                var term = datatable ? datatable.search() : "";
                if (term) {
                    params.set("search", term);
                }

                opts.filters.forEach(function (name) {
                    var val = filterVal(root, name);
                    if (val) {
                        params.set(name, val);
                    }
                });

                window.location.href = routeEmployeesExport + "?" + params.toString();
            });
        });
    };

    // Re-measure column widths when a tab is shown (DataTables initialised in a
    // hidden pane otherwise mis-sizes its columns).
    var handleTabAdjust = function () {
        var links = document.querySelectorAll('#kt_employee_tabs [data-bs-toggle="tab"]');
        links.forEach(function (link) {
            link.addEventListener("shown.bs.tab", function () {
                Object.keys(tables).forEach(function (key) {
                    if (tables[key]) {
                        tables[key].columns.adjust();
                    }
                });
            });
        });
    };

    return {
        // Public functions
        init: function () {
            var activeRoot = document.querySelector('[data-emp-card="active"]');
            var othersRoot = document.querySelector('[data-emp-card="others"]');

            if (activeRoot) {
                tables.active = createTable({
                    root: activeRoot,
                    tableId: "bida_employee_active_table",
                    tab: "active",
                    filters: ["grade", "active_status"]
                });
            }

            if (othersRoot) {
                tables.others = createTable({
                    root: othersRoot,
                    tableId: "bida_employee_others_table",
                    tab: "others",
                    filters: ["grade", "service_status"]
                });
            }

            handleTabAdjust();
        }
    };
})();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    BidaEmployeeList.init();
});