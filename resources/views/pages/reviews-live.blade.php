@extends('layouts.client')
@section('title', 'Reviews')
@section('content')
<div class="live-report-page staff-page-surface"><div class="live-review-list">@forelse($reviews as $review)<article><div><strong>{{ $review->reviewer?->name ?? 'Client' }}</strong><span class="review-rating-icons">@for ($star = 1; $star <= 5; $star++)<i data-lucide="star" class="{{ $star <= $review->rating ? 'is-rated' : '' }}" aria-hidden="true"></i>@endfor</span></div><p>{{ $review->review }}</p><small>{{ $review->property?->property_name ?? 'Property review' }} · {{ $review->created_at->format('M d, Y') }}</small></article>@empty<p class="live-empty">No reviews found.</p>@endforelse</div></div>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('css/staff-pages.css') }}?v={{ filemtime(public_path('css/staff-pages.css')) }}">@endpush