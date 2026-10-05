@extends('layouts.app')

@section('content')
<div class="properties">
    <div class="lifestyle">
        <!-- Left Half: Image -->
        <div class="media">
            <img src="{{ asset('images/your-lifestyle-image.jpg') }}" 
                alt="Lifestyle Match" 
                data-fallback-src="{{ asset('images/ljs-logo.jpg') }}">
        </div>

        <!-- Right Half: Content (Must be a direct child of .lifestyle, right next to .media) -->
        <div class="lifestyle-content">
            <h2>We <span>find places</span> that people love</h2>
            <p>Answer a few quick questions to match your preferences with the ideal property.</p>
            <a href="{{ route('lifestyle.match') }}" class="lifestyle-action">Lifestyle Match</a>
        </div>
    </div>

    {{-- SEARCH BAR --}}
    <div class="filter-bar">
        <form action="{{ route('properties') }}" method="GET" class="filter-form">
                
            <!-- Type Filter -->
            <div class="filter-group">
                <label>Type</label>
                <select name="type" class="filter-select">
                    <option value="">All Property</option>
                    @foreach ($propertyTypes as $propertyType)
                        <option value="{{ $propertyType }}" {{ request('type') === $propertyType ? 'selected' : '' }}>{{ $propertyType }}</option>
                    @endforeach
                </select>
            </div>

            {{-- LOCATION --}}
            <div class="filter-group">
                <label>Location</label>
                <select name="location" class="filter-select">
                    <option value="">All Locations</option>
                    @foreach ($propertyLocations as $propertyLocation)
                        <option value="{{ $propertyLocation }}" {{ request('location') === $propertyLocation ? 'selected' : '' }}>{{ $propertyLocation }}</option>
                    @endforeach
                </select>
            </div>

            {{-- PRICE --}}
            <div class="filter-group">
                <label>Price Range</label>
                <select name="price_range" class="filter-select">
                    <option value="">All</option>
                    <option value="0-5m">₱0 - ₱5M</option>
                    <option value="5m-10m">₱5M - ₱10M</option>
                    <option value="10m+">₱10M+</option>
                </select>
            </div>

            {{-- SORT --}}
            <div class="filter-group">
                <label>Sort by</label>
                <select name="sort" class="filter-select">
                    <option value="featured">Featured</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="latest">Latest</option>
                </select>
            </div>

            {{-- SEARCH AND SUBMIT --}}
            <div class="search-group">
                <label>Keyword</label>
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search properties, locations.." value="{{ request('search') }}">
                    <button type="submit" aria-label="Search">
                        <i data-lucide="search" aria-hidden="true"></i>                        </button>
                </div>
            </div>
        </form>
    </div>

        <!-- PROPERTIES GRID -->
        <div class="properties-grid">
            @forelse ($properties as $item)
                @php
                    $firstImage = $item->images->first();
                    $imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
                    $imageCount = $item->images->count() > 0 ? $item->images->count() : 1;
                @endphp

                <article class="property-card">
                    <div class="property-card-img-container">
                        <img src="{{ $imageUrl }}" alt="{{ $item->property_name }}">
                        
                        {{-- TEMPORARY --}}
                        <div class="property-badges">
                            <span class="badge-sale">FOR SALE</span>
                        </div>

                        <span class="property-img-count">1/{{ $imageCount }}</span>

                        <div class="property-agent-overlay">
                            <div class="property-agent-avatar">
                                <img src="{{ asset('images/ljs-logo.jpg') }}" alt="Agent">
                            </div>
                            <div class="property-agent-info">
                                <div class="property-agent-name">{{ $item->user?->name ?? 'LJS Professional' }}</div>
                                <div class="property-agent-prc">{{ $item->user?->role_name ?? 'Real Estate Professional' }}</div>
                            </div>
                        </div>

                    </div>

                    <div class="property-card-body">
                        <div class="property-price">₱ {{ number_format($item->price, 0, '.', ',') }}</div>
                        <h3 class="property-title">{{ $item->property_name }}</h3>
                        <p class="property-location">{{ $item->city }}, {{ $item->province }}</p>
                        
                        <div class="property-category-tag">{{ $item->property_type ?? 'Condominium' }} - For Sale</div>

                        <hr class="property-divider">

                        <div class="property-meta">
                            <span> {{ $item->details->bedroom ?? 0 }} beds</span>
                            <span> {{ $item->details->bathroom ?? 0 }} baths</span>
                            <span> {{ $item->details->floor_area ?? 0 }} sqm</span>
                        </div>

                        <div class="property-action">
                            <a href="{{ route('properties.show', $item->property_id) }}" class="property-btn">View Details</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="property-empty-state">
                    <p>No properties found</p>
                </div>
            @endforelse
        </div>
        {{ $properties->links('pagination.ljs') }}

    </div>
</div>
@endsection