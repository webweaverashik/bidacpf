<!--begin::Settings Hero Card-->
<div class="card mb-6 mb-xl-9">
    <div class="card-body pt-9 pb-0">
        <!--begin::Details-->
        <div class="d-flex flex-wrap flex-sm-nowrap mb-6">
            <!--begin::Icon-->
            <div
                class="d-flex flex-center flex-shrink-0 bg-light-primary rounded w-100px h-100px w-lg-150px h-lg-150px me-7 mb-4">
                <img src="{{ asset('assets/img/icon.png') }}" alt="Settings"
                    class="w-50px h-50px w-lg-100px h-lg-100px object-fit-contain">
            </div>
            <!--end::Icon-->

            <!--begin::Wrapper-->
            <div class="flex-grow-1">
                <!--begin::Head-->
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                    <!--begin::Details-->
                    <div class="d-flex flex-column">
                        <!--begin::Title-->
                        <div class="d-flex align-items-center mb-1">
                            <span class="text-gray-900 fs-2 fw-bold me-3">BIDA CPF Settings</span>
                        </div>
                        <!--end::Title-->

                        <!--begin::Info-->
                        <div class="d-flex flex-wrap fw-semibold mb-4 fs-5 text-gray-500">
                            Manage your application settings, payscales, employee upload, backup.
                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::Details-->
                </div>
                <!--end::Head-->

                <!--begin::Stats-->
                <div class="d-flex flex-wrap">
                    <!--begin::Stat-->
                    <div class="border border-gray-300 border-dashed rounded min-w-100px py-3 px-4 me-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="ki-outline ki-user-edit fs-2 text-primary me-2"></i>
                            <div class="fs-4 fw-bold" data-kt-countup="true"
                                data-kt-countup-value="{{ \App\Models\Auth\User::count() }}">0</div>
                        </div>
                        <div class="fw-semibold fs-6 text-gray-500">Users</div>
                    </div>
                    <!--end::Stat-->

                    <!--begin::Stat-->
                    <div class="border border-gray-300 border-dashed rounded min-w-100px py-3 px-4 me-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="ki-outline ki-people fs-2 text-info me-2"></i>
                            <div class="fs-4 fw-bold" data-kt-countup="true"
                                data-kt-countup-value="{{ \App\Models\Employee\Employee::active()->count() }}">0</div>
                        </div>
                        <div class="fw-semibold fs-6 text-gray-500">Employees</div>
                    </div>
                    <!--end::Stat-->

                    <!--begin::Stat-->
                    <div class="border border-gray-300 border-dashed rounded min-w-100px py-3 px-4 me-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="ki-outline ki-dollar fs-2 text-success me-2"></i>
                            <div class="fs-4 fw-bold" data-kt-countup="true"
                                data-kt-countup-value="{{ \App\Models\Auth\User::count() }}">
                                0</div>
                        </div>
                        <div class="fw-semibold fs-6 text-gray-500">CPF Balance</div>
                    </div>
                    <!--end::Stat-->
                </div>
                <!--end::Stats-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Details-->

        <!--begin::Navs-->
        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
            <!--begin::Nav item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6" id="settings_users_link"
                    href="{{ route('settings.index') }}">
                    <i class="ki-outline ki-user-edit fs-4 me-2"></i>
                    Settings
                </a>
            </li>
            <!--end::Nav item-->

            <!--begin::Nav item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6" id="settings_branch_link"
                    href="{{ route('settings.index') }}">
                    <i class="ki-outline ki-parcel fs-4 me-2"></i>
                    Payscale
                </a>
            </li>
            <!--end::Nav item-->

            <!--begin::Nav item-->
            @if (app()->environment('local'))
                <li class="nav-item">
                    <a class="nav-link text-active-primary py-5 me-6" id="settings_bulk_admission_link"
                        href="{{ route('settings.index') }}">
                        <i class="ki-outline ki-file-up fs-4 me-2"></i>
                        Employee Upload
                    </a>
                </li>
            @endif
            <!--end::Nav item-->

            <!--begin::Nav item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5" id="settings_backup_link"
                    href="{{ route('backup') }}">
                    <i class="ki-outline ki-data fs-4 me-2"></i>
                    DB Backup
                </a>
            </li>
            <!--end::Nav item-->
        </ul>
        <!--end::Navs-->
    </div>
</div>
<!--end::Settings Hero Card-->
