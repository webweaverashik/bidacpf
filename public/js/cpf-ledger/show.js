"use strict";

// =========================================================================
// BidaLedgerStatement — server-side per-employee statement.
// =========================================================================
var BidaLedgerStatement = (function () {
      var table;
      var datatable;
      var fiscalYear = '';

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
                        "url": BidaLedgerStatementConfig.dataUrl,
                        "data": function (d) { d.fiscal_year = fiscalYear; }
                  },
                  "order": [[1, 'asc']], // chronological — keeps the running balance readable
                  "lengthMenu": [10, 25, 50, 100],
                  "pageLength": 25,
                  "language": {
                        "processing": '<span class="spinner-border spinner-border-sm align-middle me-2"></span> Loading...',
                        "emptyTable": "No ledger entries yet for this member",
                        "zeroRecords": "No matching entries found"
                  },
                  "columns": [
                        { data: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'date' },
                        { data: 'type' },
                        { data: 'reference' },
                        { data: 'remarks' },
                        { data: 'debit', className: 'text-end' },
                        { data: 'credit', className: 'text-end' },
                        { data: 'balance', className: 'text-end' }
                  ]
            });
      };

      var handleSearch = function () {
            document.querySelector('[data-stmt-table-filter="search"]')
                  .addEventListener('keyup', debounce(function (e) {
                        datatable.search(e.target.value).draw();
                  }, 300));
      };

      var handleFiscalYear = function () {
            var select = document.querySelector('[data-stmt-filter="fiscal-year"]');
            if (!select) { return; }

            // Select2 change must be caught via jQuery.
            $(select).on('change', function () {
                  fiscalYear = this.value || '';
                  datatable.draw();
            });
      };

      var buildExportUrl = function (format) {
            var p = new URLSearchParams();
            p.set('format', format);
            if (fiscalYear) { p.set('fiscal_year', fiscalYear); }
            var s = datatable.search();
            if (s) { p.set('search[value]', s); }
            return BidaLedgerStatementConfig.exportUrl + '?' + p.toString();
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
            init: function () {
                  table = document.getElementById('bida_ledger_stmt_table');
                  if (!table) { return; }
                  initDatatable();
                  handleSearch();
                  handleFiscalYear();
                  handleExport();
            }
      };
})();

KTUtil.onDOMContentLoaded(function () {
      BidaLedgerStatement.init();
});