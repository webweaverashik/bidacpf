"use strict";

// =========================================================================
// BidaContributionList — Monthly contribution batches listing page.
// =========================================================================
var BidaContributionList = (function () {
      var table;
      var datatable;

      var initDatatable = function () {
            datatable = $(table).DataTable({
                  "info": true,
                  "order": [],
                  "lengthMenu": [10, 25, 50, 100],
                  "pageLength": 10,
                  "lengthChange": true,
                  "autoWidth": false,
                  "columnDefs": [{ orderable: false, targets: 8 }] // Actions
            });
      };

      var exportButtons = function () {
            const documentTitle = 'Monthly Contribution Batches';
            new $.fn.dataTable.Buttons(datatable, {
                  buttons: [
                        {
                              extend: 'copyHtml5', className: 'buttons-copy', title: documentTitle,
                              exportOptions: { columns: ':visible:not(.not-export)' }
                        },
                        {
                              extend: 'excelHtml5', className: 'buttons-excel', title: documentTitle,
                              exportOptions: { columns: ':visible:not(.not-export)' }
                        },
                        {
                              extend: 'csvHtml5', className: 'buttons-csv', title: documentTitle,
                              exportOptions: { columns: ':visible:not(.not-export)' }
                        },
                        {
                              extend: 'pdfHtml5', className: 'buttons-pdf', title: documentTitle,
                              exportOptions: { columns: ':visible:not(.not-export)', modifier: { page: 'all', search: 'applied' } },
                              customize: function (doc) {
                                    doc.pageMargins = [20, 20, 20, 40];
                                    doc.defaultStyle.fontSize = 10;
                                    doc.footer = getPdfFooterWithPrintTime();
                              }
                        }
                  ]
            }).container().appendTo('#kt_hidden_export_buttons');

            document.querySelectorAll('#kt_table_report_dropdown_menu [data-row-export]').forEach(function (item) {
                  item.addEventListener('click', function (e) {
                        e.preventDefault();
                        const target = document.querySelector('.buttons-' + this.getAttribute('data-row-export'));
                        if (target) { target.click(); }
                  });
            });
      };

      var handleSearch = function () {
            document.querySelector('[data-contributions-table-filter="search"]')
                  .addEventListener('keyup', function (e) { datatable.search(e.target.value).draw(); });
      };

      var handleFilter = function () {
            const form = document.querySelector('[data-contributions-table-filter="form"]');
            const filterButton = form.querySelector('[data-contributions-table-filter="filter"]');
            const resetButton = form.querySelector('[data-contributions-table-filter="reset"]');
            const selectOptions = form.querySelectorAll('select');

            filterButton.addEventListener('click', function () {
                  var filterString = '';
                  selectOptions.forEach(function (item, index) {
                        if (item.value && item.value !== '') {
                              if (index !== 0) { filterString += ' '; }
                              filterString += item.value;
                        }
                  });
                  datatable.search(filterString).draw();
            });

            resetButton.addEventListener('click', function () {
                  selectOptions.forEach(function (item) { $(item).val(null).trigger('change'); });
                  datatable.search('').draw();
            });
      };

      // AJAX generate: button preloader + JSON create/validate, redirect on success.
      var handleGenerate = function () {
            const form = document.getElementById('kt_generate_batch_form');
            if (!form) { return; }

            const submitBtn = document.getElementById('kt_generate_batch_submit');

            form.addEventListener('submit', function (e) {
                  e.preventDefault();

                  submitBtn.setAttribute('data-kt-indicator', 'on');
                  submitBtn.disabled = true;

                  fetch(BidaContributionListConfig.storeUrl, {
                        method: 'POST',
                        headers: {
                              'Content-Type': 'application/json',
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest',
                              'X-CSRF-TOKEN': BidaContributionListConfig.csrf,
                        },
                        body: JSON.stringify({ contribution_month: BidaContributionListConfig.currentMonth }),
                  })
                        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
                        .then(function (result) {
                              if (!result.ok || result.data.success === false) {
                                    var msg = result.data.message || 'Could not generate the batch.';
                                    if (result.data.errors) {
                                          msg = Object.values(result.data.errors).flat().join(' ');
                                    }
                                    toastr.error(msg);
                                    submitBtn.removeAttribute('data-kt-indicator');
                                    submitBtn.disabled = false;
                                    return;
                              }
                              toastr.success(result.data.message || 'Batch generated.');
                              window.location.href = result.data.redirect; // -> show page
                        })
                        .catch(function () {
                              toastr.error('Something went wrong while generating the batch.');
                              submitBtn.removeAttribute('data-kt-indicator');
                              submitBtn.disabled = false;
                        });
            });
      };

      return {
            init: function () {
                  table = document.getElementById('bida_contribution_table');
                  if (!table) { return; }
                  initDatatable();
                  exportButtons();
                  handleSearch();
                  handleFilter();
                  handleGenerate();
            }
      };
})();

KTUtil.onDOMContentLoaded(function () {
      BidaContributionList.init();
});