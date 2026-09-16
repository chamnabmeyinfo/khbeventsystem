<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $companySettings = \App\Models\Setting::getCompanySettings();
        $appearanceSettings = \App\Models\Setting::getAppearanceSettings();
        try {
            $cdnSettings = \App\Models\Setting::getCDNSettings();
            $useCDN = $cdnSettings['use_cdn'] ?? true; // Default to true (CDN enabled)
        } catch (\Exception $e) {
            // If settings table doesn't exist or error occurs, default to CDN enabled
            $useCDN = true;
        }
    @endphp
    <title>@yield('title', $companySettings['company_name'] ?? 'KHB Booth System')</title>
    @if(($faviconUrl = \App\Helpers\AssetHelper::imageUrl($companySettings['company_favicon'] ?? null)))
        <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    @endif
    
    {{-- Performance Optimizations: Resource Hints --}}
    @if($useCDN)
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://code.jquery.com">
    <link rel="preconnect" href="https://code.ionicframework.com">
    <link rel="dns-prefetch" href="https://cdn.datatables.net">
    @else
    <link rel="preconnect" href="{{ url('/') }}">
    <link rel="dns-prefetch" href="{{ url('/') }}">
    @endif
    
    @if($useCDN)
    {{-- CDN CSS --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    {{-- Khmer Fonts from Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanuman:wght@100;300;400;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Khmer:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @else
    {{-- Local CSS: Preload essential stylesheets --}}
    <link rel="preload" href="{{ asset('vendor/adminlte/css/adminlte.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{ asset('vendor/adminlte/css/adminlte.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    </noscript>
    
    {{-- Non-Critical CSS: Load asynchronously --}}
    <link rel="preload" href="{{ asset('vendor/ionicons/css/ionicons.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('vendor/datatables/css/dataTables.bootstrap4.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('vendor/sweetalert2/css/sweetalert2.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('vendor/toastr/css/toastr.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('vendor/select2/css/select2.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('vendor/select2/css/select2-bootstrap4.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{ asset('vendor/ionicons/css/ionicons.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap4.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/css/sweetalert2.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/toastr/css/toastr.min.css') }}">
        <link href="{{ asset('vendor/select2/css/select2.min.css') }}" rel="stylesheet" />
        <link href="{{ asset('vendor/select2/css/select2-bootstrap4.min.css') }}" rel="stylesheet" />
    </noscript>
    
    {{-- Khmer Fonts from Google Fonts (for local CSS mode) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanuman:wght@100;300;400;700;900&family=Noto+Sans+Khmer:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @endif

    {{-- Shared KHB UI (CDN + local): previously only loaded in local-vendor mode --}}
    <link rel="stylesheet" href="{{ asset('css/device-optimized.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/tablet-optimized.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/desktop-optimized.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/modern-design-system.css') }}?v=5.1">
    <link rel="stylesheet" href="{{ asset('css/modern-sidebar.css') }}?v=3.4">
    <link rel="stylesheet" href="{{ asset('css/global-ux-consistency.css') }}?v=1.2">
    <link rel="stylesheet" href="{{ asset('css/admin-bootstrap-polish.css') }}?v=1">
    {{-- Align with layouts.admin / app: mobile sheets gated by viewport media (not JS width) --}}
    <link rel="stylesheet" href="{{ asset('css/mobile-design-system.css') }}?v=4.1" media="(max-width: 768px)">
    <link rel="stylesheet" href="{{ asset('css/global-mobile-enhancements.css') }}?v=2" media="(max-width: 768px)">
    <link rel="stylesheet" href="{{ asset('css/mobile-cross-browser.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/responsive-mobile-first.css') }}?v=2">

    {{-- Async CSS Loader Script --}}
    <script>
        !function(e){"use strict";var t=function(t,n,o){var i,r=e.document,a=r.createElement("link");if(n)i=n;else{var l=(r.body||r.getElementsByTagName("head")[0]).childNodes;i=l[l.length-1]}var d=r.styleSheets;a.rel="stylesheet",a.href=t,a.media="only x",function e(t){if(r.body)return t();setTimeout(function(){e(t)})}(function(){i.parentNode.insertBefore(a,n?i:i.nextSibling)});var f=function(e){for(var t=a.href,n=d.length;n--;)if(d[n].href===t)return e();setTimeout(function(){f(e)})};return a.addEventListener&&a.addEventListener("load",function(){this.media=o||"all"}),a.onloadcssdefined=f,f(function(){a.media!==o&&(a.media=o||"all")}),a};"undefined"!=typeof exports?exports.loadCSS=t:e.loadCSS=t}("undefined"!=typeof global?global:this);
    </script>
    
    <style>
        /* Khmer Font Support - Local Fonts First, then Google Fonts */
        @font-face {
            font-family: 'Khmer OS Battambang';
            src: local('Khmer OS Battambang'), 
                 local('KhmerOSBattambang'),
                 local('Khmer OS'),
                 local('Khmer');
            font-weight: normal;
            font-style: normal;
            unicode-range: U+1780-17FF, U+19E0-19FF;
        }
        
        @font-face {
            font-family: 'Hanuman';
            src: local('Hanuman'), 
                 local('Hanuman-Regular');
            font-weight: normal;
            font-style: normal;
            unicode-range: U+1780-17FF, U+19E0-19FF;
        }
        
        /* Modern UX/UI Enhancements */
        :root {
            --primary-color: {{ $appearanceSettings['primary_color'] ?? '#4e73df' }};
            --secondary-color: {{ $appearanceSettings['secondary_color'] ?? '#667eea' }};
            --success-color: {{ $appearanceSettings['success_color'] ?? '#1cc88a' }};
            --info-color: {{ $appearanceSettings['info_color'] ?? '#36b9cc' }};
            --warning-color: {{ $appearanceSettings['warning_color'] ?? '#f6c23e' }};
            --danger-color: {{ $appearanceSettings['danger_color'] ?? '#e74a3b' }};
            --sidebar-bg: {{ $appearanceSettings['sidebar_bg'] ?? '#224abe' }};
            --navbar-bg: {{ $appearanceSettings['navbar_bg'] ?? '#ffffff' }};
            --footer-bg: {{ $appearanceSettings['footer_bg'] ?? '#f8f9fc' }};
            --sidebar-width: 250px;
            /* Khmer Font Variables */
            --khmer-font-primary: 'Khmer OS Battambang', 'KhmerOSBattambang', 'Khmer OS', 'Khmer', sans-serif;
            --khmer-font-secondary: 'Hanuman', 'Hanuman-Regular', 'Noto Sans Khmer', 'Khmer OS Battambang', 'KhmerOSBattambang', sans-serif;
        }
        
        /* Apply Khmer fonts globally for Khmer Unicode characters */
        html, body {
            font-family: "Khmer OS Battambang", "Hanuman", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "KhmerOSBattambang", "Hanuman-Regular", "Noto Sans Khmer", "Khmer OS", "Khmer", sans-serif;
        }
        
        /* Khmer Unicode Range - Auto-detect and apply Khmer fonts */
        * {
            font-family: "Khmer OS Battambang", "Hanuman", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "KhmerOSBattambang", "Hanuman-Regular", "Noto Sans Khmer", "Khmer OS", "Khmer", sans-serif;
        }
        
        /* Ensure Khmer text uses Khmer fonts */
        [lang="km"], 
        .khmer-text,
        *:lang(km) {
            font-family: "Khmer OS Battambang", "KhmerOSBattambang", "Hanuman", "Hanuman-Regular", "Noto Sans Khmer", "Khmer OS", "Khmer", sans-serif !important;
        }
        
        /* Mobile Navigation & Responsive Styles */
        @media (max-width: 768px) {
            /* Body adjustments */
            body {
                font-size: 14px;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%) !important;
                padding-bottom: 80px !important;
            }
            
            /* Hide main header completely on mobile for app-style design */
            .main-header {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            /* Content wrapper - No margin/padding for full mobile app */
            .content-wrapper {
                margin: 0 !important;
                margin-top: 0 !important;
                padding: 0 !important;
                background: transparent !important;
            }
            
            /* Hide sidebar completely on mobile */
            .main-sidebar {
                display: none !important;
                visibility: hidden !important;
                width: 0 !important;
                height: 0 !important;
                position: absolute !important;
                left: -9999px !important;
            }
            
            body.sidebar-open .main-sidebar {
                left: 0 !important;
            }
            
            /* Ensure sidebar content is visible */
            .main-sidebar .sidebar {
                padding-bottom: 60px;
                overflow-y: auto;
                height: 100%;
                background: var(--sidebar-bg) !important;
            }
            
            /* Make sure all sidebar elements are visible */
            .main-sidebar,
            .main-sidebar .brand-link,
            .main-sidebar .user-panel,
            .main-sidebar .nav,
            .main-sidebar .nav-link {
                visibility: visible !important;
                opacity: 1 !important;
                display: block !important;
            }
            
            /* Brand link on mobile */
            .main-sidebar .brand-link {
                padding: 12px 15px !important;
                font-size: 1.1rem !important;
            }
            
            /* User panel on mobile */
            .main-sidebar .user-panel {
                padding: 12px 15px !important;
            }
            
            /* Navigation on mobile */
            .main-sidebar .nav-sidebar {
                display: block !important;
            }
            
            .main-sidebar .nav-sidebar > .nav-item {
                display: block !important;
            }
            
            .main-sidebar .nav-sidebar > .nav-item > .nav-link {
                display: flex !important;
                align-items: center;
            }
            
            /* Overlay when sidebar open */
            .sidebar-overlay {
                position: fixed;
                top: 56px;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.6);
                z-index: 1040 !important; /* Below sidebar but above content */
                display: none;
                backdrop-filter: blur(2px);
            }
            
            body.sidebar-open .sidebar-overlay {
                display: block !important;
            }
            
            /* Main header z-index */
            .main-header {
                z-index: 1060 !important; /* Highest - always on top */
            }
            
            /* Content wrapper margin */
            .content-wrapper {
                margin-left: 0 !important; /* No margin on mobile */
                margin-top: 0 !important;
                padding: 0 !important;
            }
            
            /* Hide content header on mobile */
            .content-header {
                display: none !important;
            }
            
            /* Ensure content area is full width */
            .content {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            /* Hamburger menu icon - Make it obvious */
            .navbar-nav .nav-link[data-widget="pushmenu"] {
                font-size: 1.5rem !important;
                padding: 8px 15px !important;
                color: #333 !important;
            }
            
            .navbar-nav .nav-link[data-widget="pushmenu"]:active {
                background: rgba(102, 126, 234, 0.1) !important;
                border-radius: 8px;
            }
            
            /* Add pulse animation to menu icon on first load */
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            
            .first-visit .navbar-nav .nav-link[data-widget="pushmenu"] i {
                animation: pulse 1s ease-in-out 3;
                color: #667eea !important;
            }
            
            /* Navbar - Touch friendly */
            .navbar-nav .nav-link {
                min-height: 44px;
                padding: 12px 15px !important;
            }
            
            /* Sidebar menu - Touch friendly */
            .nav-sidebar > .nav-item > .nav-link {
                padding: 12px 15px !important;
                font-size: 15px;
            }
            
            .nav-sidebar .nav-icon {
                font-size: 18px;
                margin-right: 10px;
            }
            
            /* Dropdowns - Full width */
            .dropdown-menu {
                position: fixed !important;
                left: 10px !important;
                right: 10px !important;
                width: calc(100% - 20px) !important;
                max-width: none !important;
            }
            
            /* Cards */
            .card {
                margin-bottom: 15px;
                border-radius: 8px;
            }
            
            .card-header {
                padding: 10px 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .card-title {
                font-size: 1.1rem;
            }
            
            /* Buttons - Touch friendly */
            .btn {
                min-height: 44px;
                padding: 10px 16px;
                font-size: 14px;
            }
            
            /* Modern Slide-Out Aside Menu - Mobile & Tablet */
            .modern-aside-menu {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 280px !important;
                max-width: 85vw !important;
                height: 100vh !important;
                height: 100dvh !important;
                background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%) !important;
                z-index: 10000 !important;
                transform: translateX(-100%) !important;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3) !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                -webkit-overflow-scrolling: touch !important;
                will-change: transform !important;
            }
            
            .modern-aside-menu.open {
                transform: translateX(0) !important;
            }
            
            /* Aside Menu Overlay */
            .modern-aside-overlay {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: rgba(0, 0, 0, 0.5) !important;
                backdrop-filter: blur(4px) !important;
                -webkit-backdrop-filter: blur(4px) !important;
                z-index: 9999 !important;
                opacity: 0 !important;
                visibility: hidden !important;
                transition: opacity 0.3s ease, visibility 0.3s ease !important;
            }
            
            .modern-aside-overlay.active {
                opacity: 1 !important;
                visibility: visible !important;
            }
            
            /* Aside Menu Header */
            .modern-aside-header {
                padding: 20px !important;
                background: rgba(255, 255, 255, 0.05) !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                position: sticky !important;
                top: 0 !important;
                z-index: 1 !important;
            }
            
            .modern-aside-header h3 {
                color: white !important;
                font-size: 20px !important;
                font-weight: 700 !important;
                margin: 0 !important;
            }
            
            .modern-aside-close {
                width: 40px !important;
                height: 40px !important;
                border-radius: 10px !important;
                background: rgba(255, 255, 255, 0.1) !important;
                border: none !important;
                color: white !important;
                font-size: 20px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                cursor: pointer !important;
                transition: all 0.2s ease !important;
            }
            
            .modern-aside-close:hover {
                background: rgba(255, 255, 255, 0.2) !important;
                transform: scale(1.1) !important;
            }
            
            /* Aside Menu Body */
            .modern-aside-body {
                padding: 16px !important;
            }
            
            /* Aside Menu Items */
            .modern-aside-item {
                display: flex !important;
                align-items: center !important;
                padding: 14px 16px !important;
                margin-bottom: 8px !important;
                border-radius: 12px !important;
                color: rgba(255, 255, 255, 0.9) !important;
                text-decoration: none !important;
                transition: all 0.2s ease !important;
                position: relative !important;
            }
            
            .modern-aside-item:hover {
                background: rgba(255, 255, 255, 0.1) !important;
                color: white !important;
                transform: translateX(4px) !important;
            }
            
            .modern-aside-item.active {
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
                color: white !important;
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3) !important;
            }
            
            .modern-aside-item i {
                width: 24px !important;
                font-size: 20px !important;
                margin-right: 12px !important;
            }
            
            .modern-aside-item span {
                font-size: 15px !important;
                font-weight: 500 !important;
            }
            
            /* Aside Menu Divider */
            .modern-aside-divider {
                height: 1px !important;
                background: rgba(255, 255, 255, 0.1) !important;
                margin: 16px 0 !important;
            }
            
            /* Aside Menu Section Title */
            .modern-aside-section-title {
                padding: 12px 16px 8px !important;
                color: rgba(255, 255, 255, 0.6) !important;
                font-size: 12px !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 1px !important;
            }
            
            /* Menu Toggle Button */
            .modern-menu-toggle {
                position: fixed !important;
                top: 16px !important;
                left: 16px !important;
                width: 48px !important;
                height: 48px !important;
                border-radius: 12px !important;
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
                color: white !important;
                border: none !important;
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 20px !important;
                cursor: pointer !important;
                z-index: 9998 !important;
                transition: all 0.3s ease !important;
                will-change: transform !important;
            }
            
            .modern-menu-toggle:hover {
                transform: scale(1.1) !important;
                box-shadow: 0 6px 16px rgba(99, 102, 241, 0.5) !important;
            }
            
            .modern-menu-toggle:active {
                transform: scale(0.95) !important;
            }
            
            /* Modern Mobile Bottom Nav - Always Sticky at Bottom - Mobile (≤768px) */
            .modern-mobile-nav {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                background: rgba(255, 255, 255, 0.95) !important;
                backdrop-filter: blur(20px) !important;
                -webkit-backdrop-filter: blur(20px) !important;
                border-top: 1px solid rgba(0, 0, 0, 0.1) !important;
                padding: 12px 16px !important;
                padding-bottom: calc(12px + env(safe-area-inset-bottom)) !important;
                z-index: 9999 !important;
                display: flex !important;
                justify-content: space-around !important;
                align-items: center !important;
                box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1) !important;
                will-change: transform !important;
                transform: translateZ(0) !important;
                margin: 0 !important;
                box-sizing: border-box !important;
            }
            
            /* Ensure body has padding at bottom for nav */
            body {
                padding-bottom: 80px !important;
            }
            
            /* Ensure content doesn't overlap nav */
            .content-wrapper,
            .content,
            main {
                padding-bottom: 100px !important;
            }
            
            .modern-mobile-nav-item {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                gap: 4px !important;
                padding: 8px 12px !important;
                border-radius: 12px !important;
                color: #6b7280 !important;
                text-decoration: none !important;
                transition: all 0.2s ease !important;
                min-width: 60px !important;
                will-change: transform !important;
                transform: translateZ(0) !important;
            }
            
            .modern-mobile-nav-item i {
                font-size: 22px !important;
            }
            
            .modern-mobile-nav-item span {
                font-size: 11px !important;
                font-weight: 600 !important;
            }
            
            .modern-mobile-nav-item.active {
                color: #6366f1 !important;
                background: rgba(99, 102, 241, 0.1) !important;
            }
            
            .modern-mobile-nav-item:active {
                transform: scale(0.9) translateZ(0) !important;
            }
            
            /* Modern Mobile FAB */
            .modern-mobile-fab {
                position: fixed !important;
                bottom: 100px !important;
                right: 20px !important;
                width: 64px !important;
                height: 64px !important;
                border-radius: 50% !important;
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
                color: white !important;
                box-shadow: 0 12px 32px rgba(99, 102, 241, 0.5) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 28px !important;
                border: none !important;
                cursor: pointer !important;
                z-index: 1040 !important;
                transition: all 0.3s ease !important;
                will-change: transform, box-shadow !important;
                transform: translateZ(0) !important;
            }
            
            .modern-mobile-fab:active {
                transform: scale(0.9) translateZ(0) !important;
                box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4) !important;
            }
            
            .btn-sm {
                min-height: 38px;
                padding: 8px 12px;
                font-size: 13px;
            }
            
            .btn-xs {
                min-height: 32px;
                padding: 6px 10px;
                font-size: 12px;
            }
            
            /* Form controls */
            .form-control, 
            .form-select,
            select {
                font-size: 16px !important; /* Prevent zoom on iOS */
                padding: 10px 12px;
                min-height: 44px;
            }
            
            .form-control-sm {
                font-size: 14px !important;
                min-height: 38px;
            }
            
            .form-label {
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 6px;
            }
            
            /* Tables - Horizontal scroll */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: -15px;
                padding: 15px;
            }
            
            .table {
                min-width: 600px;
                font-size: 13px;
            }
            
            .table th,
            .table td {
                padding: 8px;
                white-space: nowrap;
            }
            
            /* Pagination */
            .pagination {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .page-link {
                padding: 8px 12px;
                min-width: 40px;
            }
            
            /* Breadcrumb */
            .breadcrumb {
                font-size: 13px;
                padding: 8px 15px;
                flex-wrap: wrap;
            }
            
            /* Footer */
            .main-footer {
                padding: 10px 15px;
                font-size: 12px;
            }
            
            /* Modals - Full screen */
            .modal {
                padding: 0 !important;
            }
            
            .modal-dialog {
                margin: 0;
                max-width: 100%;
                height: 100vh;
            }
            
            .modal-content {
                height: 100vh;
                border-radius: 0;
                border: none;
            }
            
            .modal-header {
                padding: 12px 15px;
            }
            
            .modal-body {
                padding: 15px;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .modal-footer {
                padding: 10px 15px;
                flex-wrap: wrap;
            }
            
            .modal-footer .btn {
                margin: 5px;
                flex: 1;
                min-width: calc(50% - 10px);
            }
            
            /* Hide desktop-only elements */
            .d-none-mobile {
                display: none !important;
            }
            
            /* Show mobile-only elements */
            .d-block-mobile {
                display: block !important;
            }
            
            /* Reduce white space */
            .row {
                margin-left: -10px;
                margin-right: -10px;
            }
            
            .row > [class*='col-'] {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            /* Content header */
            .content-header {
                padding: 10px 15px !important;
            }
            
            .content-header h1 {
                font-size: 1.5rem !important;
            }
        }
        
        /* Tablet specific */
            /* Tablet - Slide-Out Menu */
            @media (min-width: 769px) and (max-width: 1024px) {
                .modern-aside-menu {
                    width: 320px !important;
                }
                
                .modern-aside-header {
                    padding: 24px !important;
                }
                
                .modern-aside-item {
                    padding: 16px 20px !important;
                    font-size: 16px !important;
                }
                
                .modern-aside-item i {
                    width: 28px !important;
                    font-size: 22px !important;
                }
            /* Modern Mobile Bottom Nav - Always Sticky at Bottom - Tablet */
            .modern-mobile-nav {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                background: rgba(255, 255, 255, 0.98) !important;
                backdrop-filter: blur(20px) !important;
                -webkit-backdrop-filter: blur(20px) !important;
                border-top: 1px solid rgba(0, 0, 0, 0.1) !important;
                padding: 14px 20px !important;
                padding-bottom: calc(14px + env(safe-area-inset-bottom)) !important;
                z-index: 9999 !important;
                display: flex !important;
                justify-content: space-around !important;
                align-items: center !important;
                box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1) !important;
                will-change: transform !important;
                transform: translateZ(0) !important;
                margin: 0 !important;
                box-sizing: border-box !important;
            }
            
            /* Ensure body has padding at bottom for nav on tablet */
            body {
                padding-bottom: 85px !important;
            }
            
            /* Ensure content doesn't overlap nav on tablet */
            .content-wrapper,
            .content,
            main {
                padding-bottom: 105px !important;
            }
            
            .modern-mobile-nav-item {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                gap: 6px !important;
                padding: 10px 16px !important;
                border-radius: 12px !important;
                color: #6b7280 !important;
                text-decoration: none !important;
                transition: all 0.2s ease !important;
                min-width: 70px !important;
                will-change: transform !important;
                transform: translateZ(0) !important;
            }
            
            .modern-mobile-nav-item i {
                font-size: 24px !important;
            }
            
            .modern-mobile-nav-item span {
                font-size: 12px !important;
                font-weight: 600 !important;
            }
            
            .modern-mobile-nav-item.active {
                color: #6366f1 !important;
                background: rgba(99, 102, 241, 0.1) !important;
            }
            
            .modern-mobile-nav-item:active {
                transform: scale(0.95) translateZ(0) !important;
            }
            
            /* Modern Mobile FAB - Tablet */
            .modern-mobile-fab {
                position: fixed !important;
                bottom: 110px !important;
                right: 24px !important;
                width: 68px !important;
                height: 68px !important;
                border-radius: 50% !important;
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
                color: white !important;
                box-shadow: 0 12px 32px rgba(99, 102, 241, 0.5) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 30px !important;
                border: none !important;
                cursor: pointer !important;
                z-index: 1040 !important;
                transition: all 0.3s ease !important;
                will-change: transform, box-shadow !important;
                transform: translateZ(0) !important;
            }
            
            .modern-mobile-fab:active {
                transform: scale(0.9) translateZ(0) !important;
                box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4) !important;
            }
            
            /* Tablet sidebar adjustments */
            .main-sidebar {
                width: 200px;
            }
            
            .content-wrapper {
                margin-left: 200px;
            }
            
            .nav-sidebar > .nav-item > .nav-link {
                padding: 10px 12px;
                font-size: 14px;
            }
        }
        
        /* Desktop - Hide Mobile Navigation (≥1025px) */
        @media (min-width: 1025px) {
            .modern-mobile-nav,
            .modern-mobile-fab,
            .modern-menu-toggle,
            .modern-aside-menu,
            .modern-aside-overlay {
                display: none !important;
            }
            
            /* Remove padding on desktop */
            body {
                padding-bottom: 0 !important;
            }
            
            .content-wrapper,
            .content,
            main {
                padding-bottom: 0 !important;
            }
        }
        
        /* Touch device improvements */
        @media (hover: none) and (pointer: coarse) {
            /* Remove all hover effects */
            .btn:hover,
            .nav-link:hover,
            .nav-item:hover {
                transform: none;
                background-color: inherit;
            }
            
            /* Active states instead */
            .btn:active,
            .nav-link:active {
                opacity: 0.7;
                transform: scale(0.98);
            }
            
            /* Scrollbars */
            * {
                -webkit-overflow-scrolling: touch;
            }
        }
        
        /* Landscape phone */
        @media (max-width: 768px) and (orientation: landscape) {
            .main-header {
                height: 50px;
            }
            
            .main-sidebar {
                top: 50px;
                height: calc(100vh - 50px);
            }
            
            .content-wrapper {
                margin-top: 50px !important;
            }
        }
        
        /* Loading overlay: see public/css/app-loading-overlay.css */
        
        /* Navbar - Clean Modern Design */
        .main-header {
            background: var(--navbar-bg);
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            padding: 0.75rem 1.5rem;
        }
        
        .navbar-nav .nav-link {
            color: #4a5568;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            margin: 0 0.25rem;
        }
        
        .navbar-nav .nav-link:hover {
            background: #f8f9fc;
            color: #667eea;
        }
        
        .navbar-nav .nav-link i {
            margin-right: 0.5rem;
        }
        
        /* Search Form */
        .form-inline {
            position: relative;
        }
        
        .form-control-navbar {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        
        .form-control-navbar:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Enhanced Search Results Styles */
        .search-type-filter .btn {
            border-radius: 6px;
            margin: 0 2px;
            font-size: 0.75rem;
            padding: 4px 8px;
            transition: all 0.2s ease;
        }
        
        .search-type-filter .btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .search-type-filter .btn:hover:not(.active) {
            background: #f3f4f6;
            border-color: #e5e7eb;
        }
        
        .search-result-item {
            transition: all 0.2s ease;
        }
        
        .search-result-item:hover {
            background-color: #f8f9fa !important;
            transform: translateX(2px);
        }
        
        .search-result-item.active {
            background-color: #e3f2fd !important;
        }
        
        .search-group-header {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        #searchResults {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .btn-navbar {
            border: 1px solid #e2e8f0;
            border-left: none;
            border-radius: 0 8px 8px 0;
            color: #4a5568;
            transition: all 0.2s ease;
        }
        
        .btn-navbar:hover {
            background: #f8f9fc;
            color: #667eea;
        }
        
        /* Card Enhancements */
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        /* Button Enhancements */
        .btn {
            transition: all 0.2s ease;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Form Enhancements */
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* ============================================
           ULTRA MODERN SIDEBAR DESIGN 2026 - CLEAN & CLEAR
           Minimalist Design with Perfect Spacing
           ============================================ */
        
        /* Sidebar Container - Clean Modern Design */
        .main-sidebar {
            background: linear-gradient(180deg, 
                var(--sidebar-bg) 0%, 
                color-mix(in srgb, var(--sidebar-bg) 90%, black) 50%,
                color-mix(in srgb, var(--sidebar-bg) 80%, black) 100%);
            box-shadow: 
                2px 0 24px rgba(0, 0, 0, 0.15),
                inset -1px 0 0 rgba(255, 255, 255, 0.03);
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            position: relative;
            overflow: hidden;
        }
        
        .main-sidebar > * {
            position: relative;
            z-index: 1;
        }
        
        /* Brand Logo - Clean & Minimal */
        .brand-link {
            background: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding: 1.25rem 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .brand-link:hover {
            background: rgba(255, 255, 255, 0.04);
            text-decoration: none;
        }
        
        .brand-link > .d-flex {
            width: 100%;
            align-items: center;
            gap: 0.75rem;
        }
        
        .brand-image-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 50px;
            height: 50px;
            /* Square container - maintains square shape */
            aspect-ratio: 1 / 1;
            min-width: 50px;
            min-height: 50px;
        }
        
        .brand-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px !important;
            height: 50px !important;
            min-width: 50px !important;
            min-height: 50px !important;
            border-radius: 50% !important;
            overflow: hidden;
            /* Perfect circle inside square wrapper */
            aspect-ratio: 1 / 1;
        }
        
        .brand-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }
        
        .brand-link:hover .brand-image {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .brand-text-wrapper {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .brand-text {
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            color: #ffffff;
            line-height: 1.3;
        }
        
        /* User Panel - Clean Card */
        .user-panel {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 1rem 1.25rem 1rem;
            transition: all 0.3s ease;
        }
        
        .user-panel:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(102, 126, 234, 0.2);
        }
        
        .user-panel .image {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-panel .image .avatar-wrapper {
            width: 50px !important;
            height: 50px !important;
        }
        
        .user-panel .image .avatar-image,
        .user-panel .image .avatar-placeholder {
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3) !important;
            transition: all 0.3s ease;
        }
        
        .user-panel:hover .image .avatar-wrapper {
            transform: scale(1.05);
        }
        
        .user-panel:hover .image .avatar-image,
        .user-panel:hover .image .avatar-placeholder {
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4) !important;
        }
        
        .user-panel .info a {
            font-weight: 600;
            font-size: 0.95rem;
            color: #ffffff;
            text-decoration: none;
        }
        
        .user-panel .info small {
            color: rgba(255, 255, 255, 0.65) !important;
            font-weight: 400;
            font-size: 0.8rem;
        }
        
        /* Navigation Links - Clean & Clear */
        .nav-link {
            transition: all 0.25s ease;
            border-radius: 10px;
            margin: 0.25rem 0.75rem;
            padding: 0.75rem 1rem;
            position: relative;
            background: transparent;
            border: none;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 0;
            width: 3px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            border-radius: 0 3px 3px 0;
            transition: height 0.25s ease;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.04);
            transform: translateX(4px);
        }
        
        .nav-link:hover::before {
            height: 60%;
        }
        
        .nav-link.active {
            background: rgba(102, 126, 234, 0.15);
            color: #ffffff;
            font-weight: 600;
        }
        
        .nav-link.active::before {
            height: 70%;
            box-shadow: 0 0 8px rgba(102, 126, 234, 0.5);
        }
        
        /* Navigation Icons */
        .nav-icon {
            width: 20px;
            text-align: center;
            margin-right: 0.75rem;
            font-size: 1rem;
            transition: all 0.25s ease;
            display: inline-block;
        }
        
        .nav-link:hover .nav-icon {
            transform: scale(1.1);
            color: #667eea;
        }
        
        .nav-link.active .nav-icon {
            color: #ffffff;
        }
        
        /* Section Headers - Clean Design */
        .nav-header {
            color: rgba(255, 255, 255, 0.5) !important;
            font-size: 0.65rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px;
            padding: 1rem 1.5rem 0.5rem 1.5rem !important;
            margin-top: 0.5rem;
            position: relative;
        }
        
        .nav-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 1.5rem;
            right: 1.5rem;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent 0%,
                rgba(255, 255, 255, 0.1) 50%,
                transparent 100%);
        }
        
        .nav-header i {
            margin-right: 0.5rem;
            font-size: 0.75rem;
            color: rgba(102, 126, 234, 0.6);
        }
        
        /* Treeview */
        .nav-treeview {
            padding-left: 0.5rem;
            margin-top: 0.25rem;
        }
        
        .nav-treeview .nav-link {
            padding-left: 2.5rem;
            font-size: 0.875rem;
            margin: 0.2rem 0.75rem;
        }
        
        .has-treeview > .nav-link {
            font-weight: 500;
        }
        
        .has-treeview > .nav-link .right {
            transition: transform 0.3s ease;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.75rem;
        }
        
        .has-treeview.menu-open > .nav-link .right {
            transform: rotate(-90deg);
            color: #667eea;
        }
        
        /* Badge */
        .nav-link .badge {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 2px 8px rgba(245, 87, 108, 0.3);
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* Scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.5);
        }
        
        /* Spacing & Typography */
        .sidebar .nav {
            padding: 0.5rem 0;
        }
        
        .nav-item {
            margin-bottom: 0.15rem;
        }
        
        .nav-link p {
            transition: all 0.25s ease;
            margin: 0;
            font-size: 0.9rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.85);
        }
        
        .nav-link.active p {
            color: #ffffff;
            font-weight: 600;
        }
        
        .nav-link:hover p {
            color: #ffffff;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .nav-link {
                margin: 0.2rem 0.5rem;
                padding: 0.65rem 0.85rem;
            }
            
            .nav-header {
                padding: 0.85rem 1.25rem 0.4rem 1.25rem !important;
                font-size: 0.6rem !important;
            }
            
            .user-panel {
                margin: 0.75rem 0.5rem 1rem 0.5rem;
                padding: 0.85rem;
            }
        }
        
        /* Table Enhancements */
        .table {
            border-radius: 0.35rem;
            overflow: hidden;
        }
        
        .table thead {
            background-color: #f8f9fc;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fc;
            transform: scale(1.01);
        }
        
        /* Badge Enhancements */
        .badge {
            padding: 0.35em 0.65em;
            font-weight: 600;
        }
        
        /* Alert Enhancements */
        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 0.35rem;
        }
        
        .alert-success {
            border-left-color: var(--success-color);
        }
        
        .alert-danger {
            border-left-color: var(--danger-color);
        }
        
        .alert-warning {
            border-left-color: var(--warning-color);
        }
        
        .alert-info {
            border-left-color: var(--info-color);
        }
        
        /* Smooth Transitions */
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
        }
        
        /* ============================================
           GLOBAL MODERN FORM DESIGN SYSTEM
           Applies modern design to all form controls
           ============================================ */
        
        /* Modern Form Controls - Global Application */
        .form-control:not(.form-control-modern):not([class*="custom"]),
        .form-control-modern,
        select.form-control:not(.form-control-modern),
        textarea.form-control:not(.form-control-modern) {
            border-radius: 12px;
            border: 2px solid rgba(102, 126, 234, 0.1);
            padding: -0.25rem 1rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            background: white;
            width: 100%;
            max-width: 100%;
            overflow: visible;
            white-space: normal;
            word-wrap: break-word;
        }
        
        .form-control:not(.form-control-modern):focus,
        .form-control-modern:focus,
        select.form-control:not(.form-control-modern):focus,
        textarea.form-control:not(.form-control-modern):focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
            transform: translateY(-1px);
        }
        
        /* Ensure form-control-modern has priority */
        .form-control-modern {
            border-radius: 12px !important;
            border: 2px solid rgba(102, 126, 234, 0.1) !important;
            padding: -0.25rem 1rem !important;
            transition: all 0.3s ease !important;
        }
        
        .form-control-modern:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
            transform: translateY(-1px);
        }
        
        /* Select elements styling */
        select.form-control:not(.form-control-modern),
        select.form-control-modern {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        
        /* Textarea specific styling */
        textarea.form-control:not(.form-control-modern),
        textarea.form-control-modern {
            min-height: 100px;
            resize: vertical;
            overflow: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>

    {{-- Mobile sheets: loaded earlier with media="(max-width: 768px)" — do not duplicate here (would apply on desktop). --}}

    <link rel="stylesheet" href="{{ asset('css/modern-header.css') }}?v=4.0">
    <link rel="stylesheet" href="{{ asset('css/app-loading-overlay.css') }}?v=2">
    
    @stack('styles')

    {{-- Pinch-to-zoom allowed on mobile (viewport + no gesture blocking) --}}
</head>
<body class="hold-transition sidebar-mini layout-fixed @stack('body-class')">
<!-- Modern Slide-Out Aside Menu - Mobile & Tablet -->
<div class="modern-aside-overlay" id="asideOverlay"></div>
<aside class="modern-aside-menu d-md-none d-lg-none" id="asideMenu">
    <div class="modern-aside-header">
        <h3><i class="fas fa-bars me-2"></i>Menu</h3>
        <button class="modern-aside-close" id="asideCloseBtn" aria-label="Close menu">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="modern-aside-body">
        @php
            try {
                $moduleSettings = \App\Models\Setting::getModuleDisplaySettings();
            } catch (\Exception $e) {
                $moduleSettings = [
                    'dashboard' => ['mobile' => true, 'tablet' => true],
                    'booths' => ['mobile' => true, 'tablet' => true],
                    'bookings' => ['mobile' => true, 'tablet' => true],
                    'clients' => ['mobile' => true, 'tablet' => true],
                    'settings' => ['mobile' => true, 'tablet' => true],
                ];
            }
        @endphp
        
        @if(($moduleSettings['dashboard']['mobile'] ?? true) || ($moduleSettings['dashboard']['tablet'] ?? true))
        <a href="{{ route('dashboard') }}" class="modern-aside-item" data-route="dashboard">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        @endif
        
        @if(($moduleSettings['booths']['mobile'] ?? true) || ($moduleSettings['booths']['tablet'] ?? true))
        <a href="{{ route('booths.index') }}" class="modern-aside-item" data-route="booths">
            <i class="fas fa-store"></i>
            <span>Booths</span>
        </a>
        @endif
        
        @if(($moduleSettings['bookings']['mobile'] ?? true) || ($moduleSettings['bookings']['tablet'] ?? true))
        <a href="{{ route('books.index') }}" class="modern-aside-item" data-route="books">
            <i class="fas fa-calendar-check"></i>
            <span>Bookings</span>
        </a>
        @endif
        
        @if(($moduleSettings['clients']['mobile'] ?? true) || ($moduleSettings['clients']['tablet'] ?? true))
        <a href="{{ route('clients.index') }}" class="modern-aside-item" data-route="clients">
            <i class="fas fa-users"></i>
            <span>Clients</span>
        </a>
        @endif
        
        @if(($moduleSettings['reports']['mobile'] ?? true) || ($moduleSettings['reports']['tablet'] ?? true))
        <a href="{{ route('reports.index') }}" class="modern-aside-item" data-route="reports">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        @endif
        
        @if(($moduleSettings['finance']['mobile'] ?? true) || ($moduleSettings['finance']['tablet'] ?? true))
        <a href="{{ route('finance.dashboard') }}" class="modern-aside-item" data-route="finance">
            <i class="fas fa-dollar-sign"></i>
            <span>Finance</span>
        </a>
        @endif
        
        <div class="modern-aside-divider"></div>
        
        @if(($moduleSettings['settings']['mobile'] ?? true) || ($moduleSettings['settings']['tablet'] ?? true))
        <a href="{{ route('settings.index') }}" class="modern-aside-item" data-route="settings">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        @endif
        <a href="{{ route('documentation.index') }}" class="modern-aside-item" data-route="documentation">
            <i class="fas fa-book"></i>
            <span>Documentation</span>
        </a>
        @auth
        <div class="modern-aside-divider"></div>
        <div class="modern-aside-section-title">
            <i class="fas fa-user me-2"></i>Account
        </div>
        <a href="{{ route('dashboard') }}" class="modern-aside-item">
            <i class="fas fa-user-circle"></i>
            <span>{{ auth()->user()->username }}</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="modern-aside-item w-100 text-start" style="border: none; background: none; cursor: pointer;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </form>
        @endauth
    </div>
</aside>

<div class="wrapper">

    <!-- Navbar -->
    @include('partials.modern-header')
    <!-- /.navbar -->

    @include('partials.modern-sidebar')

    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark">@yield('page-title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item active">@yield('breadcrumb', 'Dashboard')</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('warning') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle mr-2"></i>{{ session('info') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @yield('content')
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer">
        <strong>Copyright &copy; {{ date('Y') }} <a href="https://www.khbmedia.asia/">KHB Media</a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 2.0.0
        </div>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

@if($useCDN)
{{-- CDN JavaScript --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@else
{{-- Performance: Preload critical JavaScript with high priority --}}
<link rel="preload" href="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}" as="script" fetchpriority="high">
<link rel="preload" href="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}" as="script" fetchpriority="high">

{{-- Critical JavaScript: Load with defer (non-blocking) --}}
<script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}" defer></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}" defer></script>
<script src="{{ asset('vendor/adminlte/js/adminlte.min.js') }}" defer></script>

{{-- Performance Optimizer - Load early --}}
<script src="{{ asset('js/performance-optimizer.js') }}" defer></script>

{{-- Non-Critical JavaScript: Load asynchronously (only when needed) --}}
<script>
// Lazy load non-critical scripts
(function() {
    'use strict';
    
    // Function to load script dynamically
    function loadScript(src, callback) {
        var script = document.createElement('script');
        script.src = src;
        script.async = true;
        if (callback) {
            script.onload = callback;
        }
        document.head.appendChild(script);
    }
    
    // Load scripts after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Load jQuery UI only if needed (check for elements that use it)
            if (document.querySelector('.ui-widget, .ui-dialog, [data-toggle="tooltip"]')) {
                loadScript('{{ asset('vendor/jquery-ui/jquery-ui.min.js') }}');
            }
            
            // Load DataTables only if tables exist
            if (document.querySelector('table.dataTable, .data-table')) {
                loadScript('{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}', function() {
                    loadScript('{{ asset('vendor/datatables/js/dataTables.bootstrap4.min.js') }}');
                });
            }
            
            // Load SweetAlert2 (lightweight, load early)
            loadScript('{{ asset('vendor/sweetalert2/js/sweetalert2.min.js') }}');
            
            // Load Toastr (lightweight, load early)
            loadScript('{{ asset('vendor/toastr/js/toastr.min.js') }}');
            
            // Load Moment.js only if date formatting is needed
            if (document.querySelector('[data-moment], .moment, [data-date]')) {
                loadScript('{{ asset('vendor/moment/moment.min.js') }}');
            }
            
            // Load Select2 only if select elements with select2 class exist
            if (document.querySelector('select.select2, .select2')) {
                loadScript('{{ asset('vendor/select2/js/select2.min.js') }}');
            }
        });
    } else {
        // DOM already loaded, load immediately
        loadScript('{{ asset('vendor/sweetalert2/js/sweetalert2.min.js') }}');
        loadScript('{{ asset('vendor/toastr/js/toastr.min.js') }}');
    }
})();
</script>
@endif
<!-- Image Upload Handler -->
<script src="{{ asset('js/image-upload.js') }}"></script>

