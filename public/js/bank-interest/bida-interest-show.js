"use strict";

// =========================================================================
// BidaInterestShow — batch detail workflow actions + distribution table.
// Driven by BidaInterestShowConfig:
//   {
//     tableId, searchId, csrf,
//     actions: {
//       regenerate|submit|approve|reverse: { url, confirm, label },
//       reject: { url }
//     },
//     rejectRemarksId, rejectModalId
//   }
// Each action fires a PUT via fetch; on success we toastr + reload so the
// status badge, audit trail and ledger-derived figures refresh.
// =========================================================================
var BidaInterestShow = (function () {
    var cfg, datatable;

    var DOM =
        "<'row'<'col-sm-6 d-flex align-items-center'l><'col-sm-6'>>" +
        "<'table-responsive'tr>" +
        "<'row mt-3'<'col-sm-5 d-flex align-items-center'i><'col-sm-7 d-flex align-items-center justify-content-end'p>>";

    var debounce = function (fn, wait) {
        var t;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    };

    // ---- Distribution table (server-side AJAX) --------------------------
    var initTable = function () {
        var table = document.getElementById(cfg.tableId);
        if (!table || typeof $ === "undefined" || !$.fn.DataTable) { return; }

        datatable = $(table).DataTable({
            "processing": true,
            "serverSide": true,
            "dom": DOM,
            "ajax": { "url": cfg.distUrl },
            "order": [[6, "desc"]],
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": 10,
            "columnDefs": [{ "orderable": false, "searchable": false, "targets": 0 }],
            "language": {
                "processing": '<span class="spinner-border spinner-border-sm align-middle me-2"></span> Loading...',
                "emptyTable": "No distribution rows. Recalculate the batch to generate them.",
                "zeroRecords": "No matching members"
            },
            "columns": [
                { "data": "DT_RowIndex" },
                { "data": "cpf_acc" },
                { "data": "member" },
                { "data": "balance", "className": "text-end amount-cell" },
                { "data": "ratio", "className": "text-end" },
                { "data": "calculated", "className": "text-end amount-cell text-muted" },
                { "data": "allocated", "className": "text-end amount-cell text-gray-900" }
            ]
        });

        var search = document.getElementById(cfg.searchId);
        if (search) {
            search.addEventListener("keyup", debounce(function (e) {
                datatable.search(e.target.value).draw();
            }, 300));
        }
    };

    // ---- Shared PUT request ---------------------------------------------
    var putAction = function (url, body, onDone) {
        fetch(url, {
            method: "PUT",
            headers: {
                "X-CSRF-TOKEN": cfg.csrf,
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json",
                "Content-Type": "application/json"
            },
            body: body ? JSON.stringify(body) : null
        })
            .then(function (res) { return res.json().then(function (b) { return { ok: res.ok, body: b }; }); })
            .then(function (r) {
                if (r.ok && r.body && r.body.success) {
                    toastr.success(r.body.message || "Done.");
                    setTimeout(function () { window.location.reload(); }, 700);
                    return;
                }
                toastr.error((r.body && r.body.message) || "The action could not be completed.");
                if (onDone) { onDone(false); }
            })
            .catch(function () {
                toastr.error("Network error. Please try again.");
                if (onDone) { onDone(false); }
            });
    };

    // ---- Confirm helper (SweetAlert2 if present, else native) -----------
    var confirmThen = function (message, confirmLabel, proceed) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                text: message,
                icon: "warning",
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonText: confirmLabel || "Yes, proceed",
                cancelButtonText: "Cancel",
                customClass: {
                    confirmButton: "btn btn-primary",
                    cancelButton: "btn btn-light"
                }
            }).then(function (result) {
                if (result.isConfirmed) { proceed(); }
            });
            return;
        }
        if (window.confirm(message)) { proceed(); }
    };

    // ---- Bind the simple confirm-then-PUT actions -----------------------
    var bindActions = function () {
        ["regenerate", "submit", "approve", "reverse"].forEach(function (key) {
            var btn = document.querySelector('[data-bi-action="' + key + '"]');
            var def = cfg.actions[key];
            if (!btn || !def) { return; }

            btn.addEventListener("click", function () {
                confirmThen(def.confirm, def.label, function () {
                    btn.setAttribute("data-kt-indicator", "on");
                    btn.disabled = true;
                    putAction(def.url, null, function () {
                        btn.removeAttribute("data-kt-indicator");
                        btn.disabled = false;
                    });
                });
            });
        });
    };

    // ---- Reject (modal + remarks) ---------------------------------------
    var bindReject = function () {
        var btn = document.querySelector('[data-bi-action="reject-confirm"]');
        var def = cfg.actions.reject;
        if (!btn || !def) { return; }

        btn.addEventListener("click", function () {
            var remarksEl = document.getElementById(cfg.rejectRemarksId);
            var remarks = remarksEl ? remarksEl.value : "";

            btn.setAttribute("data-kt-indicator", "on");
            btn.disabled = true;

            putAction(def.url, { remarks: remarks }, function () {
                btn.removeAttribute("data-kt-indicator");
                btn.disabled = false;
            });
        });
    };

    // ---- Export (xlsx / csv / pdf), honouring current search ------------
    var buildExportUrl = function (format) {
        var p = new URLSearchParams();
        p.set("format", format);
        var term = datatable ? datatable.search() : "";
        if (term) { p.set("search[value]", term); }
        return cfg.exportUrl + "?" + p.toString();
    };

    var handleExport = function () {
        if (!cfg.exportUrl) { return; }
        document.querySelectorAll('#kt_table_report_dropdown_menu [data-row-export]').forEach(function (item) {
            item.addEventListener("click", function (e) {
                e.preventDefault();
                var format = this.getAttribute("data-row-export");
                toastr.info("Preparing your " + format.toUpperCase() + " download…");
                window.location.href = buildExportUrl(format);
            });
        });
    };

    return {
        init: function (config) {
            cfg = config || {};
            initTable();
            handleExport();
            bindActions();
            bindReject();
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    if (typeof BidaInterestShowConfig !== "undefined") {
        BidaInterestShow.init(BidaInterestShowConfig);
    }
});