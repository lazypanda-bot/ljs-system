@extends('layouts.app')

@section('title', 'Contact LJS Realty and Brokerage')

@section('content')
    <div class="business-contact-page">
        <header class="business-contact-heading">
            <span class="business-eyebrow">GET IN TOUCH</span>
            <h1>Let’s talk property.</h1>
            <p>Ask about a listing, request a viewing, or send a general question to the LJS Realty and Brokerage team.</p>
        </header>

        <div class="business-contact-layout">
            <section class="business-contact-details" aria-labelledby="contactDetailsTitle">
                <h2 id="contactDetailsTitle">Contact details</h2>
                <p class="business-contact-location">Cebu City, Philippines</p>
                <a href="tel:+639213456789"><i data-lucide="phone" aria-hidden="true"></i><span><small>PHONE</small>+63 921 345 6789</span></a>
                <a href="mailto:ljsrealty@gmail.com"><i data-lucide="mail" aria-hidden="true"></i><span><small>EMAIL</small>ljsrealty@gmail.com</span></a>
                <address><i data-lucide="map-pin" aria-hidden="true"></i><span><small>OFFICE</small>Unit 6 Labangon Town Center<br>Katipunan St, Cebu City</span></address>
                {{--
                    IF THIS IS USED, MAKE A TABLE FOR COMPANY_SETTINGS AND UNCOMMENT THIS TO USE.
                    Put the dynamic Contact page details in this file once AppServiceProvider shares $contactInfo.

                    <p class="business-contact-location">{{ $contactInfo['city'] ?? 'Cebu City, Philippines' }}</p>
                    @if(!empty($contactInfo['phone']))
                        <a href="tel:{{ $contactInfo['phone'] }}"><i data-lucide="phone" aria-hidden="true"></i><span><small>PHONE</small>{{ $contactInfo['phone'] }}</span></a>
                    @endif
                    @if(!empty($contactInfo['email']))
                        <a href="mailto:{{ $contactInfo['email'] }}"><i data-lucide="mail" aria-hidden="true"></i><span><small>EMAIL</small>{{ $contactInfo['email'] }}</span></a>
                    @endif
                    @if(!empty($contactInfo['address']))
                        <address><i data-lucide="map-pin" aria-hidden="true"></i><span><small>OFFICE</small>{{ $contactInfo['address'] }}</span></address>
                    @endif
                --}}
            </section>

            <form class="business-contact-form" method="POST" action="{{ route('contact.submit') }}">
                @csrf
                <h2>Send a message</h2>
                <p>We’ll use your contact details to respond to your inquiry.</p>
                @if (session('success'))<p class="business-contact-success" role="status">{{ session('success') }}</p>@endif
                <label for="contactName">Your name</label><input id="contactName" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="100" minlength="2" pattern="[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ .'\-]*" title="Letters, spaces, apostrophes, hyphens and periods only" required>
                @error('name')<span class="business-contact-error">{{ $message }}</span>@enderror
                <label for="contactEmail">Email address</label><input id="contactEmail" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required>
                @error('email')<span class="business-contact-error">{{ $message }}</span>@enderror
                <label for="contactPhone">Phone <span>(optional)</span></label><input id="contactPhone" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" title="11 digits, e.g. 09171234567">
                @error('phone')<span class="business-contact-error">{{ $message }}</span>@enderror
                <label for="contactMessage">How can we help?</label><textarea id="contactMessage" name="message" rows="5" maxlength="3000" required>{{ old('message') }}</textarea>
                @error('message')<span class="business-contact-error">{{ $message }}</span>@enderror
                <button type="submit">Send inquiry</button>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/company-pages.css') }}?v={{ filemtime(public_path('css/company-pages.css')) }}">
@endpush
