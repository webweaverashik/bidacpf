"use strict";

// =========================================================================
// BidaContributionShow — Batch preview, inline row editing, and workflow
// actions (regenerate / submit / approve / reject / reverse).
// =========================================================================
var BidaContributionShow = (function () {
      var table;
      var datatable;
      var editModal;
      var currentRow = null; // <tr> currently being edited

      var fmt = function (n) {
            return Number(n || 0).toLocaleString('en-US');
      };

      var roundHalfUp = function (value) {
            // Matches MoneyService half-up rounding for positive amounts.
            return Math.round(value);
      };

      var initDatatable = function () {
            const actionsTarget = BidaContributionShowConfig.hasActionsColumn ? [{ orderable: false, targets: -1 }] : [];

            datatable = $(table).DataTable({
                  "info": true,
                  "order": [],
                  "lengthMenu": [10, 25, 50, 100],
                  "pageLength": 25,
                  "lengthChange": true,
                  "autoWidth": false,
                  "columnDefs": actionsTarget
            });
      };

      var exportButtons = function () {
            const documentTitle = 'CPF Contributions — ' + BidaContributionShowConfig.monthLabel;

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
                                    doc.defaultStyle.fontSize = 9;
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
            const search = document.querySelector('[data-contribution-rows-filter="search"]');
            search.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      };

      // Re-sum the visible data into the summary tiles.
      var recomputeSummary = function () {
            var emp = 0, gov = 0, grand = 0;
            table.querySelectorAll('tbody tr').forEach(function (row) {
                  emp += parseInt(row.querySelector('.contrib-employee')?.getAttribute('data-order') || 0, 10);
                  gov += parseInt(row.querySelector('.contrib-government')?.getAttribute('data-order') || 0, 10);
                  grand += parseInt(row.querySelector('.contrib-total')?.getAttribute('data-order') || 0, 10);
            });
            document.getElementById('summary-employee-total').textContent = fmt(emp);
            document.getElementById('summary-government-total').textContent = fmt(gov);
            document.getElementById('summary-grand-total').textContent = fmt(grand);
      };

      var setCell = function (row, selector, value) {
            const cell = row.querySelector(selector);
            cell.setAttribute('data-order', value);
            cell.textContent = fmt(value);
      };

      var openEditModal = function (row) {
            currentRow = row;
            document.getElementById('edit_employee_name').textContent =
                  row.querySelector('.contrib-name')?.textContent.trim() || '—';
            document.getElementById('edit_basic_salary').value =
                  row.querySelector('.contrib-basic')?.getAttribute('data-order') || 0;
            document.getElementById('edit_employee_contribution').value =
                  row.querySelector('.contrib-employee')?.getAttribute('data-order') || 0;
            document.getElementById('edit_government_contribution').value =
                  row.querySelector('.contrib-government')?.getAttribute('data-order') || 0;
            document.getElementById('edit_remarks').value =
                  row.querySelector('.contrib-remarks')?.textContent.trim() || '';
            editModal.show();
      };

      var handleInlineEdit = function () {
            if (!BidaContributionShowConfig.isEditable) {
                  return;
            }

            editModal = new bootstrap.Modal(document.getElementById('kt_modal_edit_contribution'));

            // Open modal (delegated, survives DataTable redraws)
            table.addEventListener('click', function (e) {
                  const btn = e.target.closest('[data-contribution-edit]');
                  if (!btn) { return; }
                  openEditModal(btn.closest('tr'));
            });

            // Auto-calc contributions from basic salary using configured rates
            document.getElementById('edit_autocalc').addEventListener('click', function () {
                  const basic = parseInt(document.getElementById('edit_basic_salary').value || 0, 10);
                  document.getElementById('edit_employee_contribution').value =
                        roundHalfUp((basic * BidaContributionShowConfig.employeeRate) / 100);
                  document.getElementById('edit_government_contribution').value =
                        roundHalfUp((basic * BidaContributionShowConfig.governmentRate) / 100);
            });

            // Save via AJAX PATCH
            document.getElementById('edit_contribution_save').addEventListener('click', function () {
                  if (!currentRow) { return; }

                  const saveBtn = this;
                  const contributionId = currentRow.getAttribute('data-contribution-id');
                  const url = BidaContributionShowConfig.updateUrlTemplate.replace('__CID__', contributionId);

                  const remarks = document.getElementById('edit_remarks').value.trim();
                  if (!remarks) {
                        toastr.error('Remarks are required for an adjustment.');
                        document.getElementById('edit_remarks').focus();
                        return;
                  }

                  const payload = {
                        basic_salary: parseInt(document.getElementById('edit_basic_salary').value || 0, 10),
                        employee_contribution: parseInt(document.getElementById('edit_employee_contribution').value || 0, 10),
                        government_contribution: parseInt(document.getElementById('edit_government_contribution').value || 0, 10),
                        remarks: remarks,
                  };

                  saveBtn.setAttribute('data-kt-indicator', 'on');
                  saveBtn.disabled = true;

                  fetch(url, {
                        method: 'PATCH',
                        headers: {
                              'Content-Type': 'application/json',
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest',
                              'X-CSRF-TOKEN': BidaContributionShowConfig.csrf,
                        },
                        body: JSON.stringify(payload),
                  })
                        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
                        .then(function (result) {
                              if (!result.ok || result.data.success === false) {
                                    var msg = result.data.message || 'Update failed.';
                                    if (result.data.errors) {
                                          msg = Object.values(result.data.errors).flat().join(' ');
                                    }
                                    toastr.error(msg);
                                    return;
                              }

                              const c = result.data.contribution;
                              setCell(currentRow, '.contrib-basic', c.basic_salary);
                              setCell(currentRow, '.contrib-employee', c.employee_contribution);
                              setCell(currentRow, '.contrib-government', c.government_contribution);
                              setCell(currentRow, '.contrib-total', c.total);
                              currentRow.querySelector('.contrib-remarks').textContent = c.remarks || '';

                              datatable.row(currentRow).invalidate('dom').draw(false);
                              recomputeSummary();
                              editModal.hide();
                              toastr.success(result.data.message || 'Contribution updated.');
                        })
                        .catch(function () {
                              toastr.error('Something went wrong while saving.');
                        })
                        .finally(function () {
                              saveBtn.removeAttribute('data-kt-indicator');
                              saveBtn.disabled = false;
                        });
            });
      };

      // Workflow buttons -> Swal confirm -> submit the matching hidden form.
      var handleWorkflowActions = function () {
            const config = {
                  regenerate: {
                        form: 'form-regenerate-batch',
                        title: 'Regenerate this batch?',
                        text: 'All rows will be rebuilt from current salaries and any manual edits will be discarded.',
                        confirm: 'Yes, regenerate', icon: 'warning'
                  },
                  submit: {
                        form: 'form-submit-batch',
                        title: 'Submit for approval?',
                        text: 'The batch will be locked and sent to an administrator for approval.',
                        confirm: 'Yes, submit', icon: 'question'
                  },
                  approve: {
                        form: 'form-approve-batch',
                        title: 'Approve and post to ledger?',
                        text: 'CPF ledger entries will be created for every employee. This is the point of record.',
                        confirm: 'Yes, approve', icon: 'warning'
                  },
                  reverse: {
                        form: 'form-reverse-batch',
                        title: 'Reverse this approved batch?',
                        text: 'Mirror-image reversal entries will be posted to each employee ledger.',
                        confirm: 'Yes, reverse', icon: 'warning'
                  },
            };

            document.querySelectorAll('[data-batch-action]').forEach(function (btn) {
                  btn.addEventListener('click', function () {
                        const action = this.getAttribute('data-batch-action');

                        // "Send back" has its own remarks prompt.
                        if (action === 'reject') {
                              Swal.fire({
                                    title: 'Send batch back to officer?',
                                    input: 'textarea',
                                    inputLabel: 'Reason / remarks (optional)',
                                    inputPlaceholder: 'Explain what needs correcting…',
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonText: 'Send Back',
                                    confirmButtonColor: '#d33',
                                    cancelButtonText: 'Cancel',
                              }).then(function (result) {
                                    if (result.isConfirmed) {
                                          document.getElementById('reject_remarks').value = result.value || '';
                                          document.getElementById('form-reject-batch').submit();
                                    }
                              });
                              return;
                        }

                        const c = config[action];
                        if (!c) { return; }

                        Swal.fire({
                              title: c.title,
                              text: c.text,
                              icon: c.icon,
                              showCancelButton: true,
                              confirmButtonText: c.confirm,
                              cancelButtonText: 'Cancel',
                              confirmButtonColor: '#3085d6',
                              cancelButtonColor: '#d33',
                        }).then(function (result) {
                              if (result.isConfirmed) {
                                    document.getElementById(c.form).submit();
                              }
                        });
                  });
            });
      };

      return {
            init: function () {
                  table = document.getElementById('bida_contribution_rows_table');
                  if (!table) { return; }

                  initDatatable();
                  exportButtons();
                  handleSearch();
                  handleInlineEdit();
                  handleWorkflowActions();
            }
      };
})();

KTUtil.onDOMContentLoaded(function () {
      BidaContributionShow.init();
});