"use strict";

// =========================================================================
// BidaLedgerMembers — server-side member list (search/filter/sort/page/export).
// =========================================================================
var BidaLedgerMembers = (function () {
      var table;
      var datatable;
      var statusFilter = '';

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
                        "url": BidaLedgerMembersConfig.dataUrl,
                        "data": function (d) { d.status = statusFilter; }
                  },
                  "order": [[2, 'asc']],
                  "lengthMenu": [10, 25, 50, 100],
                  "pageLength": 10,
                  "language": {
                        "processing": '<span class="spinner-border spinner-border-sm align-middle me-2"></span> Loading...',
                        "emptyTable": "No members found",
                        "zeroRecords": "No matching members found"
                  },
                  "columns": [
                        { data: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'account' },
                        { data: 'name' },
                        { data: 'designation' },
                        { data: 'pay_scale' },
                        { data: 'grade' },
                        { data: 'basic_salary', className: 'text-end' },
                        { data: 'balance', className: 'text-end' },
                        { data: 'status' },
                        { data: 'actions', orderable: false, searchable: false, className: 'text-end' }
                  ]
            });
      };

      var handleSearch = function () {
            document.querySelector('[data-ledger-table-filter="search"]')
                  .addEventListener('keyup', debounce(function (e) {
                        datatable.search(e.target.value).draw();
                  }, 300));
      };

      var handleFilter = function () {
            var form = document.querySelector('[data-ledger-table-filter="form"]');
            var filterBtn = form.querySelector('[data-ledger-table-filter="filter"]');
            var resetBtn = form.querySelector('[data-ledger-table-filter="reset"]');
            var statusSelect = form.querySelector('[data-ledger-filter="status"]');

            filterBtn.addEventListener('click', function () {
                  statusFilter = statusSelect.value || '';
                  datatable.draw();
            });
            resetBtn.addEventListener('click', function () {
                  statusFilter = '';
                  $(statusSelect).val(null).trigger('change');
                  datatable.draw();
            });
      };

      var buildExportUrl = function (format) {
            var p = new URLSearchParams();
            p.set('format', format);
            if (statusFilter) { p.set('status', statusFilter); }
            var s = datatable.search();
            if (s) { p.set('search[value]', s); }
            return BidaLedgerMembersConfig.exportUrl + '?' + p.toString();
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
                  table = document.getElementById('bida_ledger_member_table');
                  if (!table) { return; }
                  initDatatable();
                  handleSearch();
                  handleFilter();
                  handleExport();
            }
      };
})();

KTUtil.onDOMContentLoaded(function () {
      BidaLedgerMembers.init();
});