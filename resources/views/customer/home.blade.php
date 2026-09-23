@extends('layouts.app')

@section('title', 'LJS Realty and Brokerage - Find Your Dream Property')

@section('content')
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

    <section class="map-search-section">
        <div class="map-section-header">
            <span class="map-section-tag">WHERE YOUR NEXT HOME BEGINS</span>
            <h2>Find Your Perfect Property</h2>
        </div>

        <div class="map-shell">
            <div class="map-toolbar">
                <div class="map-search-box">
                    <span><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                                <input id="mapSearchInput" type="search" placeholder="Search location, city, or area">
                </div>
                            <button type="button" id="mapSearchButton" class="map-search-btn">Search</button>
            </div>

            <div class="map-container-wrapper">
                <button type="button" id="locateMeBtn" class="locate-me-btn">
                    <svg class="locate-me-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
{{-- 
    <!-- 3. Featured Properties Section -->
    <section class="property-showcase">
        <div class="section-header">
            <h2>Featured Properties</h2>
        </div>

        <div class="property-grid">            
            @forelse ($properties as $item)
                @php
                    $firstImage = $item->images->first();
                    $imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
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
    </section> --}}

    <section class="rush-properties-section">
        <div class="rush-container">
            <div class="rush-header">
                <span class="rush-tag">Featured Properties</span>
                <h2>Featured Properties</h2>
            </div>

            <!-- Slider Wrapper -->
            <div class="rush-slider-wrapper">
                
                <!-- Left Arrow -->
                <button type="button" id="slideLeft" class="slider-arrow slider-left" aria-label="Previous">
                    ❮
                </button>

                <!-- Scrollable Track / Grid -->
                <div id="rushSliderTrack" class="rush-slider-track">
                    @forelse ($featuredProperties as $item)
                        @php
                            $firstImage = $item->images->first();
                            $imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
                        @endphp
                        
                        <article class="rush-card">
                            <!-- Image Container -->
                            <div class="rush-card-img-container">
                                <img src="{{ $imageUrl }}" alt="{{ $item->property_name }}">
                                
                                <!-- Top Badges -->
                                <div class="rush-badges">
                                    <span class="badge-featured">★ Featured</span>
                                    <span class="badge-sale">FOR SALE</span>
                                </div>

                                <!-- Favorite Button -->
                                <button type="button" class="rush-favorite-btn" aria-label="Save Property">
                                    ♡
                                </button>

                                <!-- Image Counter -->
                                <span class="rush-img-count">
                                    1/{{ max(1, $item->images->count()) }}
                                </span>

                                <!-- Agent Banner Overlay -->
                                <div class="rush-agent-overlay">
                                    <div class="rush-agent-avatar">
                                        <img src="{{ asset('images/ljs-logo.jpg') }}" alt="Agent">
                                    </div>
                                    <div class="rush-agent-info">
                                        <div class="rush-agent-name">Maria Santos</div>
                                        <div class="rush-agent-prc">PRC 23456789</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="rush-card-body">
                                <div class="rush-price">
                                    ₱ {{ number_format($item->price, 0, '.', ',') }}
                                </div>
                                
                                <h3 class="rush-title">
                                    {{ $item->property_name }}
                                </h3>
                                
                                <p class="rush-location">
                                     {{ $item->property_location ?? 'Cebu City, Cebu, Philippines' }}
                                </p>

                                <hr class="rush-divider">

                                <!-- Property Meta Details -->
                                <div class="rush-meta">
                                    <span> 2 beds</span>
                                    <span> 2 baths</span>
                                    <span> 85 sqm</span>
                                </div>

                                <!-- Action Button -->
                                <div class="rush-action">
                                    <a href="#" class="rush-btn">View Details</a>
                                </div>
                            </div>
                        </article>            
                    @empty
                        <p class="rush-empty">No featured properties available at the moment.</p>
                    @endforelse
                </div>

                <!-- Right Arrow -->
                <button type="button" id="slideRight" class="slider-arrow slider-right" aria-label="Next">
                    ❯
                </button>
            </div>

            <!-- Pagination Dots -->
            <div class="rush-dots">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>

        </div>
    </section>

    <section class="home-about-section">
        <div class="home-about-container">
            <!-- Left Side: Group Photo with Fade Overlay -->
            <div class="home-about-image-wrapper">
                <img src="{{ asset('images/ljs-family.jpg') }}" alt="LJS Team and People" class="home-about-img">
                <div class="home-about-fade"></div>
            </div>

            <!-- Right Side: Content & Action -->
            <div class="home-about-content">
                <h2>We <span class="highlight-green">find places</span> that people love</h2>
                <p>
                    Praesent venenatis justo in ultrices tempor. Donec maximus, dolor a condimentum congue, tellus nisi dictum magna, uttere lorem ipsum viverra massa ex sit amet nulla. Duis lobortis nisl sed aliquam sollicitudin.
                </p>
                <a href="{{ route('about') }}" class="home-about-btn">Learn More</a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
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
@endpush