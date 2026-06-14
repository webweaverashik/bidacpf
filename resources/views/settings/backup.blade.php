@extends('layouts.app')

@section('title', 'Database Backup')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Database Backup
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">Settings</li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Backup</li>
        </ul>
    </div>
@endsection

@section('content')
    @include('settings.partials.hero')

    <!--begin::Auto Backup Notice-->
    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed mb-6 p-6">
        <i class="ki-outline ki-shield-tick fs-2tx text-primary me-4"></i>
        <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
            <div class="mb-3 mb-md-0 fw-semibold">
                <h4 class="text-gray-900 fw-bold">Automatic Daily Backup Enabled</h4>
                <div class="fs-6 text-gray-700 pe-7">
                    Your database is automatically backed up every day. The system runs a scheduled backup task daily
                    to ensure your data is always protected. You can also create manual backups anytime using the button
                    below.
                </div>
            </div>
            <span class="badge badge-primary fs-7 fw-bold px-4 py-3">
                <i class="ki-outline ki-time fs-5 me-1"></i> Daily at Midnight
            </span>
        </div>
    </div>
    <!--end::Auto Backup Notice-->

    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>BIDA CPF Backup - Database & Files</h2>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-primary" id="btn-create-backup">
                    <i class="ki-outline ki-plus fs-2"></i>
                    Create Backup
                </button>
            </div>
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Stats-->
            <div class="row g-5 mb-8">
                <div class="col-md-4">
                    <div class="border border-gray-300 border-dashed rounded py-3 px-4">
                        <div class="d-flex align-items-center">
                            <i class="ki-outline ki-file-added fs-2 text-primary me-2"></i>
                            <div class="fs-4 fw-bold" id="stats-total">{{ count($backups ?? []) }}</div>
                        </div>
                        <div class="fw-semibold fs-6 text-gray-500">Total Backups</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border border-gray-300 border-dashed rounded py-3 px-4">
                        <div class="d-flex align-items-center">
                            <i class="ki-outline ki-folder fs-2 text-info me-2"></i>
                            <div class="fs-4 fw-bold" id="stats-size">{{ $totalSize ?? '0 B' }}</div>
                        </div>
                        <div class="fw-semibold fs-6 text-gray-500">Total Size</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border border-gray-300 border-dashed rounded py-3 px-4">
                        <div class="d-flex align-items-center">
                            <i class="ki-outline ki-time fs-2 text-success me-2"></i>
                            <div class="fs-4 fw-bold" id="stats-last">{{ $lastBackup ?? 'Never' }}</div>
                        </div>
                        <div class="fw-semibold fs-6 text-gray-500">Last Backup</div>
                    </div>
                </div>
            </div>
            <!--end::Stats-->

            <!--begin::Table-->
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="ps-4 rounded-start">#</th>
                            <th>Type</th>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th class="pe-4 text-end rounded-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="backup-table-body">
                        @forelse($backups ?? [] as $index => $backup)
                            <tr data-filename="{{ $backup['filename'] }}" data-type="{{ $backup['type'] }}">
                                <td class="ps-4">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge {{ $backup['type_badge'] }}">
                                        {{ $backup['type_label'] }}
                                    </span>
                                </td>
                                <td>
                                    <i class="ki-outline ki-file-added fs-4 text-primary me-2"></i>
                                    {{ $backup['filename'] }}
                                </td>
                                <td>{{ $backup['size_formatted'] }}</td>
                                <td>{{ $backup['date_formatted'] }}</td>
                                <td class="pe-4 text-end">
                                    <a href="{{ $backup['download_url'] }}" class="btn btn-sm btn-light-success me-2"
                                        title="Download">
                                        <i class="ki-outline ki-file-down fs-4"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-light-danger btn-delete-backup"
                                        data-filename="{{ $backup['filename'] }}" data-type="{{ $backup['type'] }}"
                                        title="Delete">
                                        <i class="ki-outline ki-trash fs-4"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="no-backups-row">
                                <td colspan="6" class="text-center py-10">
                                    <i class="ki-outline ki-file-deleted fs-3x text-gray-400 mb-5"></i>
                                    <p class="text-gray-500 fs-5 mb-0">No backups found</p>
                                    <p class="text-gray-400 fs-7">Click "Create Backup" to generate your first backup</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->

    <!--begin::Modal - Create Backup-->
    <div class="modal fade" id="kt_modal_create_backup" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Create New Backup</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_create_backup_form" class="form" action="#">
                        <!--begin::Input group-->
                        <div class="fv-row mb-10">
                            <!--begin::Label-->
                            <label class="fs-6 fw-semibold form-label mb-5">
                                <span class="required">Select Backup Type</span>
                            </label>
                            <!--end::Label-->

                            <!--begin::Options-->
                            <div class="d-flex flex-column">
                                <!--begin::Option-->
                                <label
                                    class="d-flex flex-stack cursor-pointer mb-5 p-4 border border-dashed border-gray-300 rounded-3 hover-elevate-up bg-hover-light-primary active-border-primary transition-all">
                                    <span class="d-flex align-items-center me-2">
                                        <span class="symbol symbol-40px me-4">
                                            <span class="symbol-label bg-light-primary">
                                                <i class="ki-outline ki-data fs-2 text-primary"></i>
                                            </span>
                                        </span>
                                        <span class="d-flex flex-column">
                                            <span class="fw-bold fs-6 text-gray-800 mb-1">Database Only</span>
                                            <span class="fs-7 text-muted">Backup database tables and records</span>
                                        </span>
                                    </span>
                                    <span class="form-check form-check-custom form-check-solid form-check-sm">
                                        <input class="form-check-input" type="radio" name="backup_type"
                                            value="database" checked="checked" />
                                    </span>
                                </label>
                                <!--end::Option-->

                                <!--begin::Option-->
                                <label
                                    class="d-flex flex-stack cursor-pointer mb-5 p-4 border border-dashed border-gray-300 rounded-3 hover-elevate-up bg-hover-light-success active-border-success transition-all">
                                    <span class="d-flex align-items-center me-2">
                                        <span class="symbol symbol-40px me-4">
                                            <span class="symbol-label bg-light-success">
                                                <i class="ki-outline ki-folder fs-2 text-success"></i>
                                            </span>
                                        </span>
                                        <span class="d-flex flex-column">
                                            <span class="fw-bold fs-6 text-gray-800 mb-1">Application Files Only</span>
                                            <span class="fs-7 text-muted">Backup app, config, routes, resources</span>
                                        </span>
                                    </span>
                                    <span class="form-check form-check-custom form-check-solid form-check-sm">
                                        <input class="form-check-input" type="radio" name="backup_type"
                                            value="files" />
                                    </span>
                                </label>
                                <!--end::Option-->

                                <!--begin::Option-->
                                <label
                                    class="d-flex flex-stack cursor-pointer mb-5 p-4 border border-dashed border-gray-300 rounded-3 hover-elevate-up bg-hover-light-info active-border-info transition-all">
                                    <span class="d-flex align-items-center me-2">
                                        <span class="symbol symbol-40px me-4">
                                            <span class="symbol-label bg-light-info">
                                                <i class="ki-outline ki-archive fs-2 text-info"></i>
                                            </span>
                                        </span>
                                        <span class="d-flex flex-column">
                                            <span class="fw-bold fs-6 text-gray-800 mb-1">Both (Separate Files)</span>
                                            <span class="fs-7 text-muted">Create separate backup files for database and
                                                application</span>
                                        </span>
                                    </span>
                                    <span class="form-check form-check-custom form-check-solid form-check-sm">
                                        <input class="form-check-input" type="radio" name="backup_type"
                                            value="both" />
                                    </span>
                                </label>
                                <!--end::Option-->
                            </div>
                            <!--end::Options-->
                        </div>
                        <!--end::Input group-->

                        <!--begin::Notice-->
                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4">
                            <i class="ki-outline ki-information-5 fs-2tx text-warning me-4"></i>
                            <div class="d-flex flex-column">
                                <span class="fs-7 text-gray-700">Admin users will receive an email notification after the
                                    backup is created.</span>
                            </div>
                        </div>
                        <!--end::Notice-->

                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" id="kt_modal_create_backup_submit" class="btn btn-primary">
                                <span class="indicator-label">
                                    <i class="ki-outline ki-file-added fs-4 me-1"></i> Create Backup
                                </span>
                                <span class="indicator-progress">
                                    Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::Form-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Create Backup-->
@endsection

@push('page-js')
    <script>
        const backupRoutes = {
            create: "{{ route('backup.create') }}",
            download: "{{ url('settings/backup/download') }}",
            destroy: "{{ url('settings/backup') }}"
        };
        const csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{{ asset('js/settings/backup.js') }}"></script>
    <script>
        document.getElementById("settings_link")?.classList.add("active");
        document.getElementById("settings_backup_link")?.classList.add("active");
    </script>
@endpush