@stack('modals')

@stack('scripts')

<script>
// Push notifications config (for Web Push)
@php
    $pushConfig = [
        'enabled' => \App\Models\Setting::getValue('push_notifications_enabled', true) && (bool) config('notifications.push.enabled', true),
        'vapidPublicKey' => \App\Models\Setting::getValue('push_vapid_public_key', '') ?: (string) config('notifications.push.vapid_public_key', ''),
    ];
@endphp
window.PUSH_CONFIG = @json($pushConfig);
window.PUSH_SUBSCRIBE_URL = '{{ route("notifications.push-subscribe") }}';
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('{{ asset("sw.js") }}').catch(function() {});
}
function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = atob(base64);
    var output = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; ++i) output[i] = rawData.charCodeAt(i);
    return output;
}
window.subscribePush = function() {
    if (!window.PUSH_CONFIG.enabled || !window.PUSH_CONFIG.vapidPublicKey) {
        if (typeof toastr !== 'undefined') toastr.warning('Push notifications are not configured.');
        return Promise.reject(new Error('Push not configured'));
    }
    return navigator.serviceWorker.ready.then(function(reg) {
        return reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(window.PUSH_CONFIG.vapidPublicKey)
        });
    }).then(function(sub) {
        function abToBase64Url(buf) {
            var u8 = new Uint8Array(buf);
            var bin = '';
            for (var i = 0; i < u8.length; i++) bin += String.fromCharCode(u8[i]);
            return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
        }
        var payload = {
            endpoint: sub.endpoint,
            keys: { p256dh: abToBase64Url(sub.getKey('p256dh')), auth: abToBase64Url(sub.getKey('auth')) },
            contentEncoding: 'aesgcm'
        };
        return fetch(window.PUSH_SUBSCRIBE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });
    }).then(function(r) {
        if (!r.ok) throw new Error('Subscribe failed');
        return r.json();
    });
};
// Configure Toastr
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
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

