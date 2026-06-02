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

                    {{-- ----------------- Student & Admission Modules ----------------- --}}
                    <!--begin:Student Info Menu item-->
                    @canany(['students.view', 'guardians.view', 'siblings.view'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="student_info_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    {{-- <i class="ki-outline ki-address-book fs-2"></i> --}}
                                    <i class="las la-graduation-cap fs-1"></i>
                                </span>
                                <span class="menu-title">Student Info</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('students.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="all_students_link"
                                            href="{{ route('students.index') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">All
                                                Students</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan


                                <!--begin:Guardians Menu item-->
                                @can('guardians.view')
                                    <div class="menu-item">
                                        <a class="menu-link" id="guardians_link" href="{{ route('guardians.index') }}"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Guardians</span></a>
                                    </div>
                                @endcan
                                <!--end:Guardians Menu item-->

                                <!--begin:Siblings Menu item-->
                                @can('siblings.view')
                                    <div class="menu-item">
                                        <a class="menu-link" id="siblings_link" href="{{ route('siblings.index') }}"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Siblings</span></a>
                                    </div>
                                @endcan
                                <!--end:Siblings Menu item-->

                                <!--begin:Alumni Menu item-->
                                @can('students.view')
                                    <div class="menu-item">
                                        <a class="menu-link" id="alumni_link" href="{{ route('students.alumni.index') }}"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Alumni Students</span></a>
                                    </div>
                                @endcan
                                <!--end:Alumni Menu item-->

                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end: Student Info Menu item-->


                    <!--begin:Admission Menu item-->
                    @canany(['students.create', 'students.approve', 'students.promote', 'students.transfer'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="admission_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    {{-- <i class="fa-solid fa-building-columns fs-2"></i> --}}
                                    <i class="ki-outline ki-bank fs-2"></i>
                                </span>
                                <span class="menu-title">Admission</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('students.create')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="new_admission_link"
                                            href="{{ route('students.create') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">New
                                                Admission</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('students.view')
                                    <div class="menu-item">
                                        <a class="menu-link" id="pending_approval_link"
                                            href="{{ route('students.pending.index') }}">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot"></span>
                                            </span>

                                            <span class="menu-title">Pending Approval</span>

                                            <span class="menu-badge">
                                                <span class="badge badge-info">
                                                    {{ \App\Models\Student\Student::pending()->forUserBranch()->count() }}
                                                </span>
                                            </span>
                                        </a>
                                    </div>
                                @endcan


                                @can('students.promote')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="promote_students_link"
                                            href="{{ route('students.promote.index') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">Promote
                                                Students</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('students.transfer')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="transfer_students_link"
                                            href="{{ route('students.transfer.index') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">Transfer
                                                Students</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end: Admission Menu item-->


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
                                        <!--begin:Menu link--><a class="menu-link" id="class_link"
                                            href="{{ route('classnames.index') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Class</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan


                                @can('batches.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="batches_link" href="{{ route('batches.index') }}"><span
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
                                            href="{{ route('institutions.index') }}"><span class="menu-bullet"><span
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
                                        <a class="menu-link" id="attendance_link"
                                            href="{{ route('attendances.index') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span
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


                    <!--begin:Notes & Sheets Menu item-->
                    @canany(['sheets.view', 'notes.manage'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="sheets_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-note-2 fs-2"></i>
                                </span>
                                <span class="menu-title">Sheets</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('sheets.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="all_sheets_link" href="{{ route('sheets.index') }}"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">All
                                                Sheets</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->


                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="sheet_payments_link"
                                            href="{{ route('sheet-payments.index') }}">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">Sheet Payments</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany

                    @can('notes.distribute')
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="notes_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-notepad-bookmark fs-2"></i>
                                </span>
                                <span class="menu-title">Notes</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" id="all_distributions_link"
                                        href="{{ route('notes.distribution.index') }}"><span class="menu-bullet"><span
                                                class="bullet bullet-dot"></span></span><span class="menu-title">All
                                            Distributions</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->


                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link--><a class="menu-link" id="single_distribution_link"
                                        href="{{ route('notes.single.create') }}"><span class="menu-bullet"><span
                                                class="bullet bullet-dot"></span></span><span class="menu-title">Single
                                            Distribution</span></a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->


                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link--><a class="menu-link" id="bulk_distribution_link"
                                        href="{{ route('notes.bulk.create') }}"><span class="menu-bullet"><span
                                                class="bullet bullet-dot"></span></span><span class="menu-title">Bulk
                                            Distribution</span></a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcan
                    <!--end: Notes & Sheets Menu item-->


                    <!--begin:Invoices & Transactions-->
                    @canany(['invoices.view', 'transactions.view'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="payments_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-dollar fs-2"></i>
                                </span>
                                <span class="menu-title">Payments</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('invoices.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" href="{{ route('invoices.index') }}" id="invoices_link">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Invoices</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('transactions.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" href="{{ route('transactions.index') }}"
                                            id="transactions_link">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">Transactions</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end:Invoices & Transactions-->


                    <!--begin:Teachers Modules-->
                    @canany(['teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete',
                        'teachers.salary.manage'])
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
                                @can('teachers.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" href="{{ route('teachers.index') }}" id="teachers_link"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Teachers</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('teachers.salary.manage')
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
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end: Teachers Modules-->


                    <!--begin:Reports Menu-->
                    @can('reports.view')
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
                                @if (!auth()->user()->isAccountant())
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="finance_report_link"
                                            href="{{ route('reports.finance.index') }}">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">Finance Reports</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                @endif
                                <!--end:Finance Report item-->

                                <!--begin:Annual Due item-->
                                @if (auth()->user()->isAdmin())
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="annual_due_link"
                                            href="{{ route('reports.annual-due.index') }}">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">Annual Due</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                @endif
                                <!--end:Annual Due item-->

                                <!--begin:Cost Records item-->
                                @can('cost-records.view')
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="cost_records_link"
                                            href="{{ route('reports.cost-records.index') }}">
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
                                    <a class="menu-link" id="attendance_report_link"
                                        href="{{ route('reports.attendance.index') }}">
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
                                    {{-- <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="activity_logs_link" href="#">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">Activity</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div> --}}
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
                                    <a class="menu-link" id="settlements_link"
                                        href="{{ route('settlements.index') }}">
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
                                    <a class="menu-link" id="settlements_logs_link"
                                        href="{{ route('settlements.logs') }}">
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
                                        <a class="menu-link" id="single_sms_link"
                                            href="{{ route('sms.single.index') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">Send
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
                                        <a class="menu-link" id="sms_campaign_link"
                                            href="{{ route('send-campaign.index') }}">
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
                                        <a class="menu-link" id="sms_template_link"
                                            href="{{ route('sms.templates.index') }}">
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
                                        <a class="menu-link" id="sms_logs_link" href="{{ route('sms.logs.index') }}">
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
                    @canany(['users.manage', 'settings.manage', 'branches.manage'])
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link" href="{{ route('users.index') }}" id="settings_link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-setting-2 fs-2"></i>
                                </span>
                                <span class="menu-title">Settings</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                    @endcanany
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
            data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss-="click" title="Logout">
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
