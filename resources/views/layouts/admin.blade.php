<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel - Event Management')</title>
    
    <!-- Bootstrap 5 CSS - Local -->
    <link href="{{ asset('vendor/bootstrap5/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Font Awesome - Local -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <!-- Global UX Consistency CSS -->
    <link rel="stylesheet" href="{{ asset('css/modern-design-system.css') }}?v=5.1">
    <link rel="stylesheet" href="{{ asset('css/global-ux-consistency.css') }}?v=1.2">
    <link rel="stylesheet" href="{{ asset('css/device-optimized.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/tablet-optimized.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/desktop-optimized.css') }}?v=1">
    {{-- Mobile-only sheets: media query so resize/orientation updates apply (no JS width gate) --}}
    <link rel="stylesheet" href="{{ asset('css/mobile-design-system.css') }}?v=4.1" media="(max-width: 768px)">
    <link rel="stylesheet" href="{{ asset('css/global-mobile-enhancements.css') }}?v=2" media="(max-width: 768px)">
    <link rel="stylesheet" href="{{ asset('css/mobile-cross-browser.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/responsive-mobile-first.css') }}?v=2">

    <link rel="stylesheet" href="{{ asset('css/modern-header.css') }}?v=4.0">
    <link rel="stylesheet" href="{{ asset('css/modern-sidebar.css') }}?v=3.4">
    <link rel="stylesheet" href="{{ asset('css/app-shell-layout.css') }}?v=1.5">
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/css/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2-custom.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('vendor/toastr/css/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-loading-overlay.css') }}?v=2">
    
    @stack('styles')
</head>
<body class="admin-body app-shell @stack('body-class')">
    @include('partials.modern-header')

    <div class="layout-wrapper">
        @auth
            @include('partials.modern-sidebar')
        @endauth

        <main class="main-content-pushed container-fluid py-4" id="main-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Bootstrap 5 JS - Local -->
    <script src="{{ asset('vendor/bootstrap5/js/bootstrap.bundle.min.js') }}"></script>
    <!-- jQuery - Local -->
    <script src="{{ asset('vendor/jquery/jquery-3.7.0.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/js/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('vendor/toastr/js/toastr.min.js') }}"></script>
    <script>
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: 3500,
                extendedTimeOut: 1000
            };
        }
    </script>
    <script src="{{ asset('js/image-upload.js') }}"></script>
    
    @stack('scripts')
    @include('partials.app-loading-overlay')
</body>
</html>