// Loading overlay: window.showLoading / hideLoading from app-loading-overlay partial at end of body

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    $('.alert').fadeOut('slow', function() {
        $(this).remove();
    });
}, 5000);

// Update notification badge with real-time alerts
function updateNotificationBadge() {
    fetch('{{ route("notifications.unread-count") }}')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notification-badge');
            if (badge) {
                const oldCount = parseInt(badge.textContent) || 0;
                const newCount = data.count || 0;
                
                if (newCount > 0) {
                    badge.textContent = newCount;
                    badge.style.display = 'block';
                    
                    // Show alert if new notifications arrived
                    if (newCount > oldCount && oldCount > 0) {
                        showNotificationAlert(newCount - oldCount);
                    }
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error updating notification badge:', error);
        });
}

// Show real-time notification alert
function showNotificationAlert(count) {
    const message = count === 1 
        ? 'You have a new notification!' 
        : `You have ${count} new notifications!`;
    
    // Show toastr notification
    if (typeof toastr !== 'undefined') {
        toastr.info(message, 'New Notification', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            onclick: function() {
                window.location.href = '{{ route("notifications.index") }}';
            }
        });
    }
}

// Update badge on page load and every 15 seconds for real-time updates
updateNotificationBadge();
setInterval(updateNotificationBadge, 15000);

