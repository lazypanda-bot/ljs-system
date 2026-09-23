@extends('layouts.client')
@section('title', 'Reviews')
@section('content')
<div class="live-report-page page-card"><div class="live-page-header"><span>CLIENT FEEDBACK</span><h1>Reviews</h1><p>Reviews visible to your current account.</p></div><div class="live-review-list">@forelse($reviews as $review)<article><div><strong>{{ $review->reviewer?->name ?? 'Client' }}</strong><span>{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span></div><p>{{ $review->review }}</p><small>{{ $review->property?->property_name ?? 'Property review' }} · {{ $review->created_at->format('M d, Y') }}</small></article>@empty<p class="live-empty">No reviews found.</p>@endforelse</div></div>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('css/staff-pages.css') }}?v={{ filemtime(public_path('css/staff-pages.css')) }}">@endpush