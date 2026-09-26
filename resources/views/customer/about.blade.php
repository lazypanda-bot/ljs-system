@extends('layouts.app')

@section('content')
<section class="about-page-wrapper">
    <!-- Hero Section acting as the full image banner -->
    <div class="about-hero-section">
        <div class="about-hero-overlay"></div>
        <div class="container about-hero-content">
            <span class="about-tag">ABOUT US</span>
            <h1>LJS Realty & Brokerage</h1>
            <div class="about-meta-info">
                <span><i data-lucide="map-pin" class="about-meta-icon" aria-hidden="true"></i> Cebu City, Philippines</span>
                <span><i data-lucide="calendar-days" class="about-meta-icon" aria-hidden="true"></i> Established in 2012</span>
                <span><i data-lucide="trending-up" class="about-meta-icon" aria-hidden="true"></i> 1,200+ Properties Sold</span>
            </div>
        </div>
    </div>

    <section class="company-section">
        <div class="company-container">        
            <div class="company-image-wrapper">
                <div class="dot-pattern top-dots"></div>
                <div class="image-card">
                    <img src="images/day.png" alt="image">
                </div>
            </div>

            <div class="company-content-wrapper">
                <div class="company-text-side">
                    <span class="company-tag">Our Company</span>
                    <p>Company</p>
                    <p>Company</p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-offer-section">
        <div class="container">
            <div class="about-offer-header-row">
                <div class="about-offer-title-wrap">
                    <h2 class="about-section-heading">What We Offer</h2>
                    <div class="about-heading-line"></div>
                </div>
                <p class="about-offer-description">
                    Etiam sit amet fringilla lacus. Pellentesque suscipit ante at ullamcorper pulvinar neque porttitor integer lectus.
                </p>
            </div>

            <div class="about-offer-cards-grid">
                <div class="about-offer-card">
                    <div class="about-offer-icon-wrap">
                        <i data-lucide="house" class="about-offer-icon" aria-hidden="true"></i>
                    </div>
                    <h3>Property Selling</h3>
                    <p>Sell your property for the best value. Our strategic marketing and expert negotiation ensure a smooth and profitable sale.</p>
                </div>

                <div class="about-offer-card">
                    <div class="about-offer-icon-wrap">
                        <i data-lucide="search" class="about-offer-icon" aria-hidden="true"></i>
                    </div>
                    <h3>Property Buying</h3>
                    <p>Find your perfect property with our expert guidance. We help you discover the best options that match your needs and budget.</p>
                </div>

                <div class="about-offer-card">
                    <div class="about-offer-icon-wrap">
                        <i data-lucide="key-round" class="about-offer-icon" aria-hidden="true"></i>
                    </div>
                    <h3>Property Leasing</h3>
                    <p>Looking to rent? We offer a wide selection of quality rental properties and hassle-free leasing solutions.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-work-section">
        <div class="container">
            <div class="about-work-grid">
                <div class="about-work-text">
                    <h2>How we work?</h2>
                    <p>Lorem ipsum dolor sit amet casume adipisini elit. Maximus ligula semper metus pellentesque mattis volutpat, lorem diam enim.</p>
                </div>
                <div class="about-steps-flex">
                    <div class="about-step-item">
                        <div class="about-step-icon"><i data-lucide="phone" aria-hidden="true"></i></div>
                        <span><strong>1. Consultation</strong></span>
                    </div>
                    <div class="about-step-item">
                        <div class="about-step-icon"><i data-lucide="search" aria-hidden="true"></i></div>
                        <span><strong>2. Property Search</strong></span>
                    </div>
                    <div class="about-step-item">
                        <div class="about-step-icon"><i data-lucide="package-check" aria-hidden="true"></i></div>
                        <span><strong>3. Closing Deal</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-cta-section">
        <div class="container text-center">
            <p>Take the next step toward your real estate goals. Explore our listings or connect with our brokers and agents for personalized assistance.</p>
            <a href="{{ route('properties') }}" class="about-btn-explore">Explore Now</a>
        </div>
    </section>
</section>
@endsection