// Messages (communications) unread badge
function updateMessagesBadge() {
    var badge = document.getElementById('messages-badge');
    if (!badge) return;
    fetch('{{ route("communications.unread-count") }}')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var n = data.count || 0;
            badge.textContent = n;
            badge.style.display = n > 0 ? 'inline-block' : 'none';
        })
        .catch(function() {});
}
updateMessagesBadge();
setInterval(updateMessagesBadge, 15000);

// Global Search - Enhanced Dynamic Search
let searchTimeout;
let currentSearchType = 'all';
let isSearching = false;

// Search Type Selection
$('.search-type-btn').on('click', function() {
    $('.search-type-btn').removeClass('active');
    $(this).addClass('active');
    currentSearchType = $(this).data('type');
    
    // Trigger search with new type
    const query = $('#globalSearchInput').val();
    if (query && query.length >= 2) {
        performSearch(query, currentSearchType);
    }
});

// Enhanced Search Function
function performSearch(query, type = 'all') {
    if (isSearching) return;
    
    const resultsContent = $('#searchResultsContent');
    const resultsDiv = $('#searchResults');
    
    // Show loading state
    isSearching = true;
    resultsContent.html(`
        <div class="text-center p-4">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2 mb-0 text-muted">Searching...</p>
        </div>
    `);
    resultsDiv.show();
    
    // Perform search
    fetch('{{ route("search") }}?q=' + encodeURIComponent(query) + '&type=' + encodeURIComponent(type))
        .then(response => response.json())
        .then(data => {
            isSearching = false;
            
            if (data.results && data.results.length > 0) {
                let html = '';
                
                // Group results by type
                const groupedResults = {};
                data.results.forEach(function(result) {
                    if (!groupedResults[result.type]) {
                        groupedResults[result.type] = [];
                    }
                    groupedResults[result.type].push(result);
                });
                
                // Render grouped results
                Object.keys(groupedResults).forEach(function(type) {
                    const typeLabel = {
                        'booth': 'Booths',
                        'client': 'Clients',
                        'booking': 'Bookings',
                        'user': 'Users'
                    }[type] || type;
                    
                    html += `<div class="search-group-header px-3 py-2 bg-light border-bottom">
                        <small class="text-muted font-weight-bold"><i class="fas fa-${groupedResults[type][0].icon.replace('fas fa-', '')} mr-1"></i>${typeLabel}</small>
                    </div>`;
                    
                    groupedResults[type].forEach(function(result) {
                        html += `<a href="${result.url}" class="dropdown-item search-result-item" style="padding: 12px 16px; border-bottom: 1px solid #f0f0f0;">
                            <div class="d-flex align-items-start">
                                <div class="search-icon mr-3" style="color: #667eea; font-size: 18px; margin-top: 2px;">
                                    <i class="${result.icon}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="font-weight-bold" style="color: #2d3748; margin-bottom: 4px;">${result.title}</div>
                                    <div class="text-muted" style="font-size: 0.85rem; line-height: 1.4;">${result.description}</div>
                                </div>
                            </div>
                        </a>`;
                    });
                });
                
                resultsContent.html(html);
            } else {
                resultsContent.html(`
                    <div class="text-center p-4">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <p class="mb-0 text-muted">No results found for "${query}"</p>
                        <small class="text-muted">Try a different search term</small>
                    </div>
                `);
            }
        })
        .catch(error => {
            isSearching = false;
            console.error('Search error:', error);
            resultsContent.html(`
                <div class="text-center p-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p class="mb-0">Error searching. Please try again.</p>
                </div>
            `);
        });
}

