@extends('layouts.client')

@section('title', $property->property_name)

@section('content')
    <div class="staff-property-detail staff-page-surface">
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
        <div class="staff-property-grid">
            <section class="staff-property-panel staff-property-gallery">
                @if ($property->images->isNotEmpty())
                    <img class="staff-property-main-image" src="{{ asset('storage/' . $property->images->first()->image_path) }}" alt="{{ $property->property_name }}">
                    @if ($property->images->count() > 1)
                        <div class="staff-property-thumbnails">
                            @foreach ($property->images->skip(1) as $image)
                                <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $property->property_name }} photo">
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="staff-property-image-empty"><i class="fas fa-house" aria-hidden="true"></i><span>No property photos</span></div>
                @endif
            </section>
            <section class="staff-property-panel">
                <h2>Listing details</h2>
                <dl><div><dt>{{ $property->listing_mode === 'admin' ? 'Listing Admin' : 'Listing professional' }}</dt><dd>{{ $property->user?->name ?? 'Not available' }}</dd></div><div><dt>Type</dt><dd>{{ $property->property_type }}</dd></div><div><dt>Lot area</dt><dd>{{ $property->details?->lot_area ?? 0 }} sqm</dd></div><div><dt>Floor area</dt><dd>{{ $property->details?->floor_area ?? 0 }} sqm</dd></div><div><dt>Bedrooms</dt><dd>{{ $property->details?->bedroom ?? 0 }}</dd></div><div><dt>Bathrooms</dt><dd>{{ $property->details?->bathroom ?? 0 }}</dd></div><div><dt>Parking</dt><dd>{{ $property->details?->parking_spaces ?? 0 }}</dd></div><div><dt>Floors</dt><dd>{{ $property->details?->number_of_floors ?? 1 }}</dd></div></dl>
                <h2>Description</h2><p>{{ $property->property_description ?? 'No description provided.' }}</p>
                @if ($property->details?->amenities?->isNotEmpty())
                    <h2>Amenities</h2><ul class="staff-property-amenities">@foreach ($property->details->amenities as $amenity)<li>{{ $amenity->amenity_name }}</li>@endforeach</ul>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff-property.css') }}?v={{ filemtime(public_path('css/staff-property.css')) }}">
@endpush
