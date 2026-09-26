@extends('layouts.client')

@section('title', $property->property_name)

@section('content')
    <div class="staff-property-detail">
        <a class="staff-property-back" href="{{ route('app.page', ['page' => auth()->user()->actingRole() === 'admin' ? 'property' : 'listings']) }}">&larr; Back to listings</a>
        <div class="staff-property-header">
            <div><p>{{ $property->property_status }}</p><h1>{{ $property->property_name }}</h1><span>{{ $property->city }}, {{ $property->province }}</span></div>
            <strong>PHP {{ number_format($property->price, 0) }}</strong>
        </div>
        @if ($property->approval_status === 'Rejected' && (int) $property->user_id === (int) auth()->id())
            <div class="listing-rejection-note"><strong>Admin requested these changes</strong><p>{{ $property->rejection_note }}</p><a href="{{ route('admin.properties.edit', $property) }}">Edit and resubmit</a></div>
        @elseif ($property->approval_status === 'Pending' && (int) $property->user_id === (int) auth()->id())
            <p class="listing-review-status">Awaiting Admin approval</p>
        @endif
        @if ($showReferralUnavailable)
            <section class="staff-property-panel referral-share-panel">
                <h2>Property referral link</h2>
                <p>{{ $property->approval_status === 'Rejected' ? 'Resubmit this listing for Admin approval. A referral link will be generated when it is approved.' : 'A referral link will be generated after Admin approval.' }}</p>
            </section>
        @elseif ($canGenerateReferral)
            <section class="staff-property-panel referral-share-panel">
                <h2>Property referral link</h2>
                @if ($referralCode)
                    <label for="propertyReferralLink">Share this link to route inquiries to your account.</label>
                    <div class="referral-link-row">
                        <input id="propertyReferralLink" type="text" readonly value="{{ route('referrals.open', $referralCode->code) }}">
                        <button type="button" id="copyPropertyReferralLink">Copy link</button>
                    </div>
                @else
                    <form method="POST" action="{{ route('agent.properties.generate-code', $property->property_id) }}">
                        @csrf
                        <button type="submit" class="btn-assign-property">Generate referral link</button>
                    </form>
                @endif
            </section>
        @endif
        <div class="staff-property-grid">
            <section class="staff-property-panel staff-property-gallery">
                @forelse($property->images as $image)
                    <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $property->property_name }}">
                @empty
                    <img src="{{ asset('images/ljs-logo.jpg') }}" alt="{{ $property->property_name }}">
                @endforelse
            </section>
            <section class="staff-property-panel">
                <h2>Listing details</h2>
                <dl><div><dt>Professional</dt><dd>{{ $property->user?->name ?? 'Not assigned' }}</dd></div><div><dt>Type</dt><dd>{{ $property->property_type }}</dd></div><div><dt>Lot area</dt><dd>{{ $property->details?->lot_area ?? 0 }} sqm</dd></div><div><dt>Floor area</dt><dd>{{ $property->details?->floor_area ?? 0 }} sqm</dd></div><div><dt>Bedrooms</dt><dd>{{ $property->details?->bedroom ?? 0 }}</dd></div><div><dt>Bathrooms</dt><dd>{{ $property->details?->bathroom ?? 0 }}</dd></div></dl>
                <h2>Description</h2><p>{{ $property->property_description ?? 'No description provided.' }}</p>
            </section>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff-property.css') }}">
@endpush

@push('scripts')
    <script>
        document.getElementById('copyPropertyReferralLink')?.addEventListener('click', async function () {
            const link = document.getElementById('propertyReferralLink');
            await navigator.clipboard.writeText(link.value);
            this.textContent = 'Copied';
        });
    </script>
@endpush