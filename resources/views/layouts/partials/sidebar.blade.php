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
                <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="#kt_app_sidebar_menu"
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
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="student_info_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    {{-- <i class="ki-outline ki-address-book fs-2"></i> --}}
                                    <i class="las la-user-friends fs-1"></i>
                                </span>
                                <span class="menu-title">Employee Info</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('employee.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="all_students_link" href="#"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">All
                                                Employees</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan


                                <!--begin:Employee Create Menu item-->
                                @can('employee.create')
                                    <div class="menu-item">
                                        <a class="menu-link" id="guardians_link" href="#"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Add New</span></a>
                                    </div>
                                @endcan
                                <!--end:Employee Create Menu item-->

                                <!--begin:Salary History Menu item-->
                                @can('salary.view')
                                    <div class="menu-item">
                                        <a class="menu-link" id="guardians_link" href="#"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Salary History</span></a>
                                    </div>
                                @endcan
                                <!--end:Salary History Menu item-->
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end: Employee Info Menu item-->

                    <!--begin:Academic Menu item-->
                    @canany(['institutions.view', 'classes.view', 'batches.manage', 'students.attendance.manage'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="academic_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-book fs-2"></i>
                                    {{-- <i class="fa-solid fa-school fs-2"></i> --}}
                                </span>
                                <span class="menu-title">Academic</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('classes.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="class_link" href="#"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Class</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan


                                @can('batches.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="batches_link" href="#"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Batches</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('institutions.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="institutions_link"
                                            href="#"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Institutions</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('students.attendance.manage')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="attendance_link" href="#"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Attendance</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end: Academic Menu item-->

                    <!--begin:Teachers Modules-->
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="teachers_menu">
                        <!--begin:Menu link-->
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="fa-solid fa-person-chalkboard fs-3"></i>
                            </span>
                            <span class="menu-title">Teachers</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <!--end:Menu link-->

                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            @cannot('teachers.view')
                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" href="#" id="teachers_link"><span
                                            class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                            class="menu-title">Teachers</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->
                            @endcannot

                            @cannot('teachers.salary.manage')
                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" href="#" id="salary_tracking_link">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot">
                                            </span>
                                        </span>
                                        <span class="menu-title">Salary Tracking</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->
                            @endcannot
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end: Teachers Modules-->


                    <!--begin:Reports Menu-->
                    @can('report.view')
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="reports_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-filter-tablet fs-2"></i>
                                </span>
                                <span class="menu-title">Reports</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                <!--begin:Finance Report item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="finance_report_link" href="#">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">Finance Reports</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                <!--end:Finance Report item-->

                                <!--begin:Annual Due item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" id="annual_due_link" href="#">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot">
                                            </span>
                                        </span>
                                        <span class="menu-title">Annual Due</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Annual Due item-->

                                <!--begin:Cost Records item-->
                                @can('cost-records.view')
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="cost_records_link" href="#">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">Cost Records</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                @endcan
                                <!--end:Cost Records item-->

                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" id="attendance_report_link" href="#">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot">
                                            </span>
                                        </span>
                                        <span class="menu-title">Attendance Reports</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>

                                @if (auth()->user()->isAdmin())
                                    <!--begin:Activity Logs item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="activity_logs_link" href="#">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">Activity</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Activity Logs item-->
                                @endif
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcan
                    <!--end:Reports Menu-->

                    <!--begin:Settlements Menu-->
                    @if (auth()->user()->isAdmin())
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="settlements_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-wallet fs-2"></i>
                                </span>
                                <span class="menu-title">Settlements</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                <!--begin:Settlement Index-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" id="settlements_link" href="#">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot">
                                            </span>
                                        </span>
                                        <span class="menu-title">User Settlements</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Settlement Index-->

                                <!--begin:Settlment Logs item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" id="settlements_logs_link" href="#">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot">
                                            </span>
                                        </span>
                                        <span class="menu-title">Wallet Logs</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Settlment Logs item-->
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endif
                    <!--end:Settlements Menu-->

                    <!--begin:SMS Menu-->
                    @canany(['sms.send', 'sms.logs.view', 'sms.templates.manage'])
                        <!--begin:SMS Modules-->
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="sms_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-sms fs-2"></i>
                                </span>
                                <span class="menu-title">SMS</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('sms.send')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="single_sms_link" href="#"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Send
                                                SMS</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                <!--begin:SMS Campaign item-->
                                @can('sms.campaign.view')
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="sms_campaign_link" href="#">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">SMS Campaigns</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                @endcan
                                <!--end:SMS Campaign item-->

                                <!--begin:SMS template item-->
                                @can('sms.templates.manage')
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="sms_template_link" href="#">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">SMS Templates</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                @endcan
                                <!--end:SMS template item-->

                                <!--begin:SMS Campaign item-->
                                @can('sms.logs.view')
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="sms_logs_link" href="#">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">SMS Logs</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                @endcan
                                <!--end:SMS Campaign item-->
                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--end: SMS Modules-->
                    @endcanany
                    <!--end:SMS Menu-->

                    <!--begin:Settings Menu-->
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
