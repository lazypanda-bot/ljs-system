@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav class="mb-4 text-muted small">
        <a href="{{ route('home') }}" class="text-decoration-none text-muted">Properties</a> 
        <span class="mx-2">></span> 
        <span class="text-dark">{{ $property->property_name }}</span>
    </nav>

    <!-- Main Image Gallery Section -->
    <div class="gallery">
        <div class="wrapper">
            @if($property->images->isNotEmpty())
                <img id="mainImage" src="{{ asset('storage/' . $property->images->first()->image_path) }}" class="banner" alt="Property Image">
            @else
                <img src="{{ asset('images/default-property.jpg') }}" class="banner" alt="Default Image">
            @endif
            <button class="favorite">
                <i class="far fa-heart text-primary fs-5"></i>
            </button>
        </div>
        
        <!-- Thumbnails & Navigation Arrows -->
        <div class="toolbar">
            <button class="navbutton"><i class="fas fa-chevron-left"></i></button>
            <div class="strip">
                @foreach($property->images as $index => $image)
                    <img src="{{ asset('storage/' . $image->image_path) }}" 
                         class="thumbnail {{ $index === 0 ? 'active' : '' }}" 
                         onclick="document.getElementById('mainImage').src=this.src"
                         alt="Thumbnail">
                @endforeach
            </div>
            <button class="navbutton"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

    <!-- Main Content & Contact Sidebar -->
    <div class="row g-4">
        <!-- Left Column: Details & Description -->
        <div class="col-lg-8">
            <!-- Price and Title -->
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h1 class="fw-bold text-primary mb-1">₱{{ number_format($property->price, 2) }}</h1>
                    <h3 class="fw-bold text-dark mb-2">{{ $property->property_name }}</h3>
                    <p class="text-muted mb-0"><i class="fas fa-map-marker-alt text-danger me-1"></i> {{ $property->property_location }}</p>
                </div>
            </div>

            <!-- Property Metrics Bar -->
            <div class="panel">
                <div class="d-flex justify-content-around text-muted small fw-semibold">
                    <span><i class="fas fa-bed text-primary me-2"></i> {{ $property->details->bedrooms ?? 0 }} beds</span>
                    <span><i class="fas fa-bath text-primary me-2"></i> {{ $property->details->bathrooms ?? 0 }} baths</span>
                    <span><i class="fas fa-car text-primary me-2"></i> {{ $property->details->parking ?? 1 }} parking</span>
                    <span><i class="fas fa-vector-square text-primary me-2"></i> {{ $property->details->floor_area ?? 0 }} m² floor</span>
                    <span><i class="fas fa-expand text-primary me-2"></i> {{ $property->details->lot_area ?? 0 }} m² lot</span>
                </div>
            </div>

            <!-- Description Box -->
            <div class="panel">
                <h4 class="fw-bold mb-3">Description</h4>
                <div class="description">
                    {{ $property->details->description ?? 'No additional description provided for this listing.' }}
                </div>
            </div>

            <!-- Features & Amenities Box -->
            <div class="panel">
                <h4 class="fw-bold mb-4">Features & amenities</h4>
                <div class="amenities">
                    @if($property->details && $property->details->amenities->isNotEmpty())
                        @foreach($property->details->amenities as $amenity)
                            <div class="d-flex align-items-center gap-2 text-secondary">
                                <i class="fas fa-check-circle text-primary"></i> {{ $amenity->amenity_name }}
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted small mb-0">No amenities listed for this property.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Contact Agent Card -->
        <div class="col-lg-4">
            <div class="panel sidebar">
                <p class="text-uppercase text-muted small fw-bold mb-3">Listing Professional</p>
                <h5 class="fw-bold mb-4">Contact agent</h5>

                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="{{ asset('images/default-avatar.png') }}" class="avatar" alt="Agent">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $property->user->name ?? 'Maria Samantha Reyes' }}</h6>
                        <p class="text-muted small mb-0">Real Estate Broker</p>
                    </div>
                </div>

                <div class="mb-3 text-secondary small">
                    <p class="mb-2"><i class="fas fa-phone-alt text-primary me-2"></i> +63 917 123 4567</p>
                    <p class="mb-0"><i class="fas fa-envelope text-primary me-2"></i> {{ $property->user->email ?? 'agent@lsrealty.ph' }}</p>
                </div>

                <hr class="text-muted opacity-25 my-4">

                <button class="actionbutton outlinebutton">
                    <i class="far fa-calendar-alt me-2"></i> Book Appointment
                </button>
                <button class="actionbutton primarybutton">
                    <i class="far fa-paper-plane me-2"></i> Send Message
                </button>
            </div>
        </div>
    </div>
</div>
@endsection