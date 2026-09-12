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
                <span><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> Cebu City, Philippines</span>
                <span><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> Established in 2012</span>
                <span><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg> 1,200+ Properties Sold</span>
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
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <h3>Property Selling</h3>
                    <p>Sell your property for the best value. Our strategic marketing and expert negotiation ensure a smooth and profitable sale.</p>
                </div>

                <div class="about-offer-card">
                    <div class="about-offer-icon-wrap">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </div>
                    <h3>Property Buying</h3>
                    <p>Find your perfect property with our expert guidance. We help you discover the best options that match your needs and budget.</p>
                </div>

                <div class="about-offer-card">
                    <div class="about-offer-icon-wrap">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
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
                        <div class="about-step-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg></div>
                        <span><strong>1. Consultation</strong></span>
                    </div>
                    <div class="about-step-item">
                        <div class="about-step-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></div>
                        <span><strong>2. Property Search</strong></span>
                    </div>
                    <div class="about-step-item">
                        <div class="about-step-icon"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7v10l8 4"></path></svg></div>
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