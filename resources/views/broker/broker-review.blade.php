@extends('layouts.client')

@section('title', 'Broker Review')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/review.css') }}">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')
<div class="reviews-wrapper">
    <!-- Header Section -->
    <div class="reviews-header">
        <h1>Review Summary</h1>
        <p>View feedback and ratings given by clients.</p>
    </div>

    <!-- Metrics Cards Grid -->
    <div class="metrics-grid">
        <!-- Card 1: Total Reviews -->
        <div class="metric-card card-blue">
            <span class="card-title">Total Reviews</span>
            <div class="metric-value-container">
                <span class="metric-number">24</span>
                <span class="trend-badge">
                    <i class="fa-solid fa-chevron-up"></i> 12.5%
                </span>
            </div>
            <span class="card-subtitle">Growth in reviews this month</span>
        </div>

        <!-- Card 2: Average Rating -->
        <div class="metric-card card-teal">
            <span class="card-title">Average Rating</span>
            <div class="metric-value-container">
                <span class="metric-number">4.7</span>
                <div class="stars-row">
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star-half-stroke"></i>
                </div>
            </div>
            <span class="card-subtitle">Average rating this month</span>
        </div>

        <!-- Card 3: Rating Breakdown -->
        <div class="metric-card card-breakdown">
            <div class="rating-bar-row">
                <span class="star-label">5 Stars</span>
                <div class="bar-track"><div class="bar-fill bar-5star" style="width: 75%;"></div></div>
                <span class="star-count">18</span>
            </div>
            <div class="rating-bar-row">
                <span class="star-label">4 Stars</span>
                <div class="bar-track"><div class="bar-fill bar-4star" style="width: 20%;"></div></div>
                <span class="star-count">4</span>
            </div>
            <div class="rating-bar-row">
                <span class="star-label">3 Stars</span>
                <div class="bar-track"><div class="bar-fill bar-3star" style="width: 5%;"></div></div>
                <span class="star-count">1</span>
            </div>
            <div class="rating-bar-row">
                <span class="star-label">2 Stars</span>
                <div class="bar-track"><div class="bar-fill bar-2star" style="width: 5%;"></div></div>
                <span class="star-count">1</span>
            </div>
            <div class="rating-bar-row">
                <span class="star-label">1 Star</span>
                <div class="bar-track"><div class="bar-fill bar-1star" style="width: 0%;"></div></div>
                <span class="star-count">0</span>
            </div>
        </div>
    </div>

    <!-- Reviews List Container -->
    <div class="reviews-list-card">
        <div class="reviews-list" id="reviewsList">
            <!-- Review Item 1 -->
            <div class="review-item" data-id="1">
                <div class="user-avatar">
                    <img src="https://ui-avatars.com/api/?name=John+Dela+Cruz&background=1e293b&color=fff" alt="John Dela Cruz">
                </div>
                <div class="review-content">
                    <div class="review-item-header">
                        <div class="user-info">
                            <h3 class="user-name">John Dela Cruz</h3>
                            <p class="purchased-text">Purchased: <span>Luxury Condo Unit</span></p>
                        </div>
                        <div class="rating-meta">
                            <div class="stars">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <span class="review-date">June 15, 2025</span>
                        </div>
                    </div>
                    <p class="review-comment">Very professional broker. She guided us through the entire process and answered all our questions. The transaction was smooth and transparent. Highly recommended!</p>
                </div>
                <button class="btn-delete" title="Delete review" onclick="deleteReview(1)">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </div>

            <!-- Review Item 2 -->
            <div class="review-item" data-id="2">
                <div class="user-avatar">
                    <img src="https://ui-avatars.com/api/?name=Anna+Reyes&background=e11d48&color=fff" alt="Anna Reyes">
                </div>
                <div class="review-content">
                    <div class="review-item-header">
                        <div class="user-info">
                            <h3 class="user-name">Anna Reyes</h3>
                            <p class="purchased-text">Purchased: <span>Modern Family House</span></p>
                        </div>
                        <div class="rating-meta">
                            <div class="stars">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-regular fa-star"></i>
                            </div>
                            <span class="review-date">July 02, 2025</span>
                        </div>
                    </div>
                    <p class="review-comment">Responsive and helpful throughout the process. She really knows the market and helped us find the perfect home for our family.</p>
                </div>
                <button class="btn-delete" title="Delete review" onclick="deleteReview(2)">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </div>

            <!-- Review Item 3 -->
            <div class="review-item" data-id="3">
                <div class="user-avatar">
                    <img src="https://ui-avatars.com/api/?name=Michael+Tan&background=0284c7&color=fff" alt="Michael Tan">
                </div>
                <div class="review-content">
                    <div class="review-item-header">
                        <div class="user-info">
                            <h3 class="user-name">Michael Tan</h3>
                            <p class="purchased-text">Purchased: <span>Commercial Space</span></p>
                        </div>
                        <div class="rating-meta">
                            <div class="stars">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <span class="review-date">June 28, 2025</span>
                        </div>
                    </div>
                    <p class="review-comment">Great experience working with Ms. Maria. Very knowledgeable and easy to communicate with. Will definitely work with her again.</p>
                </div>
                <button class="btn-delete" title="Delete review" onclick="deleteReview(3)">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </div>

            <!-- Review Item 4 -->
            <div class="review-item" data-id="4">
                <div class="user-avatar">
                    <img src="https://ui-avatars.com/api/?name=Patricia+Gomez&background=7c3aed&color=fff" alt="Patricia Gomez">
                </div>
                <div class="review-content">
                    <div class="review-item-header">
                        <div class="user-info">
                            <h3 class="user-name">Patricia Gomez</h3>
                            <p class="purchased-text">Purchased: <span>2-Bedroom Condo</span></p>
                        </div>
                        <div class="rating-meta">
                            <div class="stars">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <span class="review-date">June 20, 2025</span>
                        </div>
                    </div>
                    <p class="review-comment">She was patient, attentive, and gave great advice. I appreciate her honesty and dedication. Thank you so much!</p>
                </div>
                <button class="btn-delete" title="Delete review" onclick="deleteReview(4)">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </div>
        </div>

        <!-- Footer / Pagination -->
        <div class="reviews-footer">
            <span class="pagination-info">Showing 1–5 of 100 entries</span>
            <div class="pagination">
                <button class="page-btn"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <span class="page-ellipsis">...</span>
                <button class="page-btn">10</button>
                <button class="page-btn"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/review.js') }}"></script>
@endpush