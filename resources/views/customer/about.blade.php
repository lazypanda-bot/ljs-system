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
            <div class="about-company-identity">
                <img class="about-company-logo" src="{{ asset('images/ljs-logo.jpg') }}" alt="LJS Realty and Brokerage logo">
                <div class="about-company-index">01 <span>OUR COMPANY</span></div>
            </div>
            <div class="about-company-copy">
                <span class="about-page-eyebrow">Rooted in Cebu City</span>
                <h2 id="aboutCompanyTitle">Local guidance for real property decisions.</h2>
                <p>LJS Realty and Brokerage helps people explore property opportunities in Cebu City, Philippines. We bring listings and real estate professionals together so clients can compare options and decide what works for them.</p>
                <p>From the first search to a conversation or viewing, our focus is clear information, thoughtful guidance, and a personal next step.</p>
                <div class="about-company-values">
                    <span><i data-lucide="map-pin" aria-hidden="true"></i> Local perspective</span>
                    <span><i data-lucide="messages-square" aria-hidden="true"></i> Personal guidance</span>
                    <span><i data-lucide="compass" aria-hidden="true"></i> Clear next steps</span>
                </div>
            </div>
        </section>

        <section class="about-owner-note" aria-labelledby="aboutOwnerTitle">
            <div class="about-owner-inner">
                <div class="about-owner-symbol"><i data-lucide="quote" aria-hidden="true"></i></div>
                <div class="about-owner-copy">
                    <span class="about-owner-eyebrow">A NOTE FROM THE OWNERS</span>
                    <h2 id="aboutOwnerTitle">Good guidance begins with listening.</h2>
                    <blockquote>We believe every property decision deserves care, clear information, and a personal approach. Our role is to listen to what matters to you, help you explore your options, and support your next step.</blockquote>
                    <p class="about-owner-attribution"><span></span>Owners <small>LJS Realty and Brokerage</small></p>
                </div>
                <figure class="about-owner-image">
                    <img src="{{ asset('images/ljs-family.jpg') }}" alt="LJS Realty and Brokerage team gathered at a company event">
                    <figcaption>Growing together in Cebu City</figcaption>
                </figure>
            </div>
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
                </div>
                <div class="about-journey-grid">
                    <article class="about-journey-step"><span class="about-step-number">01</span><i data-lucide="search" aria-hidden="true"></i><h3>Find a property</h3><p>Explore listings and narrow your search by location, property type, and budget.</p></article>
                    <article class="about-journey-step"><span class="about-step-number">02</span><i data-lucide="messages-square" aria-hidden="true"></i><h3>Talk it through</h3><p>Connect with the professional or listing administrator responsible for the property.</p></article>
                    <article class="about-journey-step"><span class="about-step-number">03</span><i data-lucide="calendar-days" aria-hidden="true"></i><h3>Plan a viewing</h3><p>Request an appointment and coordinate the next step directly with the listing contact.</p></article>
                </div>
            </div>
        </section>

        <section class="business-about-cta">
            <div><span class="business-eyebrow">START EXPLORING</span><h2>Your next move starts with the right listing.</h2></div>
            <div class="business-about-actions"><a href="{{ route('properties') }}" class="business-primary-link">Browse properties</a><button type="button" class="business-secondary-link" id="openContactUsDialog">Contact Us</button></div>
        </section>

        <dialog class="about-contact-dialog" id="contactUsDialog" aria-labelledby="contactUsDialogTitle">
            <div class="about-contact-dialog-header">
                <div>
                    <span class="about-page-eyebrow">GET IN TOUCH</span>
                    <h2 id="contactUsDialogTitle">Contact LJS Realty</h2>
                    <p>Choose the contact option that works for you.</p>
                </div>
                <button type="button" class="about-contact-dialog-close" aria-label="Close contact options"><i data-lucide="x" aria-hidden="true"></i></button>
            </div>
            <div class="about-contact-options">
                @auth
                    @if (Auth::user()->normalizedRole() === 'client')
                        <button type="button" class="about-contact-option" data-contact-open-messages aria-label="Open messages to contact LJS Realty">
                    @else
                        <a class="about-contact-option" href="{{ route('messages.inbox') }}" aria-label="Open messages to contact LJS Realty">
                    @endif
                @else
                    <button type="button" class="about-contact-option" data-open-login-modal data-contact-open-login aria-label="Sign in to message LJS Realty">
                @endauth
                    <span class="about-contact-option-copy"><strong>Email</strong><small>ljsrealty@gmail.com</small></span>
                @auth
                    @if (Auth::user()->normalizedRole() === 'client')
                        </button>
                    @else
                        </a>
                    @endif
                @else
                    </button>
                @endauth
                <div class="about-contact-option">
                    <span class="about-contact-option-icon"><i data-lucide="phone" aria-hidden="true"></i></span>
                    <span class="about-contact-option-copy"><strong>Phone</strong><small>+63 921 345 6789</small></span>
                </div>
                <address class="about-contact-option">
                    <span class="about-contact-option-icon"><i data-lucide="map-pin" aria-hidden="true"></i></span>
                    <span class="about-contact-option-copy"><strong>Office Address</strong><small>Unit 6 Labangon Town Center<br>Katipunan St, Cebu City</small></span>
                </address>
                <a class="about-contact-option about-contact-placeholder" href="" data-contact-placeholder>
                    <span class="about-contact-option-icon"><i data-lucide="share-2" aria-hidden="true"></i></span>
                    <span class="about-contact-option-copy"><strong>Messenger</strong></span>
                </a>
                <a class="about-contact-option about-contact-placeholder" href="" data-contact-placeholder aria-label="X">
                    <span class="about-contact-option-icon"><span class="about-contact-x-mark" aria-hidden="true">X</span></span>
                    <span class="about-contact-option-copy"><strong>X</strong></span>
                </a>
            </div>
        </dialog>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/company-pages.css') }}?v={{ filemtime(public_path('css/company-pages.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/about-page.css') }}?v={{ filemtime(public_path('css/about-page.css')) }}">
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dialog = document.getElementById('contactUsDialog');
            const openButton = document.getElementById('openContactUsDialog');
            const closeButton = dialog?.querySelector('.about-contact-dialog-close');

            openButton?.addEventListener('click', () => dialog?.showModal());
            closeButton?.addEventListener('click', () => dialog?.close());
            dialog?.querySelector('[data-contact-open-login]')?.addEventListener('click', () => dialog.close());
            dialog?.querySelector('[data-contact-open-messages]')?.addEventListener('click', () => {
                dialog.close();
                document.getElementById('clientMessagesButton')?.click();
            });
            dialog?.addEventListener('click', (event) => {
                if (event.target === dialog) dialog.close();
            });
            dialog?.querySelectorAll('[data-contact-placeholder]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    if (!link.getAttribute('href')) event.preventDefault();
                });
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && dialog?.open) dialog.close();
            });
        });
    </script>
@endpush