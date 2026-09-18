<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'LJS'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/auth-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rush.css') }}">
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
    <link rel="stylesheet" href="{{ asset('css/properties.css') }}">
    <link rel="stylesheet" href="{{ asset('css/property-details.css') }}">
    <link rel="stylesheet" href="{{ asset('css/agents.css') }}?v={{ filemtime(public_path('css/agents.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/lifestyle.css') }}">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    @stack('styles')
</head>
<body class="landing-page">
    <header class="landing-nav {{ request()->routeIs(['home', 'about']) ? 'transparent-nav' : '' }}">
    {{-- <header class="landing-nav {{ request()->routeIs('home') ? 'transparent-nav' : '' }}"> --}}
    {{-- <header class="landing-nav"> --}}
        <div class="logo-container">
            <div class="left">
                <img class="landing-img" src="{{ asset('images/ljs-logo.jpg') }}" alt="Logo" />
            </div>
            <div class="right">
                <nav class="landing-nav-links">
                    <a href="{{ route('home') }}" class="landing-nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
                    <a href="{{ route('properties') }}" class="landing-nav-link {{ request()->routeIs('properties*') ? 'active' : '' }}">Properties</a>
                    <a href="{{ route('agents') }}" class="landing-nav-link {{ request()->routeIs('agents*') ? 'active' : '' }}">Agents</a>
                    <a href="{{ route('about') }}" class="landing-nav-link {{ request()->routeIs('about') ? 'active' : '' }}">About Us</a>
                </nav>
                <div class="auth-action">
                    @auth
                        <div class="public-account-actions">
                            <div class="public-profile-wrap">
                                <button class="public-account-pill" type="button" id="publicProfileButton" aria-expanded="false"><span>{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>{{ Auth::user()->name }}<i class="fas fa-chevron-down"></i></button>
                                <div class="public-profile-menu" id="publicProfileMenu" aria-hidden="true">
                                    <a href="{{ route('messages.inbox') }}" class="public-profile-item"><i class="fas fa-paper-plane"></i> Messages</a>
                                    <button class="public-profile-item" type="button" data-notification-count-url="{{ route('notifications.count') }}" id="notificationMenuButton" aria-expanded="false"><i class="fas fa-bell"></i> Notifications @if(($unreadNotificationCount ?? 0) > 0)<span class="public-notification-badge" data-notification-count>{{ $unreadNotificationCount }}</span>@endif</button>
                                    <div class="public-notification-list" id="notificationMenu" aria-hidden="true">
                                        @forelse ($recentNotifications ?? [] as $notification)
                                            <a href="{{ route('notifications.open', $notification) }}" class="public-notification-item"><strong>{{ $notification->title }}</strong><small>{{ $notification->body }}</small><time>{{ $notification->created_at->diffForHumans() }}</time></a>
                                        @empty
                                            <p>No new notifications.</p>
                                        @endforelse
                                    </div>
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

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-col brand-col">
                <img src="{{ asset('images/ljs-logo.jpg') }}" alt="LJS Realty Logo" class="footer-logo" />
                <p class="footer-tagline">Your trusted partner in real estate.<br>Helping you find your dream property.</p>
            </div>

            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><a href="{{ route('properties') }}">Properties</a></li>
                    <li><a href="{{ route('agents') }}">Agents</a></li>
                    <li><a href="{{ route('about') }}">About Us</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Contact Us</h4>
                <ul class="contact-list">
                    <li><i class="icon-phone"></i> +63 921 345 6789</li>
                    <li><i class="icon-email"></i> ljsrealty@gmail.com</li>
                    <li><i class="icon-location"></i> Unit 6 Labangon Town Center<br>Katipunan St, Cebu City</li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Social Media</h4>
                <div class="social-icons">
                    <a href="#" class="social-icon fb" aria-label="Facebook"></a>
                    <a href="#" class="social-icon messenger" aria-label="Messenger"></a>
                </div>
            </div>
        </div>
    </footer>

    @include('components.auth-modal')

    @php
        $mapProperties = ($properties ?? collect())->map(function ($property) {
            return [
                'name' => $property->property_name,
                'type' => $property->property_type,
                'status' => $property->property_status ?? 'Available',
                'price' => (float) ($property->price ?? 0),
                'location' => $property->property_location ?? 'Cebu City',
                'lat' => (float) ($property->latitude ?? 10.3157),
                'lng' => (float) ($property->longitude ?? 123.8854),
            ];
        })->values();
    @endphp

    <script>
        window.propertyMapData = @json($mapProperties);
    </script>

    <script src="{{ asset('js/global.js') }}" defer></script>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('js/rush.js') }}" defer></script>
    <script src="{{ asset('js/about.js') }}" defer></script>
    <script src="{{ asset('js/properties.js') }}" defer></script>
    <script src="{{ asset('js/agents.js') }}" defer></script>
    <script src="{{ asset('js/lifestyle.js') }}" defer></script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>