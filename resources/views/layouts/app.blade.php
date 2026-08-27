<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', config('app.name', 'LJS'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rush.css') }}">
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
    <link rel="stylesheet" href="{{ asset('css/properties.css') }}">
    <link rel="stylesheet" href="{{ asset('css/property-details.css') }}">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body class="landing-page">
    <header class="landing-nav">
        <div class="logo-container">
            <div class="left">
                <img class="landing-img" src="{{ asset('images/ljs-logo.jpg') }}" alt="Logo" />
            </div>
            <div class="right">
                <nav class="landing-nav-links">
                    <a href="{{ route('home') }}" class="landing-nav-link">Home</a>
                    <a href="{{ route('properties') }}" class="landing-nav-link">Properties</a>
                    <a href="{{ route('agents') }}" class="landing-nav-link">Agents</a>
                    <a href="{{ route('about') }}" class="landing-nav-link">About Us</a>
                </nav>
                <div class="auth-action">
                    <button type="button" id="openLoginModal" class="login-btn" data-open-login-modal="true">Login</button>
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

    <div id="loginModalOverlay" class="login-modal-overlay" aria-hidden="true" data-open-if-error="{{ session()->has('login_error') ? 'true' : 'false' }}">
        <div class="login-modal" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
            <button type="button" class="login-modal-close" aria-label="Close">×</button>
            <div class="login-modal-header" id="loginModalTitle">Please sign in</div>
            <p class="login-modal-note">
                TEMPORARY ACCOUNTS.<br>
                admin@example.com / admin<br>
                agent@example.com / agent<br>
                leadbroker@example.com / lead<br>
                broker@example.com / broker
            </p>

            @if(session('login_error'))
                <div class="login-modal-error">{{ session('login_error') }}</div>
            @endif

            <form method="POST" action="{{ route('temp.login') }}">
                @csrf

                <div class="form-group">
                    <label for="loginEmail">Email</label>
                    <input id="loginEmail" name="email" type="email" placeholder="Enter email" required />
                </div>

                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <input id="loginPassword" name="password" type="password" placeholder="Enter password" required />
                </div>

                <button type="submit" class="login-submit-btn">Login</button>
            </form>
        </div>
    </div>

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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>