// Search Input Handler
$('#globalSearchInput').on('input', function() {
    const query = $(this).val().trim();
    const resultsDiv = $('#searchResults');
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        if (query.length === 0) {
            $('#searchResultsContent').html(`
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-search fa-2x mb-2"></i>
                    <p class="mb-0">Start typing to search...</p>
                </div>
            `);
        }
        return;
    }
    
    // Debounce search
    searchTimeout = setTimeout(function() {
        performSearch(query, currentSearchType);
    }, 300);
});

// Show search results on focus
$('#globalSearchInput').on('focus', function() {
    const query = $(this).val().trim();
    if (query.length >= 2) {
        $('#searchResults').show();
    } else {
        $('#searchResults').show();
    }
});

// Handle form submission
$('#globalSearchForm').on('submit', function(e) {
    e.preventDefault();
    const query = $('#globalSearchInput').val().trim();
    if (query.length >= 2) {
        performSearch(query, currentSearchType);
    }
});

// Hide search results when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('#globalSearchForm').length) {
        $('#searchResults').hide();
    }
});

// Keyboard navigation
$('#globalSearchInput').on('keydown', function(e) {
    const resultsDiv = $('#searchResults');
    const visibleItems = resultsDiv.find('.search-result-item:visible');
    
    if (e.key === 'ArrowDown' && visibleItems.length > 0) {
        e.preventDefault();
        const firstItem = visibleItems.first();
        firstItem.focus();
        firstItem.addClass('active');
    }
});

