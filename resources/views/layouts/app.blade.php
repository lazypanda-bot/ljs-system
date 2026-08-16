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
        <!-- 1. Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Find Your <span class="highlight">Dream</span> Property</h1>
                <p class="hero-subtitle">
                    At <strong>LJS Realty and Brokerage</strong>, finding your ideal home is easy.
                    Explore a wide selection of properties tailored to your lifestyle, preferences, and budget.
                </p>
                <a href="{{ route('properties') }}" class="hero-btn">Explore Now</a>
            </div>
        </section>

        <!-- 2. Map Section (Placed right after the hero, matching your target design) -->
        <section class="map-search-section">
            <div class="map-section-header">
                <span class="map-section-tag">WHERE YOUR NEXT HOME BEGINS</span>
                <h2>Find Your Perfect Property</h2>
            </div>

            <div class="map-shell">
                <div class="map-toolbar">
                    <div class="map-search-box">
                        <span>⌕</span>
                        <input type="text" placeholder="Search location, city, or area">
                    </div>
                    <button type="button" class="map-search-btn">Search</button>
                </div>

                <div class="map-container-wrapper" style="position: relative;">
                    <button type="button" id="locateMeBtn" class="locate-me-btn">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #2563eb;">
                            <circle cx="12" cy="12" r="7"></circle>
                            <circle cx="12" cy="12" r="2" fill="currentColor"></circle>
                            <line x1="12" y1="2" x2="12" y2="5"></line>
                            <line x1="12" y1="19" x2="12" y2="22"></line>
                            <line x1="2" y1="12" x2="5" y2="12"></line>
                            <line x1="19" y1="12" x2="22" y2="12"></line>
                        </svg>
                        Locate Me
                    </button>
                    <div id="propertyMap" class="property-map"></div>
                </div>

                <div class="map-legend">
                    <span class="legend-item"><i class="legend-dot for-sale"></i> For Sale</span>
                    <span class="legend-item"><i class="legend-dot for-rent"></i> For Rent</span>
                    <span class="legend-item"><i class="legend-dot sold"></i> Sold</span>
                    <span class="legend-item"><i class="legend-dot reserved"></i> Reserved</span>
                    <span class="legend-item"><i class="legend-dot unavailable"></i> Unavailable</span>
                </div>
            </div>
        </section>

        <!-- 3. Featured Properties Section -->
        <section class="property-showcase">
            <div class="section-header">
                <h2>Featured Properties</h2>
            </div>

            <div class="property-grid">            
                @forelse ($properties as $item)
                    @php
                        $firstImage = $item->images->first();
                        $imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/night.png');
                    @endphp
                    <article class="property-card">
                        <img src="{{ $imageUrl }}" alt="{{ $item->property_name }}">
                        <div class="property-card-body">
                            <div class="property-card-top">
                                <span class="property-type">{{ $item->property_type }}</span>
                                <span class="property-status">{{ $item->property_status }}</span>
                            </div>
                            <h3>{{ $item->property_name }}</h3>
                            <p class="property-location">{{ $item->property_location }}</p>
                            <p class="property-price">₱ {{ number_format($item->price, 0, '.', ',') }}</p>
                            <p class="property-description">{{ Str::limit($item->property_description ?? '', 100) }}</p>
                        </div>
                    </article>            
                @empty
                    <p class="no-properties">No properties available at the moment.</p>
                @endforelse
            </div>
        </section>
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>