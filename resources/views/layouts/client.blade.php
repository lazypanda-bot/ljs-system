<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name', 'Laravel'))</title>

        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="{{ asset('css/global.css') }}">
        <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
        <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
        <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
        <link rel="stylesheet" href="{{ asset('css/login-modal.css') }}">
        <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
        <link rel="stylesheet" href="{{ asset('css/addproperty.css') }}">
        
        @stack('styles')
    </head>
    <body>
        <div class="dashboard-container">
            @include('layouts.sidebar')

            <div class="main-content">
                @include('layouts.navbar')

                <main class="content-area">
                    @yield('content')
                </main>
            </div>
        </div>

        <script src="{{ asset('js/global.js') }}" defer></script>
        <script src="{{ asset('js/dashboard.js') }}" defer></script>
        <script src="{{ asset('js/admin.js') }}" defer></script>
        <script src="{{ asset('js/addproperty.js') }}" defer></script>

        @stack('scripts')
    </body>
</html>
