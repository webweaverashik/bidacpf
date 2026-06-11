"use strict";

// =========================================================================
// BidaLedgerTransactions — server-side transaction log.
// =========================================================================
var BidaLedgerTransactions = (function () {
      var table;
      var datatable;

      var filters = { employee_id: '', type: '', from: '', to: '' };

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
                        "url": BidaLedgerTxnConfig.dataUrl,
                        "data": function (d) {
                              d.employee_id = filters.employee_id;
                              d.type = filters.type;
                              d.from = filters.from;
                              d.to = filters.to;
                        }
                  },
                  "order": [[1, 'desc']],
                  "lengthMenu": [10, 25, 50, 100],
                  "pageLength": 25,
                  "language": {
                        "processing": '<span class="spinner-border spinner-border-sm align-middle me-2"></span> Loading...',
                        "emptyTable": "No transactions found",
                        "zeroRecords": "No matching transactions found"
                  },
                  "columns": [
                        { data: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'date' },
                        { data: 'employee' },
                        { data: 'type' },
                        { data: 'reference' },
                        { data: 'debit', className: 'text-end' },
                        { data: 'credit', className: 'text-end' },
                        { data: 'balance', className: 'text-end' },
                        { data: 'remarks', orderable: false }
                  ]
            });
      };

      var handleSearch = function () {
            document.querySelector('[data-txn-table-filter="search"]')
                  .addEventListener('keyup', debounce(function (e) {
                        datatable.search(e.target.value).draw();
                  }, 300));
      };

      var handleFilter = function () {
            var form = document.querySelector('[data-txn-table-filter="form"]');
            var filterBtn = form.querySelector('[data-txn-table-filter="filter"]');
            var resetBtn = form.querySelector('[data-txn-table-filter="reset"]');
            var employeeSelect = form.querySelector('[data-txn-filter="employee"]');
            var typeSelect = form.querySelector('[data-txn-filter="type"]');
            var fromInput = document.getElementById('txn_from');
            var toInput = document.getElementById('txn_to');

            if (typeof flatpickr !== 'undefined') {
                  flatpickr(fromInput, { dateFormat: 'Y-m-d', allowInput: true });
                  flatpickr(toInput, { dateFormat: 'Y-m-d', allowInput: true });
            }

            filterBtn.addEventListener('click', function () {
                  filters.employee_id = employeeSelect.value || '';
                  filters.type = typeSelect.value || '';
                  filters.from = fromInput.value || '';
                  filters.to = toInput.value || '';
                  datatable.draw();
            });

            resetBtn.addEventListener('click', function () {
                  filters = { employee_id: '', type: '', from: '', to: '' };
                  $(employeeSelect).val(null).trigger('change');
                  $(typeSelect).val(null).trigger('change');
                  fromInput.value = '';
                  toInput.value = '';
                  if (fromInput._flatpickr) { fromInput._flatpickr.clear(); }
                  if (toInput._flatpickr) { toInput._flatpickr.clear(); }
                  datatable.draw();
            });
      };

      var buildExportUrl = function (format) {
            var p = new URLSearchParams();
            p.set('format', format);
            Object.keys(filters).forEach(function (k) {
                  if (filters[k]) { p.set(k, filters[k]); }
            });
            var s = datatable.search();
            if (s) { p.set('search[value]', s); }
            return BidaLedgerTxnConfig.exportUrl + '?' + p.toString();
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
                  table = document.getElementById('bida_ledger_txn_table');
                  if (!table) { return; }
                  initDatatable();
                  handleSearch();
                  handleFilter();
                  handleExport();
            }
      };
})();

KTUtil.onDOMContentLoaded(function () {
      BidaLedgerTransactions.init();
});