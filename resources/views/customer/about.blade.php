@extends('layouts.app')

@section('title', 'About LJS Realty and Brokerage')

@section('content')
    <div class="business-about-page">
        <section class="business-about-hero" aria-labelledby="aboutTitle">
            <div class="business-about-hero-content">
                <span class="business-eyebrow">LOCAL REAL ESTATE GUIDANCE</span>
                <h1 id="aboutTitle">LJS Realty and Brokerage</h1>
                <p>Helping people explore property opportunities in Cebu City, Philippines.</p>
            </div>
            <span class="business-hero-caption">Property search · Buying · Selling · Leasing</span>
        </section>

        <section class="about-company-section" aria-labelledby="aboutCompanyTitle">
            <div class="about-company-copy">
                <span class="about-page-eyebrow">WHO WE ARE</span>
                <h2 id="aboutCompanyTitle">Local guidance for real property decisions.</h2>
                <p>LJS Realty and Brokerage helps clients explore property opportunities in Cebu City, Philippines. We bring listings, licensed professionals, and appointment coordination into one clear experience.</p>
                <p>Whether you are searching, selling, leasing, or preparing for a viewing, our work is to make the next step easier to understand.</p>
                <div class="about-company-values">
                    <span><i data-lucide="map-pin" aria-hidden="true"></i> Cebu-focused guidance</span>
                    <span><i data-lucide="badge-check" aria-hidden="true"></i> Professional support</span>
                    <span><i data-lucide="calendar-check" aria-hidden="true"></i> Clear appointment flow</span>
                </div>
            </div>
            <figure class="about-company-visual">
                <img src="{{ asset('images/ljs-family.jpg') }}" alt="LJS Realty and Brokerage team gathered at a company event">
                <figcaption>Rooted in service, built around real property decisions.</figcaption>
            </figure>
        </section>

        <section class="about-offer-section" aria-labelledby="aboutOfferTitle">
            <div class="about-content-width">
                <div class="about-section-heading">
                    <span class="about-page-eyebrow">WHAT WE OFFER</span>
                    <h2 id="aboutOfferTitle">The right support for your next move.</h2>
                    <p>Explore, buy, sell, or lease with help from local real estate professionals.</p>
                </div>
                <div class="about-offer-grid">
                    <article class="about-offer-card">
                        <span class="about-offer-watermark"><i data-lucide="house" aria-hidden="true"></i></span>
                        <span class="about-offer-number">01 / BUY</span>
                        <h3>Find your place</h3>
                        <p>Browse homes and developments, compare details, and connect with the listing professional.</p>
                    </article>
                    <article class="about-offer-card">
                        <span class="about-offer-watermark"><i data-lucide="tag" aria-hidden="true"></i></span>
                        <span class="about-offer-number">02 / SELL</span>
                        <h3>Bring your property to market</h3>
                        <p>Share your property with people looking for opportunities in Cebu and beyond.</p>
                    </article>
                    <article class="about-offer-card">
                        <span class="about-offer-watermark"><i data-lucide="key-round" aria-hidden="true"></i></span>
                        <span class="about-offer-number">03 / LEASE</span>
                        <h3>Make a place yours</h3>
                        <p>Explore rental options and coordinate questions or viewings with the listing contact.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="about-journey-section" aria-labelledby="aboutJourneyTitle">
            <div class="about-content-width">
                <div class="about-section-heading">
                    <span class="about-page-eyebrow">HOW WE CAN HELP</span>
                    <h2 id="aboutJourneyTitle">From first search to next step.</h2>
                    <p>A simple path for clients who want to compare properties, ask questions, and plan a visit.</p>
                </div>
                <div class="about-journey-grid">
                    <article class="about-journey-step"><span class="about-step-number">01</span><i data-lucide="search" aria-hidden="true"></i><h3>Find a property</h3><p>Explore listings and narrow your search by location, property type, and budget.</p></article>
                    <article class="about-journey-step"><span class="about-step-number">02</span><i data-lucide="messages-square" aria-hidden="true"></i><h3>Talk it through</h3><p>Connect with the professional or listing administrator responsible for the property.</p></article>
                    <article class="about-journey-step"><span class="about-step-number">03</span><i data-lucide="calendar-days" aria-hidden="true"></i><h3>Plan a viewing</h3><p>Request an appointment and coordinate the next step directly with the listing contact.</p></article>
                </div>
            </div>
        </section>

        <section class="about-trust-section" aria-labelledby="aboutTrustTitle">
            <div class="about-content-width about-trust-layout">
                <div class="about-section-heading">
                    <span class="about-page-eyebrow">WHY CLIENTS CHOOSE US</span>
                    <h2 id="aboutTrustTitle">Practical help, transparent details, and people you can reach.</h2>
                </div>
                <div class="about-trust-grid">
                    <article><i data-lucide="file-search" aria-hidden="true"></i><h3>Clear listing details</h3><p>Property information is organized so clients can compare options without digging through scattered messages.</p></article>
                    <article><i data-lucide="users-round" aria-hidden="true"></i><h3>Professional connection</h3><p>Clients can reach the people responsible for each listing and continue the conversation directly.</p></article>
                    <article><i data-lucide="clock" aria-hidden="true"></i><h3>Appointment updates</h3><p>Viewing requests and schedule changes are easier to follow, including important updates sent to clients.</p></article>
                    <article><i data-lucide="home" aria-hidden="true"></i><h3>Local property focus</h3><p>The experience is shaped around Cebu property search, listing, leasing, and viewing workflows.</p></article>
                </div>
            </div>
        </section>

        <section class="about-professionals-section" aria-labelledby="aboutProfessionalsTitle">
            <div class="about-content-width about-professionals-card">
                <div>
                    <span class="about-page-eyebrow">OUR PROFESSIONALS</span>
                    <h2 id="aboutProfessionalsTitle">Work with brokers and agents who can guide the next step.</h2>
                    <p>Browse professional profiles, check available listings, and connect with the right contact for the property you are interested in.</p>
                </div>
                <a href="{{ route('agents') }}" class="business-secondary-link">View professionals</a>
            </div>
        </section>

        <section class="business-about-cta">
            <div><span class="business-eyebrow">START EXPLORING</span><h2>Your next move starts with the right listing.</h2></div>
            <div class="business-about-actions"><a href="{{ route('properties') }}" class="business-primary-link">Browse properties</a><a href="{{ route('agents') }}" class="business-secondary-link">Find an agent</a></div>
        </section>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/company-pages.css') }}?v={{ filemtime(public_path('css/company-pages.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/about-page.css') }}?v={{ filemtime(public_path('css/about-page.css')) }}">
@endpush

