@extends('layouts.app')

@section('content')
<div class="properties">
    <section class="lifestyle" aria-label="Lifestyle property match">
        <div class="media">
            <img src="{{ $lifestyleBannerUrl ?? asset('images/lifestyle.png') }}" 
                alt="Lifestyle Match" 
                data-fallback-src="{{ asset('images/lifestyle.png') }}"
                data-lifestyle-images='@json(($lifestyleBannerUrls ?? collect([asset('images/lifestyle.png')]))->values())'>
        </div>

        <div class="lifestyle-content">
            <span class="lifestyle-kicker">Personalized property finder</span>
            <h2>We <span>find places</span> that people love</h2>
            <p>Answer a few quick questions and discover listings that fit your budget, location, and everyday routine.</p>
            <a href="{{ route('lifestyle.match') }}" class="lifestyle-action">Lifestyle Match</a>
        </div>
    </section>

    {{-- SEARCH BAR --}}
    <div class="filter-bar">
        <form action="{{ route('properties') }}" method="GET" class="filter-form" id="propertyFilterForm">
                
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
                    @foreach ($priceRanges as $priceRange)
                        <option value="{{ $priceRange['value'] }}" @selected(request('price_range') === $priceRange['value'])>{{ $priceRange['label'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- SORT --}}
            <div class="filter-group">
                <label>Sort by</label>
                <select name="sort" class="filter-select">
                    <option value="featured" @selected(request('sort', 'featured') === 'featured')>Featured</option>
                    <option value="price_low" @selected(request('sort') === 'price_low')>Price: Low to High</option>
                    <option value="price_high" @selected(request('sort') === 'price_high')>Price: High to Low</option>
                    <option value="latest" @selected(request('sort') === 'latest')>Latest</option>
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
        <div id="propertyResults">
        <div class="properties-grid">
            @forelse ($properties as $item)
                @php
                    $firstImage = $item->images->first();
                    $imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
                    $imageCount = $item->images->count() > 0 ? $item->images->count() : 1;
                    $statusClass = Str::slug($item->property_status ?: 'available');
                @endphp

                <article class="property-card property-status-card-{{ $statusClass }}">
                    <div class="property-card-img-container">
                        <img src="{{ $imageUrl }}" alt="{{ $item->property_name }}">
                        
                        {{-- TEMPORARY --}}
                        <div class="property-badges">
                            <span class="badge-sale badge-status-{{ $statusClass }}">{{ strtoupper($item->property_status ?: 'Available') }}</span>
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
                        
                        <div class="property-category-tag">{{ $item->property_type ?? 'Property' }} - {{ $item->property_status ?: 'Available' }}</div>

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
        <p class="property-filter-status" id="propertyFilterStatus" role="status" aria-live="polite" aria-atomic="true"></p>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('propertyFilterForm');
        const results = document.getElementById('propertyResults');
        const status = document.getElementById('propertyFilterStatus');
        if (!form || !results) return;

        let activeRequest;
        let searchTimer;

        const loadResults = async (url, updateHistory = true) => {
            activeRequest?.abort();
            const request = new AbortController();
            activeRequest = request;
            form.setAttribute('aria-busy', 'true');
            results.classList.add('is-loading');

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: request.signal,
                });
                if (!response.ok) throw new Error('Unable to load properties.');

                const documentFromResponse = new DOMParser().parseFromString(await response.text(), 'text/html');
                const updatedResults = documentFromResponse.querySelector('#propertyResults');
                if (!updatedResults) throw new Error('Property results were not found.');

                results.innerHTML = updatedResults.innerHTML;
                if (updateHistory && url.href !== window.location.href) {
                    window.history.pushState({}, '', url);
                }
                const count = results.querySelectorAll('.property-card').length;
                if (status) status.textContent = `${count} ${count === 1 ? 'property' : 'properties'} shown.`;
            } catch (error) {
                if (error.name !== 'AbortError' && status) {
                    status.textContent = 'Unable to update properties. Please try again.';
                }
            } finally {
                if (activeRequest === request) {
                    activeRequest = null;
                    form.removeAttribute('aria-busy');
                    results.classList.remove('is-loading');
                }
            }
        };

        const submitFilters = () => {
            const url = new URL(form.action, window.location.href);
            new FormData(form).forEach((value, name) => {
                if (String(value).trim()) url.searchParams.set(name, String(value));
                else url.searchParams.delete(name);
            });
            url.searchParams.delete('page');
            loadResults(url);
        };

        const restoreFiltersFromUrl = () => {
            const url = new URL(window.location.href);
            form.querySelectorAll('select[name], input[name="search"]').forEach((field) => {
                const defaultValue = field.name === 'sort' ? 'featured' : '';
                field.value = url.searchParams.get(field.name) ?? defaultValue;
            });
            loadResults(url, false);
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitFilters();
        });

        const autoSubmitFields = form.querySelectorAll('select, input[name="search"]');
        autoSubmitFields.forEach((field) => {
            if (field.matches('select')) {
                field.addEventListener('change', submitFilters);
                return;
            }

            field.addEventListener('input', () => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(submitFilters, 350);
            });
        });

        results.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link) return;
            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) return;
            event.preventDefault();
            loadResults(url);
        });

        window.addEventListener('popstate', restoreFiltersFromUrl);

        const lifestyleImage = document.querySelector('[data-lifestyle-images]');
        if (lifestyleImage) {
            let images = [];
            try {
                images = JSON.parse(lifestyleImage.dataset.lifestyleImages || '[]');
            } catch {
                images = [];
            }

            images = images.filter(Boolean);
            if (images.length > 1) {
                let currentIndex = Math.max(0, images.indexOf(lifestyleImage.getAttribute('src')));
                images.forEach((imageUrl) => {
                    const preload = new Image();
                    preload.src = imageUrl;
                });

                lifestyleImage.addEventListener('animationiteration', () => {
                    currentIndex = (currentIndex + 1) % images.length;
                    lifestyleImage.src = images[currentIndex];
                });
            }
        }
    });
</script>
@endpush
