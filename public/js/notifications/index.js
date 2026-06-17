
"use strict";

var BidaNotifications = (function () {
      var table;
      var dt;

      function initTable() {
            table = document.getElementById('notifications_table');
            if (!table) {
                  return;
            }

            dt = $(table).DataTable({
                  processing: true,
                  serverSide: true,
                  searchDelay: 400,
                  order: [
                        [4, 'desc']
                  ],
                  lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                  ],
                  dom: "<'table-responsive'tr>" +
                        "<'row mt-3'" +
                        "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'l>" +
                        "<'col-sm-12 col-md-6 d-flex align-items-center justify-content-end'p>>",
                  ajax: {
                        url: BidaNotificationsConfig.dataUrl,
                        data: function (d) {
                              d.status = filterValue('status');
                              d.category = filterValue('category');
                        },
                  },
                  columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-gray-600',
                        render: function (data, type, row, meta) {
                              return meta.row + 1 + meta.settings._iDisplayStart;
                        },
                  },
                  {
                        data: 'type',
                        name: 'type',
                        orderable: true,
                        searchable: false
                  },
                  {
                        data: 'notification',
                        name: 'notification',
                        orderable: false
                  },
                  {
                        data: 'status',
                        name: 'status',
                        orderable: true,
                        searchable: false
                  },
                  {
                        data: 'time',
                        name: 'time',
                        orderable: true,
                        searchable: false
                  },
                  {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                  },
                  ],
            });
      }

      function filterValue(key) {
            var el = document.querySelector('[data-notifications-table-filter="' + key + '"]');
            return el ? el.value : '';
      }

      function bindSearch() {
            var input = document.querySelector('[data-notifications-table-filter="search"]');
            if (!input) {
                  return;
            }
            input.addEventListener('keyup', function () {
                  if (dt) {
                        dt.search(this.value).draw();
                  }
            });
      }

      function bindFilterMenu() {
            var applyBtn = document.querySelector('[data-notifications-table-filter="filter"]');
            var resetBtn = document.querySelector('[data-notifications-table-filter="reset"]');

            if (applyBtn) {
                  applyBtn.addEventListener('click', function () {
                        if (dt) {
                              dt.ajax.reload();
                        }
                  });
            }

            if (resetBtn) {
                  resetBtn.addEventListener('click', function () {
                        ['status', 'category'].forEach(function (key) {
                              var el = document.querySelector('[data-notifications-table-filter="' +
                                    key + '"]');
                              if (el) {
                                    // Clear underlying value and sync the Select2 UI.
                                    $(el).val(null).trigger('change');
                              }
                        });
                        if (dt) {
                              dt.ajax.reload();
                        }
                  });
            }
      }

      function bindMarkAll() {
            var btn = document.getElementById('notifications_mark_all');
            if (!btn) {
                  return;
            }

            btn.addEventListener('click', function () {
                  fetch(BidaNotificationsConfig.readAllUrl, {
                        method: 'POST',
                        headers: {
                              'X-CSRF-TOKEN': BidaNotificationsConfig.csrf,
                              'Accept': 'application/json',
                        },
                  })
                        .then(function (res) {
                              return res.json();
                        })
                        .then(function (json) {
                              if (json.success) {
                                    toastr.success(json.message || 'All notifications marked as read.');
                                    if (dt) {
                                          dt.ajax.reload(null, false);
                                    }
                              }
                        })
                        .catch(function () {
                              toastr.error('Could not mark notifications as read.');
                        });
            });
      }

      function bindDelete() {
            // Delegated — rows are re-rendered on every draw.
            $(table).on('click', '.js-notification-delete', function () {
                  var id = this.getAttribute('data-id');

                  Swal.fire({
                        text: 'Delete this notification?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel',
                        buttonsStyling: false,
                        customClass: {
                              confirmButton: 'btn btn-danger',
                              cancelButton: 'btn btn-light',
                        },
                  }).then(function (result) {
                        if (!result.isConfirmed) {
                              return;
                        }

                        fetch(BidaNotificationsConfig.destroyUrl.replace(':id', id), {
                              method: 'DELETE',
                              headers: {
                                    'X-CSRF-TOKEN': BidaNotificationsConfig.csrf,
                                    'Accept': 'application/json',
                              },
                        })
                              .then(function (res) {
                                    return res.json();
                              })
                              .then(function (json) {
                                    if (json.success) {
                                          toastr.success(json.message || 'Notification deleted.');
                                          if (dt) {
                                                dt.ajax.reload(null, false);
                                          }
                                    }
                              })
                              .catch(function () {
                                    toastr.error('Could not delete the notification.');
                              });
                  });
            });
      }

      return {
            init: function () {
                  initTable();
                  bindSearch();
                  bindFilterMenu();
                  bindMarkAll();
                  bindDelete();
            },
      };
})();

KTUtil.onDOMContentLoaded(function () {
      BidaNotifications.init();
});