@extends('layouts.app')

@section('title', $professional->name . ' | LJS Realty')

@section('content')
    @php
        $photo = $professional->profile_photo
            ? (str_starts_with($professional->profile_photo, 'http') ? $professional->profile_photo : asset('storage/' . $professional->profile_photo))
            : asset('images/ljs-logo.jpg');
        $rating = $professional->received_reviews_avg_rating ? number_format($professional->received_reviews_avg_rating, 1) : 'New';
    @endphp
    <section class="professional-profile-page">
        <a class="profile-back-link" href="{{ route('agents') }}">&larr; All professionals</a>
        <div class="professional-profile-layout">
            <aside class="professional-summary">
                <img class="professional-profile-photo" src="{{ $photo }}" alt="{{ $professional->name }}">
                <h1>{{ $professional->name }}</h1>
                <p class="professional-role">{{ $professional->role_name }}</p>
                <div class="professional-score"><i data-lucide="star" aria-hidden="true"></i> {{ $rating }} <small>({{ $professional->received_reviews_count }} reviews)</small></div>
                <dl class="professional-details">
                    <div><dt>Experience</dt><dd>{{ $professional->professionalProfile?->years_experience ?? 0 }} years</dd></div>
                    <div><dt>{{ in_array($professional->role_name, ['Agent', 'Broker', 'Lead Broker'], true) ? 'Accreditation' : 'PRC License No.' }}</dt><dd>{{ $professional->professionalProfile?->license_number ?? 'Not listed' }}</dd></div>
                    <div><dt>Contact</dt><dd>{{ $professional->contact_number ?? 'Not listed' }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $professional->email }}</dd></div>
                    @if ($supervisors->isNotEmpty())
                        <div><dt>Supervising Broker</dt><dd>@foreach ($supervisors as $supervisor)<a href="{{ route('agents.show', $supervisor) }}">{{ $supervisor->name }}</a>@if (! $loop->last), @endif @endforeach</dd></div>
                    @endif
                </dl>
                @if ($professional->professionalProfile?->professional_bio)
                    <p class="professional-bio">{{ $professional->professionalProfile->professional_bio }}</p>
                @endif
                @auth
                    @if (auth()->user()?->normalizedRole() === 'client')
                        <a class="agent-view-btn" href="{{ route('messages.direct', $professional) }}">Message {{ $professional->role_name }}</a>
                    @endif
                @else
                    <button type="button" class="agent-view-btn" data-open-login-modal>Sign in to message</button>
                @endauth
            </aside>

            <div class="professional-content">
                <section class="profile-content-section">
                    <div class="profile-section-heading"><h2>Current Listings</h2><span>{{ $listings->count() }} available</span></div>
                    <div class="profile-listings-grid">
                        @forelse ($listings as $listing)
                            @php $image = $listing->images->first(); @endphp
                            <article class="rush-card">
                                <div class="rush-card-img-container">
                                    <img src="{{ $image ? asset('storage/' . $image->image_path) : asset('images/ljs-logo.jpg') }}" alt="{{ $listing->property_name }}">
                                    <div class="rush-badges">
                                        @if ($listing->featured)<span class="badge-featured"><i data-lucide="star" aria-hidden="true"></i> Featured</span>@endif
                                        <span class="badge-sale">{{ strtoupper($listing->property_status) }}</span>
                                    </div>
                                    <span class="rush-img-count">1/{{ max(1, $listing->images->count()) }}</span>
                                </div>
                                <div class="rush-card-body">
                                    <div class="rush-price">₱ {{ number_format($listing->price, 0, '.', ',') }}</div>
                                    <h3 class="rush-title">{{ $listing->property_name }}</h3>
                                    <p class="rush-location">{{ collect([$listing->city, $listing->province])->filter()->implode(', ') ?: 'Cebu, Philippines' }}</p>
                                    <hr class="rush-divider">
                                    <div class="rush-meta">
                                        <span>{{ $listing->details?->bedroom ?? 0 }} beds</span>
                                        <span>{{ $listing->details?->bathroom ?? 0 }} baths</span>
                                        <span>{{ $listing->details?->floor_area ?? 0 }} sqm</span>
                                    </div>
                                    <div class="rush-action"><a href="{{ route('properties.show', $listing) }}" class="rush-btn">View Details</a></div>
                                </div>
                            </article>
                        @empty
                            <p class="profile-empty-state">No current listings are available.</p>
                        @endforelse
                    </div>
                </section>

                <section class="profile-content-section" id="reviews">
                    <div class="profile-section-heading"><h2>Reviews</h2><span>{{ $professional->received_reviews_count }} total</span></div>
                    @if (session('success'))<p class="profile-success">{{ session('success') }}</p>@endif
                    @if ($canReview)
                        <button type="button" class="client-review-toggle" data-review-toggle aria-expanded="{{ $errors->any() ? 'true' : 'false' }}" aria-controls="clientReviewForm">
                            {{ $myReview ? 'Edit Review' : 'Write Review' }}
                        </button>
                        <div class="client-review-panel" id="clientReviewForm" @if (! $errors->any()) hidden @endif>
                            <form class="client-review-form" method="POST" action="{{ route('agents.reviews.store', $professional) }}">
                                @csrf
                                <label for="rating">Your rating</label><select id="rating" name="rating" required>@foreach ([5 => 'Excellent', 4 => 'Good', 3 => 'Fair', 2 => 'Poor', 1 => 'Very poor'] as $value => $label)<option value="{{ $value }}" @selected((int) old('rating', $myReview?->rating ?? 5) === $value)>{{ $value }} - {{ $label }}</option>@endforeach</select>
                                <label for="review">Your review</label><textarea id="review" name="review" rows="4" maxlength="2000" required placeholder="Share your experience with this professional.">{{ old('review', $myReview?->review) }}</textarea>
                                @error('review')<small class="client-review-error">{{ $message }}</small>@enderror
                                <div class="client-review-actions">
                                    <button type="submit">Save review</button>
                                </div>
                            </form>
                            @if ($myReview)
                                <form method="POST" action="{{ route('agents.reviews.destroy', $professional) }}" class="client-review-delete-form" data-review-delete>
                                    @csrf @method('DELETE')
                                    <button type="submit">Delete my review</button>
                                </form>
                            @endif
                        </div>
                    @endif
                    <div class="profile-reviews-list">
                        @forelse ($professional->receivedReviews as $review)
                            <article class="profile-review"><div><strong>{{ $review->reviewer?->name ?? 'Verified client' }}</strong><span class="review-rating-icons">@for ($star = 1; $star <= 5; $star++)<i data-lucide="star" class="{{ $star <= $review->rating ? 'is-rated' : '' }}" aria-hidden="true"></i>@endfor</span></div><p>{{ $review->review }}</p><small>{{ $review->created_at->format('M d, Y') }}</small></article>
                        @empty
                            <p class="profile-empty-state">No reviews yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </section>
    <script>
        document.querySelector('[data-review-toggle]')?.addEventListener('click', function () {
            const panel = document.getElementById(this.getAttribute('aria-controls'));
            const willOpen = panel?.hidden;
            if (!panel) return;
            panel.hidden = !willOpen;
            this.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen) panel.querySelector('select, textarea, button')?.focus();
        });
        document.querySelector('[data-review-delete]')?.addEventListener('submit', async function (event) {
            event.preventDefault();
            const confirmed = window.appConfirm
                ? await window.appConfirm('Delete your review? This action cannot be undone.')
                : window.confirm('Delete your review? This action cannot be undone.');
            if (confirmed) this.submit();
        });
    </script>
@endsection
