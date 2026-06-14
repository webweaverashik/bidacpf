"use strict";

// BIDA CPF — Scheduled task run log (server-side DataTable + detail modal).
var BidaScheduledTasks = (function () {
    var cfg = window.BidaScheduledTasksConfig || {};

    var table = null;
    var modal = null;
    var filters = { status: "", command: "", date_from: "", date_to: "" };

    function esc(s) {
        return String(s == null ? "" : s)
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    function initTable() {
        table = $("#tasks_table").DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 400,
            order: [[5, "desc"]],
            ajax: {
                url: cfg.dataUrl,
                data: function (d) {
                    d.status = filters.status;
                    d.command = filters.command;
                    d.date_from = filters.date_from;
                    d.date_to = filters.date_to;
                },
            },
            columns: [
                {
                    data: null, orderable: false, render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { data: "task" },
                { data: "status" },
                { data: "exit_code", className: "text-center" },
                { data: "runtime" },
                { data: "started" },
                { data: "finished" },
                { data: "actions", orderable: false, className: "text-end" },
            ],
            // Length + info bottom-left, pagination bottom-right (house style).
            dom:
                "<'row'<'col-sm-12'tr>>" +
                "<'row mt-3'<'col-sm-6 d-flex align-items-center'li><'col-sm-6 d-flex justify-content-end'p>>",
            language: {
                emptyTable: "No scheduled task runs recorded yet.",
                processing: '<span class="spinner-border spinner-border-sm align-middle"></span> Loading…',
            },
        });
    }

    function applyFilters() {
        filters.status = $("#filter_status").val() || "";
        filters.command = $("#filter_command").val() || "";
        filters.date_from = $("#filter_from").val() || "";
        filters.date_to = $("#filter_to").val() || "";
        table.ajax.reload();
    }

    function resetFilters() {
        $("#filter_status").val("").trigger("change");
        $("#filter_command").val("").trigger("change");
        var from = document.getElementById("filter_from");
        var to = document.getElementById("filter_to");
        if (from._flatpickr) from._flatpickr.clear(); else from.value = "";
        if (to._flatpickr) to._flatpickr.clear(); else to.value = "";
        filters = { status: "", command: "", date_from: "", date_to: "" };
        table.ajax.reload();
    }

    function showDetails(id) {
        fetch(cfg.showUrl.replace(":id", id), {
            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) { if (window.toastr) toastr.error("Could not load details."); return; }
                var l = res.log;
                document.getElementById("d_task").textContent = l.task || "—";
                document.getElementById("d_command").textContent = l.command || "—";
                document.getElementById("d_status").innerHTML =
                    '<span class="badge text-capitalize ' + statusBadge(l.status) + '">' + esc(l.status) + "</span>";
                document.getElementById("d_description").textContent = l.description || "—";
                document.getElementById("d_description_row").style.display = l.description ? "" : "none";
                document.getElementById("d_exit").textContent = (l.exit_code === null || l.exit_code === undefined) ? "—" : l.exit_code;
                document.getElementById("d_runtime").textContent = l.runtime || "—";
                document.getElementById("d_started").textContent = l.started_at || "—";
                document.getElementById("d_finished").textContent = l.finished_at || "—";
                document.getElementById("d_output").textContent = l.output || "— (no output captured)";
                modal.show();
            })
            .catch(function () { if (window.toastr) toastr.error("Network error."); });
    }

    function statusBadge(status) {
        switch (status) {
            case "completed": return "badge-light-success";
            case "failed": return "badge-light-danger";
            case "skipped": return "badge-light-warning";
            default: return "badge-light-primary";
        }
    }

    return {
        init: function () {
            if (!document.getElementById("tasks_table")) return;
            modal = new bootstrap.Modal(document.getElementById("task_modal"));

            initTable();

            // Date pickers in the filter dropdown.
            if (window.flatpickr) {
                document.querySelectorAll(".js-fp").forEach(function (el) {
                    flatpickr(el, { dateFormat: "Y-m-d", allowInput: true });
                });
            }

            // Global search box → DataTable search.
            var searchEl = document.getElementById("task_search");
            if (searchEl) {
                searchEl.addEventListener("keyup", function () { table.search(this.value).draw(); });
            }

            document.getElementById("filter_apply").addEventListener("click", applyFilters);
            document.getElementById("filter_reset").addEventListener("click", resetFilters);

            // Delegated details button.
            document.querySelector("#tasks_table tbody").addEventListener("click", function (e) {
                var btn = e.target.closest(".js-task-details");
                if (btn) showDetails(btn.getAttribute("data-id"));
            });
        },
    };
})();

KTUtil.onDOMContentLoaded(function () {
    BidaScheduledTasks.init();
});