// Handle Enter key on search result items
$(document).on('keydown', '.search-result-item', function(e) {
    if (e.key === 'Enter') {
        window.location.href = $(this).attr('href');
    }
});

// Show toast notifications from session
@if(session('success'))
    toastr.success('{{ session('success') }}', 'Success');
@endif

@if(session('error'))
    toastr.error('{{ session('error') }}', 'Error');
@endif

@if(session('warning'))
    toastr.warning('{{ session('warning') }}', 'Warning');
@endif

@if(session('info'))
    toastr.info('{{ session('info') }}', 'Information');
@endif

// Form submission loading indicator
$(document).ready(function() {
    $('form').on('submit', function() {
        showLoading();
    });
    
    // AJAX form submissions
    $(document).ajaxStart(function() {
        showLoading();
    }).ajaxStop(function() {
        hideLoading();
    });
});

// Resolve conflict in jQuery UI tooltip with Bootstrap tooltip
$.widget.bridge('uibutton', $.ui.button);

// Mobile Navigation Enhancement
(function() {
    'use strict';
    
    function initMobileMenu() {
        // Create overlay element if it doesn't exist
        if (!document.querySelector('.sidebar-overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
            
            overlay.addEventListener('click', function() {
                document.body.classList.remove('sidebar-open');
            });
        }
        
        // Handle menu toggle button
        const menuToggle = document.querySelector('[data-widget="pushmenu"]');
        if (menuToggle) {
            menuToggle.removeEventListener('click', handleMenuClick);
            menuToggle.addEventListener('click', handleMenuClick);
        }
        
        // Close sidebar when navigation link clicked on mobile
        const navLinks = document.querySelectorAll('.main-sidebar .nav-link');
        
        navLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    setTimeout(function() {
                        document.body.classList.remove('sidebar-open');
                    }, 200);
                }
            });
        });
        
        // Close sidebar on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
                document.body.classList.remove('sidebar-open');
            }
        });
    }
    
    // Menu click handler
    function handleMenuClick(e) {
        if (window.innerWidth <= 768) {
            e.preventDefault();
            e.stopPropagation();
            document.body.classList.toggle('sidebar-open');
        }
    }
    
    function setVh() {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', vh + 'px');
    }
    
    // Initialize on DOM ready
    $(document).ready(function() {
        initMobileMenu();
        setVh();
        
        if (window.innerWidth <= 768) {
            document.body.classList.add('mobile-device');
        }
        
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
        }
        
        // Force sidebar to be visible initially on desktop
        if (window.innerWidth > 768) {
            document.body.classList.remove('sidebar-open');
        }
    });
    
    // Handle window resize
    $(window).on('resize', function() {
        setVh();
        
        // Close sidebar on resize to desktop
        if (window.innerWidth > 768) {
            document.body.classList.remove('sidebar-open');
        }
    });
})();
</script>

