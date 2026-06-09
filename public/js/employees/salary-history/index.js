"use strict";

// =========================================================================
// BidaEmployeeSalaryHistory
// Server-side (AJAX) DataTable for the employee salary history list.
// Search, change-type / pay-scale / grade / basic-salary filtering, sorting
// and pagination are all resolved on the server (EmployeeSalaryController@data).
//
// The filter cascades: Pay Scale -> (AJAX) Grade -> (AJAX) Basic Salary.
// =========================================================================
var BidaEmployeeSalaryHistory = (function () {
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
                  order: [[10, "desc"]], // default: newest "Created At" first
                  stateSave: false,
                  lengthMenu: [10, 25, 50, 100],
                  pageLength: 10,
                  // Length menu + record count bottom-left, pagination bottom-right.
                  // (The built-in search box is omitted — we use the toolbar one.)
                  dom:
                        "<'table-responsive'tr>" +
                        "<'row align-items-center mt-4'" +
                        "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start'" +
                        "<'me-4'l>i>" +
                        "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                  ajax: {
                        url: routeSalaryHistoryData,
                        data: function (d) {
                              d.change_type = filterVal("change_type");
                              d.pay_scale_id = filterVal("pay_scale_id");
                              d.grade = filterVal("grade");
                              d.pay_scale_step_id = filterVal("pay_scale_step_id");
                        }
                  },
                  columns: [
                        { data: null, name: "index", orderable: false, searchable: false },
                        { data: "employee", name: "employee" },
                        { data: "designation", name: "designation" },
                        { data: "pay_scale", name: "pay_scale" },
                        { data: "grade_step", name: "grade_step" },
                        { data: "basic_salary", name: "basic_salary" },
                        { data: "effective_date", name: "effective_date" },
                        { data: "change_type", name: "change_type" },
                        { data: "remarks", name: "remarks" },
                        { data: "created_by", name: "created_by" },
                        { data: "created_at", name: "created_at" }
                  ],
                  columnDefs: [
                        {
                              targets: 0,
                              render: function (data, type, row, meta) {
                                    return meta.row + meta.settings._iDisplayStart + 1;
                              }
                        },
                        { targets: 5, className: "text-end" } // Basic salary
                  ],
                  initComplete: function () {
                        // Ensure the length <select> picks up Metronic / Bootstrap styling.
                        $("#bida_salary_history_table_length select")
                              .addClass("form-select form-select-sm form-select-solid");
                  }
            });
      };

      // Read a filter select's current value
      var filterVal = function (name) {
            var el = document.querySelector('[data-salary-history-filter="' + name + '"]');
            return el ? el.value : "";
      };

      // Custom toolbar search --- official docs: https://datatables.net/reference/api/search()
      var handleSearch = function () {
            var filterSearch = document.querySelector('[data-salary-history-filter="search"]');
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

      // Reset a Select2 <select> back to an empty placeholder-only state.
      // change.select2 updates the widget UI WITHOUT firing the change handlers
      // (so resetting the parent does not accidentally cascade).
      var clearSelect = function ($el, enabled) {
            $el.empty().append(new Option("", "")); // placeholder option
            $el.prop("disabled", !enabled);
            $el.val(null).trigger("change.select2");
      };

      // Cascading dependent dropdowns. Select2 events are jQuery-bound, so we
      // must listen via $(el).on('change', ...) rather than addEventListener.
      var handleCascade = function () {
            var $payScale = $('[data-salary-history-filter="pay_scale_id"]');
            var $grade = $('[data-salary-history-filter="grade"]');
            var $step = $('[data-salary-history-filter="pay_scale_step_id"]');

            // Pay Scale -> load grades
            $payScale.on("change", function () {
                  var payScaleId = $(this).val();

                  clearSelect($grade, false);
                  clearSelect($step, false);

                  if (!payScaleId) {
                        return;
                  }

                  $.getJSON(routeSalaryHistoryGrades, { pay_scale_id: payScaleId })
                        .done(function (res) {
                              (res.grades || []).forEach(function (g) {
                                    $grade.append(new Option("Grade " + g, g, false, false));
                              });
                              $grade.prop("disabled", false).trigger("change.select2");
                        });
            });

            // Grade -> load steps (basic salaries)
            $grade.on("change", function () {
                  var grade = $(this).val();
                  var payScaleId = $payScale.val();

                  clearSelect($step, false);

                  if (!grade || !payScaleId) {
                        return;
                  }

                  $.getJSON(routeSalaryHistorySteps, { pay_scale_id: payScaleId, grade: grade })
                        .done(function (res) {
                              (res.steps || []).forEach(function (s) {
                                    var label = "৳" + Number(s.basic_salary).toLocaleString() + " (Step " + s.step + ")";
                                    $step.append(new Option(label, s.id, false, false));
                              });
                              $step.prop("disabled", false).trigger("change.select2");
                        });
            });
      };

      // Filter dropdown (Apply / Reset)
      var handleFilter = function () {
            var filterForm = document.querySelector('[data-salary-history-filter="form"]');
            if (!filterForm) {
                  return;
            }

            var filterButton = filterForm.querySelector('[data-salary-history-filter="filter"]');
            var resetButton = filterForm.querySelector('[data-salary-history-filter="reset"]');

            // Apply: reload datatable with current filter values (sent via ajax.data)
            filterButton.addEventListener("click", function () {
                  datatable.ajax.reload();
            });

            // Reset: clear every filter (and the dependent dropdowns) then reload
            resetButton.addEventListener("click", function () {
                  $('[data-salary-history-filter="change_type"]').val(null).trigger("change.select2");
                  $('[data-salary-history-filter="pay_scale_id"]').val(null).trigger("change.select2");
                  clearSelect($('[data-salary-history-filter="grade"]'), false);
                  clearSelect($('[data-salary-history-filter="pay_scale_step_id"]'), false);
                  datatable.ajax.reload();
            });
      };

      return {
            // Public functions
            init: function () {
                  table = document.getElementById("bida_salary_history_table");

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  handleSearch();
                  handleCascade();
                  handleFilter();
            }
      };
})();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      BidaEmployeeSalaryHistory.init();
});