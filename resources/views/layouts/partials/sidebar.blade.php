<div id="kt_app_sidebar" class="app-sidebar  flex-column " data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    <!--begin::Logo-->
    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
        <!--begin::Logo image-->
        <a href="{{ route('dashboard') }}">
            <img alt="Logo" src="{{ asset('assets/img/logo-dark.png') }}" class="h-50px app-sidebar-logo-default" />
            <img alt="Logo" src="{{ asset('assets/img/icon.png') }}" class="h-20px app-sidebar-logo-minimize" />
        </a>
        <!--end::Logo image-->

        <!--begin::Sidebar toggle-->
        <!--begin::Minimized sidebar setup:
            if (isset($_COOKIE["sidebar_minimize_state"]) && $_COOKIE["sidebar_minimize_state"] === "on") {
                1. "src/js/layout/sidebar.js" adds "sidebar_minimize_state" cookie value to save the sidebar minimize state.
                2. Set data-kt-app-sidebar-minimize="on" attribute for body tag.
                3. Set data-kt-toggle-state="active" attribute to the toggle element with "kt_app_sidebar_toggle" id.
                4. Add "active" class to to sidebar toggle element with "kt_app_sidebar_toggle" id.
            }
        -->
        <div id="kt_app_sidebar_toggle"
            class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate "
            data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
            data-kt-toggle-name="app-sidebar-minimize">
            <i class="ki-outline ki-black-left-line fs-3 rotate-180"></i>
        </div>
        <!--end::Sidebar toggle-->
    </div>
    <!--end::Logo-->

    <!--begin::sidebar menu-->
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <!--begin::Menu wrapper-->
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
            <!--begin::Scroll wrapper-->
            <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" data-kt-scroll="true"
                data-kt-scroll-activate="true" data-kt-scroll-height="auto"
                data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
                data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
                data-kt-scroll-save-state="true">
                <!--begin::Menu-->
                <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="kt_app_sidebar_menu"
                    data-kt-menu="true" data-kt-menu-expand="false">

                    <!--begin:Dashboard Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link" href="{{ route('dashboard') }}" id="dashboard_link">
                            <span class="menu-icon">
                                <i class="ki-outline ki-chart-pie-4 fs-2"></i>
                            </span>
                            <span class="menu-title">Dashboard</span>
                        </a>
                        <!--end:Dashboard Menu link-->
                    </div>
                    <!--end:Dashboard Menu item-->

                    <!--begin:Employee Info Menu item-->
                    @canany(['employee.view', 'employee.create', 'salary.view'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="employee_info_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-people fs-1"></i>
                                </span>
                                <span class="menu-title">Employee Info</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('employee.view')
                                    <!--begin:All Employees Menu item-->
                                    <div class="menu-item">
                                        <a class="menu-link" id="all_employees_link" href="#">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">All Employees</span>
                                        </a>
                                    </div>
                                    <!--end:All Employees Menu item-->
                                @endcan

                                @can('salary.view')
                                    <!--begin:Salary History Menu item-->
                                    <div class="menu-item">
                                        <a class="menu-link" id="salary_history_link" href="#">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Salary History</span>
                                        </a>
                                    </div>
                                    <!--end:Salary History Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end: Employee Info Menu item-->

                    <!--begin:CPF Operation Menu item-->
                    @canany(['employee.view'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="cpf_operation_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-wallet fs-2"></i>
                                </span>
                                <span class="menu-title">CPF Operation</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('employee.view')
                                    <!--begin:Monthly Contributions Menu item-->
                                    <div class="menu-item">
                                        <a class="menu-link" id="monthly_contributions_link" href="#">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Monthly Contributions</span>
                                        </a>
                                    </div>
                                    <!--end:Monthly Contributions Menu item-->
                                @endcan

                                @can('employee.view')
                                    <!--begin:CPF Ledger Menu item-->
                                    <div class="menu-item">
                                        <a class="menu-link" id="cpf_ledger_link" href="#">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">CPF Ledger</span>
                                        </a>
                                    </div>
                                    <!--end:CPF Ledger Menu item-->
                                @endcan

                                @can('employee.view')
                                    <!--begin:Ledger Transactions Menu item-->
                                    <div class="menu-item">
                                        <a class="menu-link" id="ledger_transactions_link" href="#">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Ledger Transactions</span>
                                        </a>
                                    </div>
                                    <!--end:Ledger Transactions Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end: CPF Operation Menu item-->

                    <!--begin:CPF Advance/Loan Menu item-->
                    @canany(['employee.view'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="cpf_advance_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-credit-cart fs-3"></i>
                                </span>
                                <span class="menu-title">CPF Advance/Loan</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('employee.view')
                                    <!--begin:Advance Applications Menu item-->
                                    <div class="menu-item">
                                        <a class="menu-link" href="#" id="advance_applications_link">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Advance Applications</span>
                                        </a>
                                    </div>
                                    <!--end:Advance Applications Menu item-->
                                @endcan

                                @can('employee.view')
                                    <!--begin:Outstanding Advances Menu item-->
                                    <div class="menu-item">
                                        <a class="menu-link" href="#" id="outstanding_advances_link">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Outstanding Advances</span>
                                        </a>
                                    </div>
                                    <!--end:Outstanding Advances Menu item-->
                                @endcan

                                @can('employee.view')
                                    <!--begin:Recovery Posting Menu item-->
                                    <div class="menu-item">
                                        <a class="menu-link" href="#" id="recovery_posting_link">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Recovery Posting</span>
                                        </a>
                                    </div>
                                    <!--end:Recovery Posting Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end: CPF Advance/Loan Menu item-->

                    <!--begin:Interest Management Menu-->
                    @can('report.view')
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="interest_management_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-percentage fs-2"></i>
                                </span>
                                <span class="menu-title">Interest Management</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                <!--begin:Interest Distribution Menu item-->
                                <div class="menu-item">
                                    <a class="menu-link" id="interest_distribution_link" href="#">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Interest Distribution</span>
                                    </a>
                                </div>
                                <!--end:Interest Distribution Menu item-->

                                <!--begin:Distribution History Menu item-->
                                <div class="menu-item">
                                    <a class="menu-link" id="distribution_history_link" href="#">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Distribution History</span>
                                    </a>
                                </div>
                                <!--end:Distribution History Menu item-->
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcan
                    <!--end:Interest Management Menu-->

                    <!--begin:Reports Menu-->
                    @can('report.view')
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link" href="#" id="reports_link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-filter fs-2"></i>
                                </span>
                                <span class="menu-title">Reports</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                    @endcan
                    <!--end:Reports Menu-->

                    <!--begin:Menu Separator item-->
                    <div class="menu-item pt-5">
                        <div class="menu-content">
                            <span class="menu-heading fw-bold text-uppercase fs-7">Settings</span>
                        </div>
                    </div>
                    <!--end:Menu Separator item-->

                    <!--begin:Users Menu item-->
                    @canany(['user.view', 'user.create'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="users_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-user fs-1"></i>
                                </span>
                                <span class="menu-title">Users</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('user.view')
                                    <!--begin:All Users Menu item-->
                                    <div class="menu-item">
                                        <a class="menu-link" id="all_users_link" href="#">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">All Users</span>
                                        </a>
                                    </div>
                                    <!--end:All Users Menu item-->
                                @endcan

                                @can('user.create')
                                    <!--begin:Add User Menu item-->
                                    <div class="menu-item">
                                        <a class="menu-link" id="add_user_link" href="#">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Add New</span>
                                        </a>
                                    </div>
                                    <!--end:Add User Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end: Users Menu item-->

                    <!--begin:Settings Menu item-->
                    @canany(['user.view', 'user.create'])
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link" href="#" id="settings_link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-setting-2 fs-2"></i>
                                </span>
                                <span class="menu-title">Settings</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                    @endcanany
                    <!--end:Settings Menu item-->

                    <!--begin:Settings Menu item-->
                    @canany(['user.view', 'user.create'])
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link" href="#" id="audit_logs_link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-notepad-bookmark fs-2"></i>
                                </span>
                                <span class="menu-title">Audit Logs</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                    @endcanany
                    <!--end:Settings Menu item-->

                </div>
                <!--end::Menu-->
            </div>
            <!--end::Scroll wrapper-->
        </div>
        <!--end::Menu wrapper-->
    </div>
    <!--end::sidebar menu-->

    <!--begin::Footer-->
    <div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
        <a href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="btn btn-flex flex-center btn-custom btn-danger overflow-hidden text-nowrap px-0 h-40px w-100"
            data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss-="click" title="Click to logout">
            <span class="btn-label">
                Logout
            </span>
            <i class="ki-outline ki-document btn-icon fs-2 m-0"></i>
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
    <!--end::Footer-->
</div>
