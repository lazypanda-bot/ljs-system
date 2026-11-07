@extends('layouts.client')

@section('title', 'Reviews')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
@endpush

@section('content')
    <div class="listings-management-container staff-page-surface au-page-card">
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        <header class="staff-property-admin-header">
            <div>
                <h2>Reviews</h2>
                <p>All client reviews of professionals.</p>
            </div>
        </header>

        <form class="staff-filter-bar" method="GET" action="{{ route('app.page', ['page' => 'review']) }}">
            <div class="search-box">
                <span class="search-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                <input type="search" name="q" value="{{ $search }}" aria-label="Search reviews" placeholder="Search reviewer, professional, property or comment...">
            </div>
            <select name="rating" aria-label="Filter by rating" data-auto-submit>
                <option value="">All ratings</option>
                @for ($star = 5; $star >= 1; $star--)
                    <option value="{{ $star }}" @selected($rating === $star)>{{ $star }} {{ $star === 1 ? 'star' : 'stars' }}</option>
                @endfor
            </select>
            <select name="professional" aria-label="Filter by professional" data-auto-submit>
                <option value="">All professionals</option>
                <option value="all-agents" @selected($professionalFilter === 'all-agents')>Agents</option>
                <option value="all-brokers" @selected($professionalFilter === 'all-brokers')>Brokers</option>
                <option value="all-lead-brokers" @selected($professionalFilter === 'all-lead-brokers')>Lead Brokers</option>
                {{--
                <optgroup label="Individual">
                    @foreach ($professionals as $professional)
                        <option value="{{ $professional->user_id }}" @selected($professionalId === (int) $professional->user_id)>{{ $professional->name }} ({{ $professional->role_name }})</option>
                    @endforeach
                </optgroup>
                --}}
            </select>
        </form>

        <div class="au-table-container">
            <div class="staff-property-table-wrap">
                <table class="staff-property-table">
                    <thead>
                        <tr>
                            <th>REVIEWER</th>
                            <th>REVIEWED</th>
                            <th>PROPERTY</th>
                            <th>RATING</th>
                            <th>REVIEW</th>
                            <th>DATE</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reviews as $review)
                            @php($reviewed = $review->reviewedUser ?? $review->user)
                            <tr>
                                <td class="fw-semibold">{{ $review->reviewer?->name ?? 'Client' }}</td>
                                <td>{{ $reviewed?->name ?? 'N/A' }}<br><small class="text-muted">{{ $reviewed?->role_name }}</small></td>
                                <td>{{ $review->property?->property_name ?? 'N/A' }}</td>
                                <td><span class="staff-review-stars" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', max(0, 5 - $review->rating)) }}</span></td>
                                <td class="staff-review-text">{{ $review->review }}</td>
                                <td class="text-muted">{{ $review->created_at->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" data-confirm-delete>
                                        @csrf @method('DELETE')
                                        <button class="au-deleteuser-btn" type="submit" aria-label="Delete review"><i class="fas fa-trash text-danger"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="staff-table-empty">No reviews found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($reviews->hasPages())
                {{ $reviews->links('pagination.ljs') }}
            @endif
        </div>
    </div>

    <script src="{{ asset('js/auto-filter.js') }}?v={{ filemtime(public_path('js/auto-filter.js')) }}" defer></script>
    <script>
        document.querySelectorAll('form[data-confirm-delete]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (await window.appConfirm('Delete this review? This action cannot be undone.')) form.submit();
            });
        });
    </script>
@endsection
