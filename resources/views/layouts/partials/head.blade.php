<head>
    <title>@yield('title', 'Dashboard') - UCMS</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8" />
    <meta name="description" content="Unique Coaching Management System (UCMS) is an all-in-one solution for academic coaching centers, streamlining student admissions, attendance tracking, tuition and notes payment management, teacher scheduling, and sheet distribution. Designed for efficiency, UCMS ensures seamless operations with role-based access for Super Admins, Branch Managers, Accountants, Teachers, Guardians, and Students." />
    <meta name="keywords" content="coaching management system, academic management software, student management system, tuition management system, coaching center software, attendance tracking, payment management, teacher scheduling, notes distribution, sheet distribution system, role-based access, education ERP, UCMS, coaching automation, student fee management, guardian portal, online coaching management, school management system" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="Unique Coaching Management System (UCMS) - Complete Coaching Center Management Solution">
    <meta property="og:url" content="https://ashikur-rahman.com" />
    <meta property="og:site_name" content="UCMS by Ashikur Rahman" />
    <link rel="canonical" href="https://ashikur-rahman.com" />
    <link rel="shortcut icon" href="{{ asset('assets/img/favicon.ico') }}" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->

    <!--begin::Vendor Stylesheets(used for this page only)-->
    @stack('page-css')
    <!--end::Vendor Stylesheets-->

    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->
    <script>// Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }</script>
</head>