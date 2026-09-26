@extends('layouts.client')

@section('title', $pageTitle ?? 'Reviews')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/review.css') }}">
@endpush

@section('content')
	@php
		$reviews = [
			['name' => 'John Dela Cruz', 'property' => 'Luxury Condo Unit', 'rating' => 5, 'date' => 'June 15, 2025', 'comment' => 'Very professional broker. She guided us through the entire process and answered all our questions. The transaction was smooth and transparent. Highly recommended!'],
			['name' => 'Anna Reyes', 'property' => 'Modern Family House', 'rating' => 4, 'date' => 'July 02, 2025', 'comment' => 'Responsive and helpful throughout the process. She really knows the market and helped us find the perfect home for our family.'],
			['name' => 'Michael Tan', 'property' => 'Commercial Space', 'rating' => 5, 'date' => 'June 28, 2025', 'comment' => 'Great experience working with Ms. Maria. Very knowledgeable and easy to communicate with. Will definitely work with her again.'],
			['name' => 'Patricia Gomez', 'property' => '2-Bedroom Condo', 'rating' => 5, 'date' => 'June 20, 2025', 'comment' => 'She was patient, attentive, and gave great advice. I appreciate her honesty and dedication. Thank you so much!'],
		];
		$averageRating = round(collect($reviews)->avg('rating'), 1);
		$ratingCounts = collect($reviews)->countBy('rating');
	@endphp

	<div class="reviews-wrapper page-card">
		<div class="reviews-header"><h1>Review Summary</h1><p>View feedback and ratings given by clients.</p></div>
		<div class="metrics-grid">
			<div class="metric-card card-blue"><span class="card-title">Total Reviews</span><div class="metric-value-container"><span class="metric-number">{{ count($reviews) }}</span></div><span class="card-subtitle">Reviews received</span></div>
			<div class="metric-card card-teal"><span class="card-title">Average Rating</span><div class="metric-value-container"><span class="metric-number">{{ $averageRating }}</span><div class="stars-row">@for ($star = 1; $star <= 5; $star++)<i data-lucide="star" class="{{ $star <= round($averageRating) ? 'is-rated' : '' }}" aria-hidden="true"></i>@endfor</div></div><span class="card-subtitle">Average rating from clients</span></div>
			<div class="metric-card card-breakdown">
				@for ($rating = 5; $rating >= 1; $rating--)
					@php $count = $ratingCounts->get($rating, 0); $percentage = count($reviews) ? ($count / count($reviews)) * 100 : 0; @endphp
					<div class="rating-bar-row"><span class="star-label">{{ $rating }} {{ $rating === 1 ? 'Star' : 'Stars' }}</span><div class="bar-track"><div class="bar-fill bar-{{ $rating }}star" data-width="{{ $percentage }}"></div></div><span class="star-count">{{ $count }}</span></div>
				@endfor
			</div>
		</div>
		<div class="reviews-list-card">
			<div class="reviews-list" id="reviewsList">
				@foreach ($reviews as $index => $review)
					<div class="review-item" data-id="{{ $index + 1 }}">
						<div class="user-avatar"><img src="https://ui-avatars.com/api/?name={{ urlencode($review['name']) }}&background=1e293b&color=fff" alt="{{ $review['name'] }}"></div>
						<div class="review-content">
							<div class="review-item-header"><div class="user-info"><h3 class="user-name">{{ $review['name'] }}</h3><p class="purchased-text">Purchased: <span>{{ $review['property'] }}</span></p></div><div class="rating-meta"><div class="stars">@for ($star = 1; $star <= 5; $star++)<i data-lucide="star" class="{{ $star <= $review['rating'] ? 'is-rated' : '' }}" aria-hidden="true"></i>@endfor</div><span class="review-date">{{ $review['date'] }}</span></div></div>
							<p class="review-comment">{{ $review['comment'] }}</p>
						</div>
						@if ($acting === 'admin')<button class="btn-delete" title="Delete review" type="button" data-delete-review="{{ $index + 1 }}"><i data-lucide="trash-2" aria-hidden="true"></i></button>@endif
					</div>
				@endforeach
			</div>
			<div class="reviews-footer"><span class="pagination-info">Showing {{ count($reviews) }} entries</span></div>
		</div>
	</div>
@endsection

@push('scripts')
	<script src="{{ asset('js/review.js') }}?v={{ filemtime(public_path('js/review.js')) }}"></script>
@endpush
