"use strict";

// =========================================================================
// BidaEmployeeShow
// AJAX activate/deactivate (with consequence warning), balance-gated delete,
// DataTables (search + sort) on every table with a display-order serial
// column, Select2 ledger month/year filter, server-side AJAX activity log.
// =========================================================================
var BidaEmployeeShow = (function () {
      var ledgerTable = null;

      const root = () => document.getElementById("kt_employee_show");
      const csrf = () =>
            document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
      const hasDT = () => typeof $ !== "undefined" && $.fn && $.fn.DataTable;

      // ---- compact the DataTables length (page-size) select ----------------
      // Mirrors the employee index page: query the well-known #<tableId>_length
      // wrapper, add the Metronic solid styling, and force width:auto inline so
      // the select sizes to its content instead of filling the whole column.
      const styleLength = function (tableId) {
            if (!tableId) return;
            const sel = document.querySelector("#" + tableId + "_length select");
            if (!sel) return;
            sel.classList.add("form-select", "form-select-sm", "form-select-solid");
            sel.style.width = "auto";
            sel.style.minWidth = "80px";
            sel.style.display = "inline-block";
      };

      // ---- feedback helpers --------------------------------------------------
      const notifySuccess = (msg) => {
            if (typeof toastr !== "undefined") toastr.success(msg);
            else if (typeof Swal !== "undefined")
                  Swal.fire({ icon: "success", title: msg, timer: 1300, showConfirmButton: false });
      };
      const notifyError = (msg) => {
            if (typeof toastr !== "undefined") toastr.error(msg);
            else if (typeof Swal !== "undefined") Swal.fire({ icon: "error", title: msg });
            else alert(msg);
      };

      // ---- reflect new activation state in the UI ---------------------------
      const applyActiveState = function (isActive) {
            root().setAttribute("data-is-active", isActive ? "1" : "0");

            const badge = document.querySelector('[data-kt-employee-badge="active"]');
            if (badge) {
                  badge.textContent = isActive ? "Active" : "Inactive";
                  badge.classList.remove("badge-light-success", "badge-light-danger");
                  badge.classList.add(isActive ? "badge-light-success" : "badge-light-danger");
            }
            const label = document.querySelector('[data-kt-employee-label="toggle"]');
            if (label) label.textContent = isActive ? "Deactivate" : "Activate";

            const icon = document.querySelector('[data-kt-employee-icon="toggle"]');
            if (icon) {
                  icon.classList.remove("bi-person-slash", "bi-person-check");
                  icon.classList.add(isActive ? "bi-person-slash" : "bi-person-check");
            }
            const card = root().querySelector(".card-flush");
            if (card) {
                  card.classList.toggle("border", !isActive);
                  card.classList.toggle("border-dashed", !isActive);
                  card.classList.toggle("border-danger", !isActive);
            }
      };

      // ---- AJAX activate / deactivate (with consequence warning) ------------
      const initToggleActive = function () {
            const btn = document.querySelector('[data-kt-employee-action="toggle-active"]');
            if (!btn) return;

            btn.addEventListener("click", function (e) {
                  e.preventDefault();
                  const url = root().getAttribute("data-toggle-url");
                  const employeeId = root().getAttribute("data-employee-id");
                  const isActive = root().getAttribute("data-is-active") === "1";
                  if (!url || !employeeId) return;

                  const doToggle = function () {
                        fetch(url, {
                              method: "POST",
                              headers: {
                                    "X-CSRF-TOKEN": csrf(),
                                    "Content-Type": "application/json",
                                    Accept: "application/json",
                                    "X-Requested-With": "XMLHttpRequest",
                              },
                              body: JSON.stringify({ employee_id: employeeId }),
                        })
                              .then((r) => r.json())
                              .then((data) => {
                                    if (data && data.success) {
                                          applyActiveState(!!data.is_active);
                                          notifySuccess(
                                                data.is_active ? "Employee activated." : "Employee deactivated."
                                          );
                                    } else {
                                          notifyError(data && data.message ? data.message : "Could not update activation status.");
                                    }
                              })
                              .catch(() => notifyError("Something went wrong. Please try again."));
                  };

                  const title = isActive ? "Deactivate this employee?" : "Activate this employee?";
                  const html = isActive
                        ? "While inactive, this employee will be <b>excluded from CPF contribution batches</b> — no monthly employee or government contributions will be calculated for them. Existing ledger entries are not affected."
                        : "This employee will be <b>included in CPF contribution batches</b> again, and monthly contributions will resume from the next batch.";

                  if (typeof Swal !== "undefined") {
                        Swal.fire({
                              title: title,
                              html: html,
                              icon: "warning",
                              showCancelButton: true,
                              confirmButtonText: isActive ? "Yes, deactivate" : "Yes, activate",
                              cancelButtonText: "Cancel",
                              confirmButtonColor: isActive ? "#f1416c" : "#50cd89",
                        }).then((r) => {
                              if (r.isConfirmed) doToggle();
                        });
                  } else {
                        const msg = isActive
                              ? "Deactivating excludes this employee from CPF contribution batches. Continue?"
                              : "Activating includes this employee in CPF contribution batches. Continue?";
                        if (confirm(msg)) doToggle();
                  }
            });
      };

      // ---- delete (only proceeds when current balance is zero) --------------
      const initDelete = function () {
            const btn = document.querySelector('[data-kt-employee-action="delete"]');
            if (!btn) return;

            btn.addEventListener("click", function (e) {
                  e.preventDefault();

                  if (root().getAttribute("data-can-delete") !== "1") {
                        notifyError("This employee cannot be deleted while the CPF balance is non-zero.");
                        return;
                  }
                  const url = root().getAttribute("data-destroy-url");
                  if (!url) return;

                  const submitDelete = function () {
                        const form = document.createElement("form");
                        form.method = "POST";
                        form.action = url;
                        form.innerHTML =
                              '<input type="hidden" name="_method" value="DELETE">' +
                              '<input type="hidden" name="_token" value="' + csrf() + '">';
                        document.body.appendChild(form);
                        form.submit();
                  };

                  if (typeof Swal !== "undefined") {
                        Swal.fire({
                              title: "Delete this employee?",
                              html: "This permanently removes the employee record. This action <b>cannot be undone</b>.",
                              icon: "warning",
                              showCancelButton: true,
                              confirmButtonText: "Yes, delete",
                              cancelButtonText: "Cancel",
                              confirmButtonColor: "#f1416c",
                        }).then((r) => {
                              if (r.isConfirmed) submitDelete();
                        });
                  } else if (confirm("Delete this employee? This action cannot be undone.")) {
                        submitDelete();
                  }
            });
      };

      // ---- renumber the first (serial) column in current display order ------
      // Keeps the # column reading 1,2,3… regardless of sort/search/paging.
      const applySerial = function (table) {
            if (!table) return;
            const renumber = function () {
                  let n = 1;
                  table
                        .column(0, { search: "applied", order: "applied" })
                        .nodes()
                        .each(function (cell) {
                              cell.innerHTML = n++;
                        });
            };
            table.on("draw.dt", renumber);
            renumber();
      };

      // ---- DataTables DOM layout —————————————————————————————————————————
      // Matches the employee index page: table, then a bottom bar with the
      // length menu + record count on the left and pagination on the right.
      // The built-in search box is omitted; each table uses a custom Metronic
      // search input wired up separately.
      const BOTTOM_DOM =
            "<'table-responsive'tr>" +
            "<'row align-items-center mt-4'" +
            "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start'" +
            "<'me-4'l>i>" +
            "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-end'p>>";

      // ---- generic DataTable initialiser ------------------------------------
      const baseTable = function (sel, opts) {
            if (!hasDT()) return null;
            const el = document.querySelector(sel);
            if (!el || $.fn.DataTable.isDataTable(sel)) return null;

            const dt = $(sel).DataTable(
                  Object.assign(
                        {
                              lengthChange: true,
                              pageLength: 10,
                              lengthMenu: [10, 25, 50, 100],
                              autoWidth: false,
                              order: [],
                              columnDefs: [{ orderable: false, targets: 0 }],
                              language: { search: "", searchPlaceholder: "Search..." },
                        },
                        opts || {}
                  )
            );

            styleLength(el.id);
            return dt;
      };

      const initStaticTables = function () {
            applySerial(baseTable("#kt_employee_contributions_table", { order: [[1, "desc"]] }));
            applySerial(baseTable("#kt_employee_interest_table", { order: [[1, "desc"]] }));
            applySerial(baseTable("#kt_employee_salary_table", { order: [[1, "desc"]] }));
      };

      // ---- ledger table + Metronic search / Filter dropdown ----------------
      const initLedgerTable = function () {
            // order:[] preserves the backend order (created_at desc) sent in the DOM.
            // BOTTOM_DOM keeps the length menu bottom-left while dropping the
            // built-in search box (we use a custom Metronic input instead).
            ledgerTable = baseTable("#kt_employee_ledger_table", {
                  order: [],
                  scrollX: true,
                  dom: BOTTOM_DOM,
            });
            if (!ledgerTable) return;
            applySerial(ledgerTable);

            const fySel = document.getElementById("kt_ledger_filter_fy");
            const typeSel = document.getElementById("kt_ledger_filter_type");
            const monthSel = document.getElementById("kt_ledger_filter_month");
            const menu = $("#kt_ledger_filter_menu");

            // Select2 — dropdownParent set to the filter menu so opening it does
            // not close the Metronic menu.
            if ($.fn.select2) {
                  [fySel, typeSel, monthSel].forEach(function (el) {
                        if (el) {
                              $(el).select2({
                                    minimumResultsForSearch: Infinity,
                                    dropdownParent: menu,
                                    width: "100%",
                              });
                        }
                  });
            }

            // applied filter state — only changes on Apply / Reset
            var applied = { fy: "", type: "", month: "" };

            // scoped custom filter (only affects the ledger table)
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                  if (settings.nTable.id !== "kt_employee_ledger_table") return true;
                  if (!applied.fy && !applied.type && !applied.month) return true;

                  const node = ledgerTable.row(dataIndex).node();
                  if (!node) return false;
                  if (applied.fy && node.getAttribute("data-fy") !== applied.fy) return false;
                  if (applied.type && node.getAttribute("data-type") !== applied.type) return false;
                  if (applied.month && node.getAttribute("data-month") !== applied.month) return false;
                  return true;
            });

            // keep the PDF/Excel export links in sync with applied filters + search
            const updateExportLinks = function () {
                  const params = new URLSearchParams();
                  if (applied.fy) params.set("fiscal_year", applied.fy);
                  if (applied.type) params.set("type", applied.type);
                  if (applied.month) params.set("month", applied.month);
                  const term = ledgerTable.search();
                  if (term) params.set("search", term);
                  const qs = params.toString() ? "?" + params.toString() : "";
                  document.querySelectorAll("[data-kt-ledger-export]").forEach(function (a) {
                        a.href = a.getAttribute("data-base-url") + qs;
                  });
            };
            ledgerTable.on("draw.dt", updateExportLinks);
            updateExportLinks();

            // custom Metronic search box
            const searchInput = document.querySelector('[data-kt-ledger-filter="search"]');
            if (searchInput) {
                  searchInput.addEventListener("keyup", function () {
                        ledgerTable.search(this.value).draw();
                  });
            }

            // Apply / Reset
            const applyBtn = document.querySelector('[data-kt-ledger-filter="filter"]');
            const resetBtn = document.querySelector('[data-kt-ledger-filter="reset"]');

            if (applyBtn) {
                  applyBtn.addEventListener("click", function () {
                        applied = {
                              fy: fySel ? fySel.value : "",
                              type: typeSel ? typeSel.value : "",
                              month: monthSel ? monthSel.value : "",
                        };
                        ledgerTable.draw();
                  });
            }

            if (resetBtn) {
                  resetBtn.addEventListener("click", function () {
                        applied = { fy: "", type: "", month: "" };
                        [fySel, typeSel, monthSel].forEach(function (el) {
                              if (!el) return;
                              el.value = "";
                              if ($.fn.select2) $(el).val("").trigger("change");
                        });
                        ledgerTable.draw();
                  });
            }
      };

      // ---- activity log (server-side AJAX, with search + filter) ------------
      const initActivityTable = function () {
            if (!hasDT()) return;
            const url = root().getAttribute("data-activities-url");
            if (!url || $.fn.DataTable.isDataTable("#kt_employee_activity_table")) return;

            const txt = $.fn.dataTable.render.text();
            const eventBadge = function (event) {
                  const map = { created: "success", updated: "info", deleted: "danger" };
                  const cls = map[(event || "").toLowerCase()] || "secondary";
                  const label = event ? event.charAt(0).toUpperCase() + event.slice(1) : "-";
                  return '<span class="badge badge-light-' + cls + '">' + label + "</span>";
            };

            // applied filter state, sent to the server on each request
            var activityApplied = { event: "", subject: "" };

            const table = $("#kt_employee_activity_table").DataTable({
                  processing: true,
                  serverSide: true,
                  scrollX: true,
                  autoWidth: false,
                  lengthChange: true,
                  pageLength: 10,
                  lengthMenu: [10, 25, 50, 100],
                  order: [[6, "desc"]],
                  // BOTTOM_DOM: length menu bottom-left, built-in search box removed
                  // (custom Metronic search input is wired up below instead).
                  dom: BOTTOM_DOM,
                  language: { processing: "Loading..." },
                  ajax: {
                        url: url,
                        type: "GET",
                        data: function (d) {
                              d.event = activityApplied.event;
                              d.subject = activityApplied.subject;
                        },
                  },
                  columns: [
                        {
                              data: null, orderable: false, searchable: false,
                              render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1,
                        },
                        { data: "description", render: txt },
                        { data: "event", render: (d) => eventBadge(d) },
                        { data: "subject", render: txt },
                        { data: "changes", orderable: false, searchable: false },
                        { data: "causer", orderable: false, render: txt },
                        {
                              data: "when",
                              render: (d, t, r) =>
                                    '<span data-order="' + (r.when_ts || 0) + '">' + (d || "") +
                                    ' <span class="ms-1" data-bs-toggle="tooltip" title="' +
                                    (r.when_exact || "") +
                                    '"><i class="ki-outline ki-information-5 text-gray-500 fs-6"></i></span></span>',
                        },
                  ],
                  drawCallback: function () {
                        if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
                              this.api()
                                    .table()
                                    .container()
                                    .querySelectorAll('[data-bs-toggle="tooltip"]')
                                    .forEach((el) => {
                                          if (!bootstrap.Tooltip.getInstance(el)) new bootstrap.Tooltip(el);
                                    });
                        }
                  },
            });

            // Compact the length select once the controls are rendered.
            styleLength("kt_employee_activity_table");

            // filter dropdown selects (Select2 kept inside the menu)
            const eventSel = document.getElementById("kt_activity_filter_event");
            const subjectSel = document.getElementById("kt_activity_filter_subject");
            const menu = $("#kt_activity_filter_menu");
            if ($.fn.select2) {
                  [eventSel, subjectSel].forEach(function (el) {
                        if (el) $(el).select2({ minimumResultsForSearch: Infinity, dropdownParent: menu, width: "100%" });
                  });
            }

            // custom Metronic search box → server-side search
            const searchInput = document.querySelector('[data-kt-activity-filter="search"]');
            if (searchInput) {
                  searchInput.addEventListener("keyup", function () {
                        table.search(this.value).draw();
                  });
            }

            // Apply / Reset
            const applyBtn = document.querySelector('[data-kt-activity-filter="filter"]');
            const resetBtn = document.querySelector('[data-kt-activity-filter="reset"]');
            if (applyBtn) {
                  applyBtn.addEventListener("click", function () {
                        activityApplied = {
                              event: eventSel ? eventSel.value : "",
                              subject: subjectSel ? subjectSel.value : "",
                        };
                        table.ajax.reload();
                  });
            }
            if (resetBtn) {
                  resetBtn.addEventListener("click", function () {
                        activityApplied = { event: "", subject: "" };
                        [eventSel, subjectSel].forEach(function (el) {
                              if (!el) return;
                              el.value = "";
                              if ($.fn.select2) $(el).val("").trigger("change");
                        });
                        table.ajax.reload();
                  });
            }
      };

      // ---- recalc widths when a tab becomes visible -------------------------
      const initTabAdjust = function () {
            if (!hasDT()) return;
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach((tab) => {
                  tab.addEventListener("shown.bs.tab", function () {
                        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                  });
            });
      };

      return {
            init: function () {
                  if (!root()) return;
                  initToggleActive();
                  initDelete();
                  initLedgerTable();
                  initStaticTables();
                  initActivityTable();
                  initTabAdjust();
            },
      };
})();

KTUtil.onDOMContentLoaded(function () {
      BidaEmployeeShow.init();
});