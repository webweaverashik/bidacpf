{{-- Usage inside a view:
     @section('header-title')
         @include('cpf-advances.partials.page-header', ['heading' => 'Advance Applications', 'crumbs' => ['CPF Operation', 'CPF Advance/Loan']])
     @endsection
--}}
<div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
    data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
    class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">{{ $heading }}</h1>
    <span class="h-20px border-gray-300 border-start mx-4"></span>
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
        @foreach ($crumbs as $crumb)
            @if (!$loop->last)
                <li class="breadcrumb-item text-muted"><a href="#" class="text-muted text-hover-primary">{{ $crumb }}</a></li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            @else
                <li class="breadcrumb-item text-muted">{{ $crumb }}</li>
            @endif
        @endforeach
    </ul>
</div>
