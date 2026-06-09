"use strict";

// =========================================================================
// BidaAuditLogs
// Server-side (AJAX) DataTable for the global audit log (Spatie activity_log).
// Search, event / log-name / subject filtering, sorting and pagination are all
// resolved on the server (AuditLogController@data).
// =========================================================================
var BidaAuditLogs = (function () {
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
                  order: [[7, "desc"]], // default: newest "When" first
                  stateSave: false,
                  lengthMenu: [10, 25, 50, 100],
                  pageLength: 10,
                  // Length menu + record count bottom-left, pagination bottom-right.
                  dom:
                        "<'table-responsive'tr>" +
                        "<'row align-items-center mt-4'" +
                        "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start'" +
                        "<'me-4'l>i>" +
                        "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                  ajax: {
                        url: routeAuditLogsData,
                        data: function (d) {
                              d.event = filterVal("event");
                              d.log_name = filterVal("log_name");
                              d.subject_type = filterVal("subject_type");
                        }
                  },
                  columns: [
                        { data: null, name: "index", orderable: false, searchable: false },
                        { data: "description", name: "description" },
                        { data: "event", name: "event" },
                        { data: "log_name", name: "log_name" },
                        { data: "subject", name: "subject" },
                        { data: "changes", name: "changes", orderable: false, searchable: false },
                        { data: "causer", name: "causer" },
                        { data: "when", name: "when" },
                        { data: "actions", name: "actions", orderable: false, searchable: false }
                  ],
                  columnDefs: [
                        {
                              targets: 0,
                              render: function (data, type, row, meta) {
                                    return meta.row + meta.settings._iDisplayStart + 1;
                              }
                        },
                        { targets: 8, className: "text-end" } // Actions
                  ],
                  initComplete: function () {
                        // Ensure the length <select> picks up Metronic / Bootstrap styling.
                        $("#bida_audit_logs_table_length select")
                              .addClass("form-select form-select-sm form-select-solid");
                  }
            });
      };

      // Read a filter select's current value
      var filterVal = function (name) {
            var el = document.querySelector('[data-audit-logs-filter="' + name + '"]');
            return el ? el.value : "";
      };

      // Custom toolbar search --- official docs: https://datatables.net/reference/api/search()
      var handleSearch = function () {
            var filterSearch = document.querySelector('[data-audit-logs-filter="search"]');
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
            var filterForm = document.querySelector('[data-audit-logs-filter="form"]');
            if (!filterForm) {
                  return;
            }

            var filterButton = filterForm.querySelector('[data-audit-logs-filter="filter"]');
            var resetButton = filterForm.querySelector('[data-audit-logs-filter="reset"]');
            var selectOptions = filterForm.querySelectorAll("select");

            // Apply: reload datatable with current filter values (sent via ajax.data)
            filterButton.addEventListener("click", function () {
                  datatable.ajax.reload();
            });

            // Reset: clear the Select2 dropdowns then reload
            resetButton.addEventListener("click", function () {
                  selectOptions.forEach(function (item) {
                        // Reset Select2 --- official docs: https://select2.org/programmatic-control/add-select-clear-items
                        $(item).val(null).trigger("change.select2");
                  });
                  datatable.ajax.reload();
            });
      };

      return {
            // Public functions
            init: function () {
                  table = document.getElementById("bida_audit_logs_table");

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  handleSearch();
                  handleFilter();
            }
      };
})();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      BidaAuditLogs.init();
});