@extends('layouts.app')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'Notifications')

@section('header-title')
    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
            Notifications
        </h1>
    </div>
@endsection

@section('content')
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title (filters)-->
            <div class="card-title">
                <div class="d-flex flex-wrap gap-3">
                    <!--begin::Status filter-->
                    <select class="form-select form-select-solid w-150px" data-control="select2" data-hide-search="true"
                        data-notifications-table-filter="status">
                        <option value="">All status</option>
                        <option value="unread">Unread</option>
                        <option value="read">Read</option>
                    </select>
                    <!--end::Status filter-->
                    <!--begin::Category filter-->
                    <select class="form-select form-select-solid w-175px" data-control="select2" data-hide-search="true"
                        data-notifications-table-filter="category">
                        <option value="">All types</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <!--end::Category filter-->
                </div>
            </div>
            <!--end::Card title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <button type="button" class="btn btn-light-primary" id="notifications_mark_all">
                    <i class="ki-outline ki-check-circle fs-3"></i>
                    Mark all as read
                </button>
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body pt-0">
            <table id="notifications_table" class="table table-row-dashed table-row-gray-300 align-middle gy-4 ashik-table">
                <thead>
                    <tr class="fw-bold text-gray-700 fs-7 text-uppercase gs-0">
                        <th class="w-50px">#</th>
                        <th>Type</th>
                        <th>Notification</th>
                        <th class="w-100px">Status</th>
                        <th class="w-150px">Time</th>
                        <th class="text-end w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <!--end::Card body-->
    </div>
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        var BidaNotificationsConfig = {
            dataUrl: "{{ route('notifications.data') }}",
            readAllUrl: "{{ route('notifications.read-all') }}",
            // :id is swapped client-side
            destroyUrl: "{{ route('notifications.destroy', ':id') }}",
            csrf: "{{ csrf_token() }}",
        };

        var BidaNotifications = (function() {
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
                        data: function(d) {
                            d.status = filterValue('status');
                            d.category = filterValue('category');
                        },
                    },
                    columns: [{
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-gray-600',
                            render: function(data, type, row, meta) {
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

            function bindFilters() {
                ['status', 'category'].forEach(function(key) {
                    var el = document.querySelector('[data-notifications-table-filter="' + key + '"]');
                    if (!el) {
                        return;
                    }
                    // Select2 fires its change through jQuery.
                    $(el).on('change', function() {
                        if (dt) {
                            dt.ajax.reload();
                        }
                    });
                });
            }

            function bindMarkAll() {
                var btn = document.getElementById('notifications_mark_all');
                if (!btn) {
                    return;
                }

                btn.addEventListener('click', function() {
                    fetch(BidaNotificationsConfig.readAllUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': BidaNotificationsConfig.csrf,
                                'Accept': 'application/json',
                            },
                        })
                        .then(function(res) {
                            return res.json();
                        })
                        .then(function(json) {
                            if (json.success) {
                                toastr.success(json.message || 'All notifications marked as read.');
                                if (dt) {
                                    dt.ajax.reload(null, false);
                                }
                            }
                        })
                        .catch(function() {
                            toastr.error('Could not mark notifications as read.');
                        });
                });
            }

            function bindDelete() {
                // Delegated — rows are re-rendered on every draw.
                $(table).on('click', '.js-notification-delete', function() {
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
                    }).then(function(result) {
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
                            .then(function(res) {
                                return res.json();
                            })
                            .then(function(json) {
                                if (json.success) {
                                    toastr.success(json.message || 'Notification deleted.');
                                    if (dt) {
                                        dt.ajax.reload(null, false);
                                    }
                                }
                            })
                            .catch(function() {
                                toastr.error('Could not delete the notification.');
                            });
                    });
                });
            }

            return {
                init: function() {
                    initTable();
                    bindFilters();
                    bindMarkAll();
                    bindDelete();
                },
            };
        })();

        KTUtil.onDOMContentLoaded(function() {
            BidaNotifications.init();
        });
    </script>
@endpush