<!-- Modern Mobile Bottom Navigation - Global (Always show on Mobile & Tablet, always sticky at bottom) -->
<div class="modern-mobile-nav-wrapper">
    <!-- Menu Toggle Button for Aside Menu -->
    <button class="modern-menu-toggle" id="asideMenuToggle" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="modern-mobile-nav">
        @php
            try {
                $moduleSettings = \App\Models\Setting::getModuleDisplaySettings();
            } catch (\Exception $e) {
                $moduleSettings = [
                    'dashboard' => ['mobile' => true, 'tablet' => true],
                    'booths' => ['mobile' => true, 'tablet' => true],
                    'bookings' => ['mobile' => true, 'tablet' => true],
                    'clients' => ['mobile' => true, 'tablet' => true],
                    'settings' => ['mobile' => true, 'tablet' => true],
                ];
            }
        @endphp
        
        @if(($moduleSettings['dashboard']['mobile'] ?? true) || ($moduleSettings['dashboard']['tablet'] ?? true))
        <a href="{{ route('dashboard') }}" class="modern-mobile-nav-item" data-route="dashboard">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        @endif
        
        @if(($moduleSettings['booths']['mobile'] ?? true) || ($moduleSettings['booths']['tablet'] ?? true))
        <a href="{{ route('booths.index') }}" class="modern-mobile-nav-item" data-route="booths">
            <i class="fas fa-store"></i>
            <span>Booths</span>
        </a>
        @endif
        
        @if(($moduleSettings['bookings']['mobile'] ?? true) || ($moduleSettings['bookings']['tablet'] ?? true))
        <a href="{{ route('books.index') }}" class="modern-mobile-nav-item" data-route="books">
            <i class="fas fa-calendar-check"></i>
            <span>Bookings</span>
        </a>
        @endif
        
        @if(($moduleSettings['clients']['mobile'] ?? true) || ($moduleSettings['clients']['tablet'] ?? true))
        <a href="{{ route('clients.index') }}" class="modern-mobile-nav-item" data-route="clients">
            <i class="fas fa-users"></i>
            <span>Clients</span>
        </a>
        @endif
        
        @if(($moduleSettings['settings']['mobile'] ?? true) || ($moduleSettings['settings']['tablet'] ?? true))
        <a href="{{ route('settings.index') }}" class="modern-mobile-nav-item" data-route="settings">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        @endif
        <a href="{{ route('documentation.index') }}" class="modern-mobile-nav-item" data-route="documentation">
            <i class="fas fa-book"></i>
            <span>Documentation</span>
        </a>
    </div>
    
    <!-- Modern Mobile FAB (Floating Action Button) -->
    @auth
    @if(auth()->user()->isAdmin() || auth()->user()->can('create', App\Models\Book::class))
    <button class="modern-mobile-fab" onclick="window.location.href='{{ route('books.create') }}'" title="New Booking">
        <i class="fas fa-plus"></i>
    </button>
    @endif
    @endauth
