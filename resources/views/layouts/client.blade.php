<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('components.session-history-guard')
        <title>@yield('title', config('app.name', 'LJS Realty and Brokerage'))</title>
        <link rel="icon" type="image/png" href="{{ asset('images/ljs-logo.jpg') }}">

        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
        <script src="{{ asset('js/icons.js') }}?v={{ filemtime(public_path('js/icons.js')) }}" defer></script>
        <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}?v={{ filemtime(public_path('css/sidebar.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/navbar.css') }}?v={{ filemtime(public_path('css/navbar.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/auth-modal.css') }}?v={{ filemtime(public_path('css/auth-modal.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/addproperty.css') }}?v={{ filemtime(public_path('css/addproperty.css')) }}">
        
        @stack('styles')
        <link rel="stylesheet" href="{{ asset('css/icons.css') }}?v={{ filemtime(public_path('css/icons.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/responsive.css') }}?v={{ filemtime(public_path('css/responsive.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/account-menu.css') }}?v={{ filemtime(public_path('css/account-menu.css')) }}">
    </head>
    <body>
        @include('components.flash-messages')
        <div class="dashboard-container">
            @include('layouts.sidebar')
            <button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Close navigation" tabindex="-1"></button>

            <div class="main-content">
                @include('layouts.navbar')

                <main class="content-area">
                    @yield('content')
                </main>
            </div>
        </div>

        @include('components.auth-modal')
        @include('components.popup-message')

        <script src="{{ asset('js/global.js') }}?v={{ filemtime(public_path('js/global.js')) }}" defer></script>
        <script src="{{ asset('js/dashboard.js') }}" defer></script>
        <script src="{{ asset('js/admin.js') }}" defer></script>
        <script src="{{ asset('js/addproperty.js') }}" defer></script>

        <script src="{{ asset('js/page-context.js') }}?v={{ filemtime(public_path('js/page-context.js')) }}" defer></script>
        @stack('scripts')
    </body>
</html>
