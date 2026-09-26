<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- <title>@yield('title', config('app.name', 'LJS'))</title> --}}
    <title>LJS Realty and Brokerage</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ljs-logo.jpg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
    <script src="{{ asset('js/icons.js') }}?v={{ filemtime(public_path('js/icons.js')) }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/auth-modal.css') }}?v={{ filemtime(public_path('css/auth-modal.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/rush.css') }}">
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
    <link rel="stylesheet" href="{{ asset('css/properties.css') }}">
    <link rel="stylesheet" href="{{ asset('css/property-details.css') }}">
    <link rel="stylesheet" href="{{ asset('css/agents.css') }}?v={{ filemtime(public_path('css/agents.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/lifestyle.css') }}">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/icons.css') }}?v={{ filemtime(public_path('css/icons.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}?v={{ filemtime(public_path('css/responsive.css')) }}">
</head>
<body class="landing-page">
    @include('components.flash-messages')
    <header class="landing-nav {{ request()->routeIs(['home', 'about']) ? 'transparent-nav' : '' }}">
    {{-- <header class="landing-nav {{ request()->routeIs('home') ? 'transparent-nav' : '' }}"> --}}
    {{-- <header class="landing-nav"> --}}
        <div class="logo-container">
            <div class="left">
                <a href="{{ route('home') }}" aria-label="LJS Realty home"><img class="landing-img" src="{{ asset('images/ljs-logo.jpg') }}" alt="LJS Realty" /></a>
            </div>
            <div class="right">
                <nav class="landing-nav-links" id="publicNavigation" aria-label="Main navigation">
                    <a href="{{ route('home') }}" class="landing-nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
                    <a href="{{ route('properties') }}" class="landing-nav-link {{ request()->routeIs('properties*') ? 'active' : '' }}">Properties</a>
                    <a href="{{ route('agents') }}" class="landing-nav-link {{ request()->routeIs('agents*') ? 'active' : '' }}">Agents</a>
                    <a href="{{ route('about') }}" class="landing-nav-link {{ request()->routeIs('about') ? 'active' : '' }}">About Us</a>
                    <a href="{{ route('contact') }}" class="landing-nav-link {{ request()->routeIs('contact') ? 'active' : '' }}">Contact Us</a>
                </nav>
                <button class="mobile-nav-toggle" id="publicNavToggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="publicNavigation">
                    <i data-lucide="menu" aria-hidden="true"></i>
                </button>
                <div class="auth-action">
                    @auth
                        <div class="public-account-actions">
                            <div class="public-profile-wrap">
                                <button class="public-account-pill" type="button" id="publicProfileButton" aria-expanded="false"><span>{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>{{ Auth::user()->name }}<i class="fas fa-chevron-down"></i></button>
                                <div class="public-profile-menu" id="publicProfileMenu" aria-hidden="true">
                                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="public-profile-item public-logout" type="submit"><i class="fas fa-right-from-bracket"></i> Logout</button></form>
                                </div>
                            </div>
                        </div>
                    @else
                        <button type="button" id="openLoginModal" class="login-btn">Login</button>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <div class="floating-actions" aria-label="Messages and notifications">
        @auth
            <a href="{{ route('messages.inbox') }}" class="floating-action" aria-label="Messages" title="Messages">
                <i class="fas fa-comments" aria-hidden="true"></i>
            </a>
            <div class="floating-notification-wrap">
                <button type="button" class="floating-action" data-notification-count-url="{{ route('notifications.count') }}" id="notificationMenuButton" aria-expanded="false" aria-label="Notifications" title="Notifications">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    @if(($unreadNotificationCount ?? 0) > 0)
                        <span class="public-notification-badge" data-notification-count>{{ $unreadNotificationCount }}</span>
                    @endif
                </button>
                <div class="floating-notification-panel" id="notificationMenu" aria-hidden="true">
                    <div class="floating-notification-title">Notifications</div>
                    @forelse ($recentNotifications ?? [] as $notification)
                        <a href="{{ route('notifications.open', $notification) }}" class="public-notification-item"><strong>{{ $notification->title }}</strong><small>{{ $notification->body }}</small><time>{{ $notification->created_at->diffForHumans() }}</time></a>
                    @empty
                        <p>No new notifications.</p>
                    @endforelse
                </div>
            </div>
        @else
            <button type="button" class="floating-action" data-open-login-modal aria-label="Open Messages login" title="Messages">
                <i class="fas fa-comments" aria-hidden="true"></i>
            </button>
            <button type="button" class="floating-action" data-open-login-modal aria-label="Open Notifications login" title="Notifications">
                <i class="fas fa-bell" aria-hidden="true"></i>
            </button>
        @endauth
    </div>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-col brand-col">
                <img src="{{ asset('images/ljs-logo.jpg') }}" alt="LJS Realty Logo" class="footer-logo" />
            </div>
        </div>
    </footer>

    @include('components.auth-modal')

    @php
        $mapProperties = ($properties ?? collect())->map(function ($property) {
            return [
                'id' => $property->property_id,
                'name' => $property->property_name,
                'type' => $property->property_type,
                'status' => $property->property_status ?? 'Available',
                'price' => (float) ($property->price ?? 0),
                'location' => trim(($property->city ?? '') . ', ' . ($property->province ?? '')),
                'lat' => (float) ($property->latitude ?? 10.3157),
                'lng' => (float) ($property->longitude ?? 123.8854),
            ];
        })->values();
    @endphp

    <script>
        window.propertyMapData = @json($mapProperties);
    </script>

    <script src="{{ asset('js/global.js') }}?v={{ filemtime(public_path('js/global.js')) }}" defer></script>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('js/rush.js') }}" defer></script>
    <script src="{{ asset('js/about.js') }}" defer></script>
    <script src="{{ asset('js/properties.js') }}?v={{ filemtime(public_path('js/properties.js')) }}" defer></script>
    <script src="{{ asset('js/agents.js') }}" defer></script>
    <script src="{{ asset('js/lifestyle.js') }}" defer></script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>