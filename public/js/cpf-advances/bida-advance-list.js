"use strict";

// =========================================================================
// BidaAdvanceList — generic server-side list (search/filter/sort/page/export).
// Mirrors the cpf-ledger BidaLedgerMembers pattern. Driven entirely by a
// `BidaAdvanceListConfig` object inlined in the Blade:
//   {
//     tableId, dataUrl, exportUrl,
//     filterPrefix,            // e.g. "advance" -> data-advance-table-filter
//     order, pageLength,
//     columns: [ ...DataTables columns... ],
//     filters: ["status"],     // select keys -> data-<prefix>-filter="status"
//     extraData: { scope: "outstanding" }   // optional, always-sent params
//   }
// =========================================================================
var BidaAdvanceList = (function () {
    var cfg, table, datatable;
    var activeFilters = {};

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

    var initDatatable = function () {
        datatable = $(table).DataTable({
            "processing": true,
            "serverSide": true,
            "dom": DOM,
            "ajax": {
                "url": cfg.dataUrl,
                "data": function (d) {
                    (cfg.filters || []).forEach(function (key) {
                        d[key] = activeFilters[key] || "";
                    });
                    var extra = cfg.extraData || {};
                    Object.keys(extra).forEach(function (k) { d[k] = extra[k]; });
                }
            },
            "order": cfg.order || [],
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": cfg.pageLength || 10,
            "language": {
                "processing": '<span class="spinner-border spinner-border-sm align-middle me-2"></span> Loading...',
                "emptyTable": "No records found",
                "zeroRecords": "No matching records found"
            },
            "columns": cfg.columns
        });
    };

    var sel = function (suffix) {
        return '[data-' + cfg.filterPrefix + '-table-filter="' + suffix + '"]';
    };

    var handleSearch = function () {
        var input = document.querySelector(sel('search'));
        if (!input) { return; }
        input.addEventListener('keyup', debounce(function (e) {
            datatable.search(e.target.value).draw();
        }, 300));
    };

    var handleFilter = function () {
        var form = document.querySelector(sel('form'));
        if (!form) { return; }

        var applyBtn = form.querySelector(sel('filter'));
        var resetBtn = form.querySelector(sel('reset'));

        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                (cfg.filters || []).forEach(function (key) {
                    var s = form.querySelector('[data-' + cfg.filterPrefix + '-filter="' + key + '"]');
                    activeFilters[key] = s ? (s.value || "") : "";
                });
                datatable.draw();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                (cfg.filters || []).forEach(function (key) {
                    activeFilters[key] = "";
                    var s = form.querySelector('[data-' + cfg.filterPrefix + '-filter="' + key + '"]');
                    if (s) { $(s).val(null).trigger('change'); }
                });
                datatable.draw();
            });
        }
    };

    var buildExportUrl = function (format) {
        var p = new URLSearchParams();
        p.set('format', format);
        (cfg.filters || []).forEach(function (key) {
            if (activeFilters[key]) { p.set(key, activeFilters[key]); }
        });
        var extra = cfg.extraData || {};
        Object.keys(extra).forEach(function (k) { p.set(k, extra[k]); });
        var s = datatable.search();
        if (s) { p.set('search[value]', s); }
        return cfg.exportUrl + '?' + p.toString();
    };

    var handleExport = function () {
        document.querySelectorAll('#kt_table_report_dropdown_menu [data-row-export]').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                var format = this.getAttribute('data-row-export');
                toastr.info('Preparing your ' + format.toUpperCase() + ' download…');
                window.location.href = buildExportUrl(format);
            });
        });
    };

    return {
        init: function (config) {
            cfg = config || {};
            table = document.getElementById(cfg.tableId);
            if (!table) { return; }
            initDatatable();
            handleSearch();
            handleFilter();
            handleExport();
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    if (typeof BidaAdvanceListConfig !== "undefined") {
        BidaAdvanceList.init(BidaAdvanceListConfig);
    }
});
