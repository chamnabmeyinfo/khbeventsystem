<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KHB Booths Booking System')</title>
    
    @php
        try {
            $cdnSettings = \App\Models\Setting::getCDNSettings();
            $useCDN = $cdnSettings['use_cdn'] ?? true; // Default to true (CDN enabled)
        } catch (\Exception $e) {
            // If settings table doesn't exist or error occurs, default to CDN enabled
            $useCDN = true;
        }
    @endphp
    
    {{-- Performance Optimizations: Resource Hints --}}
    @if($useCDN)
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://code.jquery.com">
    <link rel="dns-prefetch" href="{{ url('/') }}">
    @else
    <link rel="preconnect" href="{{ url('/') }}">
    <link rel="dns-prefetch" href="{{ url('/') }}">
    @endif
    
    @if($useCDN)
    {{-- CDN CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {{-- Khmer Fonts from Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanuman:wght@100;300;400;700;900&family=Noto+Sans+Khmer:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @else
    {{-- Critical CSS: Preload essential stylesheets --}}
    <link rel="preload" href="{{ asset('vendor/bootstrap5/css/bootstrap.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{ asset('vendor/bootstrap5/css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    </noscript>
    
    {{-- Khmer Fonts from Google Fonts (for local CSS mode) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanuman:wght@100;300;400;700;900&family=Noto+Sans+Khmer:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    @endif

    {{-- Shared UX/CSS for both CDN and local Bootstrap --}}
    <link rel="stylesheet" href="{{ asset('css/device-optimized.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/tablet-optimized.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/desktop-optimized.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/modern-design-system.css') }}?v=5.1">
    <link rel="stylesheet" href="{{ asset('css/global-ux-consistency.css') }}?v=1.2">
    {{-- Mobile-only: link media matches viewport (resize-safe; avoids first-paint width-only JS) --}}
    <link rel="stylesheet" href="{{ asset('css/mobile-design-system.css') }}?v=4.1" media="(max-width: 768px)">
    <link rel="stylesheet" href="{{ asset('css/global-mobile-enhancements.css') }}?v=2" media="(max-width: 768px)">
    <link rel="stylesheet" href="{{ asset('css/mobile-cross-browser.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/responsive-mobile-first.css') }}?v=2">

    @if(!$useCDN)
    {{-- Performance Optimizer - Load early --}}
    <script src="{{ asset('js/performance-optimizer.js') }}" defer></script>
    @endif
    
    <link rel="stylesheet" href="{{ asset('css/modern-header.css') }}?v=4.0">
    <link rel="stylesheet" href="{{ asset('css/modern-sidebar.css') }}?v=3.4">
    <link rel="stylesheet" href="{{ asset('css/app-shell-layout.css') }}?v=1.5">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2-custom.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/app-loading-overlay.css') }}?v=2">
    
    @stack('styles')
</head>
<body class="app-shell @stack('body-class')">
<script>
// Viewport-based responsiveness: no redirects or URL params. Layout is driven by CSS
// media queries and, where server chooses a template, by User-Agent only.
(function() {
    // Optional: expose for any in-page logic that needs viewport width (e.g. feature flags).
    const w = window.innerWidth || screen.width;
    try { sessionStorage.setItem('viewport_width', w); } catch (e) {}
})();
</script>
    @include('partials.modern-header')

    <div class="layout-wrapper">
        @auth
            @include('partials.modern-sidebar')
        @endauth

        <main class="main-content-pushed container-fluid py-4" id="main-content">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any() && !request()->routeIs('login'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
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

@if($useCDN)
{{-- CDN JavaScript --}}
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/@panzoom/panzoom@4.5.1/dist/panzoom.min.js"></script>
@else
{{-- Performance: Preload critical JavaScript with high priority --}}
<link rel="preload" href="{{ asset('vendor/jquery/jquery-3.7.0.min.js') }}" as="script" fetchpriority="high">
<link rel="preload" href="{{ asset('vendor/bootstrap5/js/bootstrap.bundle.min.js') }}" as="script" fetchpriority="high">
    
{{-- jQuery MUST load synchronously so @stack('scripts') (e.g. booths canvas) has $ available --}}
<script src="{{ asset('vendor/jquery/jquery-3.7.0.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap5/js/bootstrap.bundle.min.js') }}" defer></script>

{{-- Performance Optimizer - Load early --}}
<script src="{{ asset('js/performance-optimizer.js') }}" defer></script>
@endif
    
{{-- Non-Critical JavaScript: Lazy load only when needed --}}
<script>
    (function() {
        'use strict';
        
        function loadScript(src, callback) {
            var script = document.createElement('script');
            script.src = src;
            script.async = true;
            if (callback) script.onload = callback;
            document.head.appendChild(script);
        }
        
        // Wait for DOM and jQuery to be ready
        function whenReady(callback) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback);
            } else {
                callback();
            }
        }
        
        whenReady(function() {
            // Wait for jQuery
            var checkJQuery = setInterval(function() {
                if (typeof jQuery !== 'undefined') {
                    clearInterval(checkJQuery);
                    
                    // Load SweetAlert2 (lightweight, commonly used)
                    loadScript('{{ asset('vendor/sweetalert2/js/sweetalert2.min.js') }}');
                    
                    // Load Panzoom only if needed (check for panzoom elements)
                    if (document.querySelector('[data-panzoom], .panzoom, canvas, svg')) {
                        loadScript('{{ asset('vendor/panzoom/panzoom.min.js') }}');
                    }
                }
            }, 50);
        });
    })();
</script>
    
    <!-- Custom Notification System -->
    <script>
    // Custom notification functions to replace browser alerts
    window.showNotification = function(message, type = 'info', title = null) {
        const config = {
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: type === 'error' ? 5000 : 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        };

        const icons = {
            'success': 'success',
            'error': 'error',
            'warning': 'warning',
            'info': 'info',
            'question': 'question'
        };

        return Swal.fire({
            ...config,
            icon: icons[type] || 'info',
            title: title || (type === 'success' ? 'Success!' : type === 'error' ? 'Error!' : 'Info'),
            text: message
        });
    };

    // Copy text to clipboard
    window.copyToClipboard = function(text) {
        // Try modern clipboard API first
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).then(function() {
                return true;
            }).catch(function(err) {
                console.error('Failed to copy using clipboard API:', err);
                return fallbackCopyToClipboard(text);
            });
        } else {
            // Fallback for older browsers
            return fallbackCopyToClipboard(text);
        }
    };

    // Fallback copy method for older browsers
    function fallbackCopyToClipboard(text) {
        return new Promise(function(resolve, reject) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                if (successful) {
                    resolve(true);
                } else {
                    reject(new Error('Copy command failed'));
                }
            } catch (err) {
                document.body.removeChild(textArea);
                reject(err);
            }
        });
    }

    // Replace alert() with custom notification
    window.customAlert = function(message, type = 'info', title = null) {
        const isError = type === 'error';
        const fullMessage = title ? (title + '\n\n' + message) : message;
        
        return Swal.fire({
            icon: type === 'error' ? 'error' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info',
            title: title || (type === 'success' ? 'Success!' : type === 'error' ? 'Error!' : type === 'warning' ? 'Warning!' : 'Information'),
            html: message + (isError ? '<br><br><button id="copyErrorBtn" class="btn btn-sm btn-outline-light mt-2" style="font-size: 12px;"><i class="fas fa-copy me-1"></i>Copy Error Message</button>' : ''),
            confirmButtonText: 'OK',
            confirmButtonColor: type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : type === 'warning' ? '#ffc107' : '#007bff',
            buttonsStyling: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: {
                popup: 'custom-swal-popup'
            },
            didOpen: function() {
                // Add copy button functionality for error messages
                if (isError) {
                    const copyBtn = document.getElementById('copyErrorBtn');
                    if (copyBtn) {
                        copyBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            copyToClipboard(fullMessage).then(function() {
                                // Show success feedback
                                const originalHtml = copyBtn.innerHTML;
                                copyBtn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
                                copyBtn.classList.remove('btn-outline-light');
                                copyBtn.classList.add('btn-success');
                                copyBtn.disabled = true;
                                
                                // Reset after 2 seconds
                                setTimeout(function() {
                                    copyBtn.innerHTML = originalHtml;
                                    copyBtn.classList.remove('btn-success');
                                    copyBtn.classList.add('btn-outline-light');
                                    copyBtn.disabled = false;
                                }, 2000);
                            }).catch(function(err) {
                                console.error('Failed to copy:', err);
                                // Show error feedback
                                const originalHtml = copyBtn.innerHTML;
                                copyBtn.innerHTML = '<i class="fas fa-times me-1"></i>Failed';
                                copyBtn.classList.remove('btn-outline-light');
                                copyBtn.classList.add('btn-danger');
                                
                                setTimeout(function() {
                                    copyBtn.innerHTML = originalHtml;
                                    copyBtn.classList.remove('btn-danger');
                                    copyBtn.classList.add('btn-outline-light');
                                }, 2000);
                            });
                        });
                    }
                }
            }
        });
    };

    // Replace confirm() with custom confirmation dialog
    window.customConfirm = function(message, title = 'Confirm Action', confirmText = 'Yes', cancelText = 'No') {
        return Swal.fire({
            title: title,
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            buttonsStyling: true,
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: {
                popup: 'custom-swal-popup'
            }
        }).then((result) => {
            return result.isConfirmed;
        });
    };

    // Standalone copy function that can be used anywhere
    // Usage: copyToClipboard('text to copy').then(() => console.log('Copied!'));
    window.copyTextToClipboard = function(text, showNotification = true) {
        return copyToClipboard(text).then(function() {
            if (showNotification) {
                showNotification('Text copied to clipboard!', 'success');
            }
            return true;
        }).catch(function(err) {
            console.error('Failed to copy:', err);
            if (showNotification) {
                showNotification('Failed to copy text. Please try manually.', 'error');
            }
            return false;
        });
    };

    // Override native alert and confirm (optional - can be removed if you prefer explicit calls)
    // Uncomment these if you want to automatically replace all alert() and confirm() calls
    /*
    window.alert = function(message) {
        return window.customAlert(message, 'info');
    };
    
    window.confirm = function(message) {
        return window.customConfirm(message, 'Confirm', 'OK', 'Cancel');
    };
    */
    </script>
    
    <style>
    /* Khmer Font Support */
    html, body {
        font-family: "Khmer OS Battambang", "Hanuman", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "KhmerOSBattambang", "Hanuman-Regular", "Noto Sans Khmer", "Khmer OS", "Khmer", sans-serif;
    }
    
    * {
        font-family: "Khmer OS Battambang", "Hanuman", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "KhmerOSBattambang", "Hanuman-Regular", "Noto Sans Khmer", "Khmer OS", "Khmer", sans-serif;
    }
    
    [lang="km"], 
    .khmer-text,
    *:lang(km) {
        font-family: "Khmer OS Battambang", "KhmerOSBattambang", "Hanuman", "Hanuman-Regular", "Noto Sans Khmer", "Khmer OS", "Khmer", sans-serif !important;
    }
    
    .custom-swal-popup {
        border-radius: 16px;
        box-shadow: 0 24px 48px -12px rgba(0,0,0,0.18);
    }
    
    #copyErrorBtn {
        transition: all 0.3s ease;
    }
    
    #copyErrorBtn:hover {
        transform: scale(1.05);
    }
    
    /* Make sure copy button is visible in error modals */
    .swal2-popup .btn-outline-light {
        border-color: rgba(255, 255, 255, 0.5);
        color: #fff;
    }
    
    .swal2-popup .btn-outline-light:hover {
        background-color: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.8);
    }
    </style>
    
    @stack('modals')
    
    @stack('scripts')
    
    <style>
    /* Mobile Adjustments for New UI */
    @media (max-width: 768px) {
        .main-content-pushed {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }
    
    /* Login page specific - ensure main doesn't interfere */
    body:has(.login-container) main.container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        min-height: 100vh !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    </style>
    @include('partials.app-loading-overlay')
</body>
</html>

