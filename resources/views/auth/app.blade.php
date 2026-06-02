<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->
@include('layouts.partials.head')
<!--end::Head-->
<!--begin::Body-->

<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center">
    <!--begin::Theme mode setup on page load-->
    @include('layouts.partials.theme_mode')
    <!--end::Theme mode setup on page load-->
    <!--begin::Root-->
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <!--begin::Page bg image-->
        <style>
            body {
                background-image: url('assets/media/auth/bg10.jpeg');
            }

            [data-bs-theme="dark"] body {
                background-image: url('assets/media/auth/bg10-dark.jpeg');
            }
        </style>
        <!--end::Page bg image-->
        <!--begin::Authentication - Layout -->
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <!--begin::Aside--> <!-- 1st div: Left sidebar (show second on mobile) -->
            <div class="d-flex flex-lg-row-fluid mb-0 pb-0">
                <!--begin::Content-->
                <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100">
                    <!--begin::Image--><a href="{{ route('home') }}">
                        <img class="theme-light-show mx-auto mw-100 w-100px w-lg-300px mb-10 mb-lg-20"
                            src="{{ asset('assets/img/icon.png') }}" alt="" />
                        <img class="theme-dark-show mx-auto mw-100 w-100px w-lg-300px mb-10 mb-lg-20"
                            src="{{ asset('assets/img/icon.png') }}" alt="" />
                    </a>
                    <!--end::Image-->
                    <!--begin::Title-->
                    <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">Unique Coaching Management System (UCMS)
                    </h1>
                    <!--end::Title-->
                    <!--begin::Text-->
                    <div class="text-gray-600 fs-base text-center fw-semibold ">UCMS (Unique Coaching Management System)
                        is an all-in-one solution to effortlessly manage<br> student data, attendance, exams, results,
                        payments, and more for coaching centers.<br> Designed for institutes like Unique Coaching, it
                        streamlines operations and boosts<br> academic efficiency with powerful features and real-time
                        insights.
                    </div>
                    <!--end::Text-->
                </div>
                <!--end::Content-->
            </div>
            <!--begin::Aside-->
            <!--begin::Body--> <!-- 2nd div: Right content (show first on mobile) -->
            <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12">
                <!--begin::Wrapper-->
                <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10 pt-0">
                    <!--begin::Content-->
                    <div class="d-flex flex-center flex-column align-items-stretch h-lg-0 w-350px w-md-400px">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-center flex-column flex-column-fluid pb-10">
                            @yield('content')
                        </div>
                        <!--end::Wrapper-->

                        <!--begin::Footer-->
                        {{-- <div class="mb-9">
                            <!--begin::Google link-->
                            <a href="https://prms.infinityfreeapp.com/uploads/prms-app.apk"
                                class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
                                <img alt="Logo"
                                    src="{{ asset('assets/media/svg/brand-logos/google-play-store.svg') }}"
                                    class="h-15px me-3" />পিআরএমএস অ্যাপটি ডাউনলোড করুন</a>
                            <!--end::Google link-->
                        </div> --}}
                        <!--end::Footer-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Wrapper-->
            </div>
            <!--end::Body-->
        </div>
        <!--end::Authentication - Layout-->
    </div>
    <!--end::Root-->
    <!--begin::Javascript-->
    <script>
        var hostUrl = "assets/";
    </script>
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <!--end::Global Javascript Bundle-->
    <!--begin::Custom Javascript(used for this page only)-->
    @stack('page-script')
    <!--end::Custom Javascript-->
    <!--end::Javascript-->

    @if (session('success') || session('warning') || session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                toastr.options = {
                    "closeButton": true,
                    "debug": false,
                    "newestOnTop": true,
                    "progressBar": true,
                    "positionClass": "toastr-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "5000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                };

                @if (session('success'))
                    toastr.success("{{ session('success') }}");
                @endif

                @if (session('warning'))
                    toastr.warning("{{ session('warning') }}");
                @endif

                @if (session('error'))
                    toastr.error("{{ session('error') }}");
                @endif
            });
        </script>
    @endif
</body>
<!--end::Body-->

</html>
