@extends('layouts.app')

@section('title', 'LJS Realty and Brokerage - Meet Our Agents')

@section('content')
    <section class="top-agents-section">
        <div class="top-agents-container">
            <div class="top-agents-header">
                <span class="top-agents-tag">MEET OUR TOP-PERFORMING PROFESSIONALS</span>
                <h2>Most <span class="highlight-green">Reviewed</span> Brokers & Agents</h2>
            </div>

            <div class="top-agents-slider-wrapper">
                <div id="agentSliderTrack" class="top-agents-track">
                    @forelse ($topProfessionals as $professional)
                        @php
                            $agentImage = $professional->profile_photo ? asset('storage/' . $professional->profile_photo) : asset('images/ljs-logo.jpg');
                            $rating = $professional->received_reviews_avg_rating ? number_format($professional->received_reviews_avg_rating, 1) : 'New';
                        @endphp
                        <article class="agent-highlight-card">
                            <div class="agent-avatar-container">
                                <img src="{{ $agentImage }}" alt="{{ $professional->name }}" onerror="this.src='{{ asset('images/ljs-logo.jpg') }}'">
                            </div>
                            <div class="agent-card-info">
                                <h3>{{ $professional->name }}</h3>
                                <p class="agent-role">{{ $professional->role_name }}</p>
                                <div class="agent-rating">
                                    <span class="stars">★★★★★</span>
                                    <span class="rating-score">{{ $rating }}</span>
                                    <span class="review-count">({{ $professional->received_reviews_count }} reviews)</span>
                                </div>
                                <div class="agent-sold-stat">
                                    {{ $professional->professionalProfile?->years_experience ?? 0 }} Years Experience
                                </div>
                                <a href="{{ route('agents.show', $professional) }}" class="agent-view-btn">View details</a>
                            </div>
                        </article>
                    @empty
                        <div class="no-agents">
                            <p>No agents found.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="agents-directory-section">
        <div class="agents-directory-container">
            <div class="directory-header">
                <h2>Find the <span class="highlight-green">Right</span> Real Estate Professional</h2>
                <p>Browse our licensed and PRC-registered real estate brokers, salespersons, and agents ready to assist you.</p>
            </div>

            <form action="{{ route('agents') }}" method="GET" class="agent-search-toolbar">
                <div class="agent-search-box">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search agents, brokers..." />
                </div>
                <button type="submit" class="agent-search-btn">Search</button>
            </form>

            <div class="agents-grid">
                @forelse ($allProfessionals as $professional)
                    @php
                        $directoryImage = $professional->profile_photo ? asset('storage/' . $professional->profile_photo) : asset('images/ljs-logo.jpg');
                        $rating = $professional->received_reviews_avg_rating ? number_format($professional->received_reviews_avg_rating, 1) : 'New';
                    @endphp
                    <article class="agent-highlight-card">
                        <div class="agent-avatar-container">
                            <img src="{{ $directoryImage }}" alt="{{ $professional->name }}" onerror="this.src='{{ asset('images/ljs-logo.jpg') }}'">
                        </div>

                        <div class="agent-card-info">
                            <h3>{{ $professional->name }}</h3>
                            <p class="agent-role">{{ $professional->role_name }}</p>
                            
                            <div class="agent-rating">
                                <span class="stars">★★★★★</span>
                                <span class="rating-score">{{ $rating }}</span>
                                <span class="review-count">({{ $professional->received_reviews_count }} reviews)</span>
                            </div>

                            <div class="agent-sold-stat">
                                {{ $professional->professionalProfile?->years_experience ?? 0 }} Years Experience
                            </div>

                            <a href="{{ route('agents.show', $professional) }}" class="agent-view-btn">View details</a>
                        </div>
                    </article>
                @empty
                    <p class="no-agents-text">No agents found.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection