@extends('layouts.client')

@section('title', $property->property_name)

@section('content')
    <div class="staff-property-detail">
        <a class="staff-property-back" href="{{ route('app.page', ['page' => auth()->user()->actingRole() === 'admin' ? 'property' : 'listings']) }}">&larr; Back to listings</a>
        <div class="staff-property-header">
            <div><p>{{ $property->property_status }}</p><h1>{{ $property->property_name }}</h1><span>{{ $property->city }}, {{ $property->province }}</span></div>
            <strong>PHP {{ number_format($property->price, 0) }}</strong>
        </div>
        <div class="staff-property-grid">
            <section class="staff-property-panel staff-property-gallery">
                @forelse($property->images as $image)
                    <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $property->property_name }}">
                @empty
                    <img src="{{ asset('images/night.png') }}" alt="{{ $property->property_name }}">
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