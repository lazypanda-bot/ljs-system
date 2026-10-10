@extends('layouts.client')

@section('title', 'Reviews')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
@endpush

@section('content')
    <div class="listings-management-container staff-page-surface au-page-card">
        <header class="staff-property-admin-header">
            <div>
                <h2>Reviews</h2>
                <p>What clients say about you.</p>
            </div>
        </header>

        <form class="staff-filter-bar" method="GET" action="{{ route('app.page', ['page' => 'review']) }}">
            <div class="search-box">
                <span class="search-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                <input type="search" name="q" value="{{ $search }}" aria-label="Search reviews" placeholder="Search reviewer, property or comment...">
            </div>
            <select name="rating" aria-label="Filter by rating" data-auto-submit>
                <option value="">All ratings</option>
                @for ($star = 5; $star >= 1; $star--)
                    <option value="{{ $star }}" @selected($rating === $star)>{{ $star }} {{ $star === 1 ? 'star' : 'stars' }}</option>
                @endfor
            </select>
        </form>

        <div class="au-table-container">
            <div class="staff-property-table-wrap">
                <table class="staff-property-table">
                    <thead><tr><th>REVIEWER</th><th>PROPERTY</th><th>RATING</th><th>REVIEW</th><th>DATE</th></tr></thead>
                    <tbody>
                        @forelse ($reviews as $review)
                            <tr>
                                <td class="fw-semibold">{{ $review->reviewer?->name ?? 'Client' }}</td>
                                <td>{{ $review->property?->property_name ?? 'N/A' }}</td>
                                <td><span class="staff-review-stars" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', max(0, 5 - $review->rating)) }}</span></td>
                                <td class="staff-review-text">{{ $review->review }}</td>
                                <td class="text-muted">{{ $review->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="staff-table-empty">No reviews found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($reviews->hasPages())
                {{ $reviews->links('pagination.ljs') }}
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/auto-filter.js') }}?v={{ filemtime(public_path('js/auto-filter.js')) }}" defer></script>
@endpush