</div>

<script>
// Set active state for mobile navigation based on current route
(function() {
    'use strict';
    
    function setActiveNavItem() {
        const currentPath = window.location.pathname;
        const navItems = document.querySelectorAll('.modern-mobile-nav-item');
        
        navItems.forEach(function(item) {
            item.classList.remove('active');
            const href = item.getAttribute('href');
            const route = item.getAttribute('data-route');
            
            if (href && currentPath) {
                // Check if current path matches the route
                if (currentPath === href || currentPath.startsWith(href + '/')) {
                    item.classList.add('active');
                } else if (route) {
                    // Check by route name
                    if (currentPath.includes('/' + route) || currentPath === '/' + route) {
                        item.classList.add('active');
                    }
                }
            }
        });
    }
    
    // Hide/show navigation items based on device type and module settings
    function applyModuleDisplaySettings() {
        const isMobile = window.innerWidth <= 768;
        const isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;
        
        if (!isMobile && !isTablet) {
            return; // Desktop - nav is hidden anyway
        }
        
        const device = isMobile ? 'mobile' : 'tablet';
        const navItems = document.querySelectorAll('.modern-mobile-nav-item');
        
        navItems.forEach(function(item) {
            const route = item.getAttribute('data-route');
            if (route) {
                // Fetch module settings and hide if disabled
                fetch('{{ route("settings.module-display") }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 200 && data.data[route]) {
                            const moduleSettings = data.data[route];
                            if (!moduleSettings[device]) {
                                item.style.display = 'none';
                            } else {
                                item.style.display = 'flex';
                            }
                        }
                    })
                    .catch(function() {
                        // On error, show all items (default behavior)
                        item.style.display = 'flex';
                    });
            }
        });
    }
    
    // Set active on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setActiveNavItem();
            applyModuleDisplaySettings();
        });
    } else {
        setActiveNavItem();
        applyModuleDisplaySettings();
    }
    
    // Update active state when navigating
    document.querySelectorAll('.modern-mobile-nav-item').forEach(function(item) {
        item.addEventListener('click', function() {
            setTimeout(setActiveNavItem, 100);
        });
    });
    
    // Re-apply settings on resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            applyModuleDisplaySettings();
        }, 250);
    });
})();

// Slide-Out Aside Menu Functionality
(function() {
    'use strict';
    
    const asideMenu = document.getElementById('asideMenu');
    const asideOverlay = document.getElementById('asideOverlay');
    const asideToggle = document.getElementById('asideMenuToggle');
    const asideCloseBtn = document.getElementById('asideCloseBtn');
    
    function openAsideMenu() {
        if (asideMenu && asideOverlay) {
            asideMenu.classList.add('open');
            asideOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeAsideMenu() {
        if (asideMenu && asideOverlay) {
            asideMenu.classList.remove('open');
            asideOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    // Toggle menu
    if (asideToggle) {
        asideToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openAsideMenu();
        });
    }
    
    // Close menu
    if (asideCloseBtn) {
        asideCloseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeAsideMenu();
        });
    }
    
    // Close on overlay click
    if (asideOverlay) {
        asideOverlay.addEventListener('click', function() {
            closeAsideMenu();
        });
    }
    
    // Close on menu item click (mobile)
    const asideItems = document.querySelectorAll('.modern-aside-item');
    asideItems.forEach(function(item) {
        item.addEventListener('click', function() {
            // Close menu after a short delay to allow navigation
            setTimeout(function() {
                if (window.innerWidth <= 1024) {
                    closeAsideMenu();
                }
            }, 200);
        });
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && asideMenu && asideMenu.classList.contains('open')) {
            closeAsideMenu();
        }
    });
    
    // Prevent body scroll when menu is open
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (asideMenu && asideMenu.classList.contains('open')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
        });
    });
    
    if (asideMenu) {
        observer.observe(asideMenu, { attributes: true });
    }
    
    // Set active state for aside menu items
    function setActiveAsideItem() {
        const currentPath = window.location.pathname;
        const asideItems = document.querySelectorAll('.modern-aside-item');
        
        asideItems.forEach(function(item) {
            item.classList.remove('active');
            const href = item.getAttribute('href');
            const route = item.getAttribute('data-route');
            
            if (href && currentPath) {
                if (currentPath === href || currentPath.startsWith(href + '/')) {
                    item.classList.add('active');
                } else if (route) {
                    if (currentPath.includes('/' + route) || currentPath === '/' + route) {
                        item.classList.add('active');
                    }
                }
            }
        });
    }
    
    // Set active on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setActiveAsideItem);
    } else {
        setActiveAsideItem();
    }
})();
</script>
@include('partials.app-loading-overlay')
</body>
</html>

