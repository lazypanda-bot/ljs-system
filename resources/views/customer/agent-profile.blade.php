@extends('layouts.app')

@section('title', $professional->name . ' | LJS Realty')

@section('content')
    @php
        $photo = $professional->profile_photo ? asset('storage/' . $professional->profile_photo) : asset('images/ljs-logo.jpg');
        $rating = $professional->received_reviews_avg_rating ? number_format($professional->received_reviews_avg_rating, 1) : 'New';
    @endphp
    <section class="professional-profile-page">
        <a class="profile-back-link" href="{{ route('agents') }}">&larr; All professionals</a>
        <div class="professional-profile-layout">
            <aside class="professional-summary">
                <img class="professional-profile-photo" src="{{ $photo }}" alt="{{ $professional->name }}">
                <h1>{{ $professional->name }}</h1>
                <p class="professional-role">{{ $professional->role_name }}</p>
                <div class="professional-score"><span>★</span> {{ $rating }} <small>({{ $professional->received_reviews_count }} reviews)</small></div>
                <dl class="professional-details">
                    <div><dt>Experience</dt><dd>{{ $professional->professionalProfile?->years_experience ?? 0 }} years</dd></div>
                    <div><dt>License</dt><dd>{{ $professional->professionalProfile?->license_number ?? 'Not listed' }}</dd></div>
                    <div><dt>Contact</dt><dd>{{ $professional->contact_number ?? 'Not listed' }}</dd></div>
                </dl>
                @if ($professional->professionalProfile?->professional_bio)
                    <p class="professional-bio">{{ $professional->professionalProfile->professional_bio }}</p>
                @endif
                @auth
                    @if (auth()->user()?->normalizedRole() === 'client')
                        <a class="agent-view-btn" href="{{ route('messages.direct', $professional) }}">Message {{ $professional->role_name }}</a>
                    @endif
                @endauth
            </aside>

            <div class="professional-content">
                <section class="profile-content-section">
                    <div class="profile-section-heading"><h2>Current Listings</h2><span>{{ $listings->count() }} available</span></div>
                    <div class="profile-listings-grid">
                        @forelse ($listings as $listing)
                            @php $image = $listing->images->first(); @endphp
                            <article class="profile-listing-card">
                                <img src="{{ $image ? asset('storage/' . $image->image_path) : asset('images/ljs-logo.jpg') }}" alt="{{ $listing->property_name }}">
                                <div><span>{{ $listing->property_status }}</span><h3>{{ $listing->property_name }}</h3><p>PHP {{ number_format($listing->price, 0) }}</p><a href="{{ route('properties.show', $listing) }}">View listing</a></div>
                            </article>
                        @empty
                            <p class="profile-empty-state">No current listings are available.</p>
                        @endforelse
                    </div>
                </section>

                <section class="profile-content-section">
                    <div class="profile-section-heading"><h2>Reviews</h2><span>{{ $professional->received_reviews_count }} total</span></div>
                    @if (session('success'))<p class="profile-success">{{ session('success') }}</p>@endif
                    @if ($canReview)
                        <form class="client-review-form" method="POST" action="{{ route('agents.reviews.store', $professional) }}">
                            @csrf
                            <label for="rating">Your rating</label><select id="rating" name="rating" required><option value="5">5 - Excellent</option><option value="4">4 - Good</option><option value="3">3 - Fair</option><option value="2">2 - Poor</option><option value="1">1 - Very poor</option></select>
                            <label for="review">Your review</label><textarea id="review" name="review" rows="4" maxlength="2000" required placeholder="Share your experience with this professional."></textarea>
                            <button type="submit">Save review</button>
                        </form>
                    @endif
                    <div class="profile-reviews-list">
                        @forelse ($professional->receivedReviews as $review)
                            <article class="profile-review"><div><strong>{{ $review->reviewer?->name ?? 'Verified client' }}</strong><span>{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span></div><p>{{ $review->review }}</p><small>{{ $review->created_at->format('M d, Y') }}</small></article>
                        @empty
                            <p class="profile-empty-state">No reviews yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </section>
@endsection