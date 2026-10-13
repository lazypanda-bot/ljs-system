@extends('layouts.app')

@section('title', 'LJS Realty and Brokerage')

@section('content')
    {{-- FOR HERO BANNER CHANGING IMAGE DEPENDING ON TIME
    <section class="hero-section"
        data-hero-time-image
        data-sunrise-image="{{ asset('images/sunrise.png') }}"
        data-day-image="{{ asset('images/day.png') }}"
        data-sunset-image="{{ asset('images/sunset.png') }}"
        data-night-image="{{ asset('images/night.png') }}">
    --}}
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
                <div id="propertyMap" class="property-map"></div>
            </div>

            <div class="map-legend">
                <span class="legend-item"><i class="legend-dot for-sale"></i> For Sale</span>
                <span class="legend-item"><i class="legend-dot for-rent"></i> For Rent</span>
                <span class="legend-item"><i class="legend-dot rent-to-own"></i> Rent to Own</span>
                <span class="legend-item"><i class="legend-dot reserved"></i> Reserved</span>
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
            <div class="rush-header home-section-header">
                <div>
                    <span class="rush-tag">Featured Properties</span>
                    <h2>Featured Properties</h2>
                </div>
                <a class="home-section-link" href="{{ route('properties') }}">All properties</a>
            </div>

            <div class="rush-property-grid">
                    @forelse ($featuredProperties as $item)
                        @php
                            $firstImage = $item->images->first();
                            $imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
                            $statusClass = Str::slug($item->property_status ?: 'available');
                        @endphp
                        
                        <article class="rush-card rush-status-card-{{ $statusClass }}">
                            <!-- Image Container -->
                            <div class="rush-card-img-container">
                                <img src="{{ $imageUrl }}" alt="{{ $item->property_name }}">
                                
                                <!-- Top Badges -->
                                <div class="rush-badges">
                                    <span class="badge-featured"><i data-lucide="star" aria-hidden="true"></i> Featured</span>
                                    <span class="badge-sale badge-status-{{ $statusClass }}">{{ strtoupper($item->property_status ?: 'Available') }}</span>
                                </div>

                                <!-- Favorite Button -->
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
                                        <div class="rush-agent-name">{{ $item->user?->name ?? 'LJS Realty Team' }}</div>
                                            <div class="rush-agent-prc">{{ $item->user?->professionalProfile?->license_number ?? 'LJS Realty and Brokerage' }}</div>
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
                                    <span>{{ $item->details?->bedroom ?? 0 }} beds</span>
                                    <span>{{ $item->details?->bathroom ?? 0 }} baths</span>
                                    <span>{{ $item->details?->floor_area ?? 0 }} sqm</span>
                                </div>

                                <!-- Action Button -->
                                <div class="rush-action">
                                    <a href="{{ route('properties.show', $item->property_id) }}" class="rush-btn">View Details</a>
                                </div>
                            </div>
                        </article>            
                    @empty
                        <p class="rush-empty">No featured properties available at the moment.</p>
                    @endforelse
            </div>
        </div>
    </section>

    <section class="top-agents-section home-professionals-section" aria-labelledby="homeProfessionalsTitle">
        <div class="top-agents-container home-professionals-container">
            <div class="top-agents-header home-professionals-header">
                <div>
                    <span class="top-agents-tag">MEET OUR TOP-PERFORMING PROFESSIONALS</span>
                    <h2 id="homeProfessionalsTitle">Most <span class="highlight-green">Reviewed</span> Brokers &amp; Agents</h2>
                </div>
                <a class="home-section-link" href="{{ route('agents') }}">View all professionals</a>
            </div>

            <div class="top-agents-track home-professionals-grid">
                @forelse ($topProfessionals as $professional)
                    @php
                        $agentImage = $professional->profile_photo ? asset('storage/' . $professional->profile_photo) : asset('images/ljs-logo.jpg');
                        $ratingValue = $professional->received_reviews_avg_rating ? (int) round($professional->received_reviews_avg_rating) : 0;
                        $rating = $professional->received_reviews_avg_rating ? number_format($professional->received_reviews_avg_rating, 1) : 'New';
                    @endphp
                    <article class="agent-highlight-card">
                        <div class="agent-avatar-container">
                            <img src="{{ $agentImage }}" alt="{{ $professional->name }}" data-fallback-src="{{ asset('images/ljs-logo.jpg') }}">
                        </div>
                        <div class="agent-card-info">
                            <h3>{{ $professional->name }}</h3>
                            <p class="agent-role">{{ $professional->role_name }}</p>
                            <div class="agent-rating">
                                <span class="stars" aria-label="{{ $ratingValue }} out of 5 stars">@for ($star = 1; $star <= 5; $star++)<i data-lucide="star" class="{{ $star <= $ratingValue ? 'is-rated' : '' }}" aria-hidden="true"></i>@endfor</span>
                                <span class="rating-score">{{ $rating }}</span>
                                <span class="review-count">({{ $professional->received_reviews_count }} reviews)</span>
                            </div>
                            <div class="agent-sold-stat">{{ $professional->professionalProfile?->years_experience ?? 0 }} Years Experience</div>
                            <a href="{{ route('agents.show', $professional) }}" class="agent-view-btn">View details</a>
                        </div>
                    </article>
                @empty
                    <p class="no-agents-text">No professionals found.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="home-about-section">
        <div class="home-about-container">
            <!-- Left Side: Group Photo with Fade Overlay -->
            <div class="home-about-image-wrapper">
                <img src="{{ asset('images/ljs-family.jpg') }}" alt="LJS Team and People" class="home-about-img">
            </div>

            <!-- Right Side: Content & Action -->
            <div class="home-about-content">
                <h2>We <span class="highlight-green">find places</span> that people love</h2>
                <p>
                    p
                </p>
                <a href="{{ route('about') }}" class="home-about-btn">Learn More</a>
            </div>
        </div>
    </section>
@endsection

{{-- FOR HERO BANNER CHANGING IMAGE DEPENDING ON TIME
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const hero = document.querySelector('[data-hero-time-image]');
            if (!hero) return;

            const hour = new Date().getHours();
            const image = hour >= 5 && hour < 9
                ? hero.dataset.sunriseImage
                : hour >= 9 && hour < 17
                    ? hero.dataset.dayImage
                    : hour >= 17 && hour < 19
                        ? hero.dataset.sunsetImage
                        : hero.dataset.nightImage;

            if (image) {
                hero.style.setProperty('--hero-image', `url("${image}")`);
            }
        });
    </script>
@endpush
--}}

