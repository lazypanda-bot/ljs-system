@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
@endpush

@section('content')
@php
    $appointmentParameters = ['property' => $property];
    $messageParameters = ['property' => $property];
    if ($referralCode) {
        $appointmentParameters['ref'] = $referralCode->code;
        $messageParameters['ref'] = $referralCode->code;
        if ($selectedAssignmentId) {
            $appointmentParameters['assign_id'] = $selectedAssignmentId;
        }
    }
    $appointmentUrl = route('appointments.create', $appointmentParameters);
    $messageUrl = route('properties.message', $messageParameters);
@endphp
<div class="property-details-page">
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
        </div>
        
        <!-- Thumbnails & Navigation Arrows -->
        <div class="toolbar">
            <button class="navbutton"><i class="fas fa-chevron-left"></i></button>
            <div class="strip">
                @foreach($property->images as $index => $image)
                    <img src="{{ asset('storage/' . $image->image_path) }}" 
                         class="thumbnail {{ $index === 0 ? 'active' : '' }}" 
                         data-thumbnail-target="mainImage"
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
                    @if($property->details?->amenities?->isNotEmpty())
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
                        <h6 id="referralContactName" class="fw-bold mb-1">{{ $contactUser->name ?? 'LJS Realty Professional' }}</h6>
                        <p id="referralContactRole" class="text-muted small mb-0">{{ $contactUser->role_name ?? 'Listing Professional' }}</p>
                    </div>
                </div>

                <div class="mb-3 text-secondary small">
                    <p class="mb-2"><i class="fas fa-phone-alt text-primary me-2"></i> {{ $contactUser?->contact_number ?: 'Contact number not provided' }}</p>
                    <p class="mb-0"><i class="fas fa-envelope text-primary me-2"></i> <span id="referralContactEmail">{{ $contactUser->email ?? 'Contact details unavailable' }}</span></p>
                </div>

                @if ($referralCode && $isAdminListing && $contactProfessionals->count() > 1)
                    <label class="form-label small fw-bold" for="referralProfessionalSelect">Choose a professional</label>
                    <select class="form-select mb-3" id="referralProfessionalSelect">
                        @foreach ($contactProfessionals as $professionalAssignment)
                            <option value="{{ $professionalAssignment->user_id }}" data-assignment-id="{{ $professionalAssignment->assign_id }}" data-name="{{ $professionalAssignment->user->name }}" data-role="{{ $professionalAssignment->user->role_name }}" data-email="{{ $professionalAssignment->user->email }}" @selected((int) $professionalAssignment->user_id === (int) $contactUser->user_id)>
                                {{ $professionalAssignment->user->name }} ({{ $professionalAssignment->user->role_name }})
                            </option>
                        @endforeach
                    </select>
                @endif

                <hr class="text-muted opacity-25 my-4">

                <a id="referralAppointmentLink" class="actionbutton outlinebutton text-decoration-none text-center" href="{{ $appointmentUrl }}">
                    <i class="far fa-calendar-alt me-2"></i> Book Appointment
                </a>
                @auth
                    @if (auth()->user()?->normalizedRole() === 'client' && ($referralCode || in_array($contactUser?->role_name, ['Broker', 'Agent', 'Lead Broker'], true)))
                        <form method="POST" action="{{ $messageUrl }}">
                            @csrf
                            @if ($referralCode)
                                <input type="hidden" name="recipient_id" id="referralMessageRecipient" value="{{ $contactUser->user_id }}">
                            @endif
                            <button class="actionbutton primarybutton" type="submit"><i class="far fa-paper-plane me-2"></i> Send Message</button>
                        </form>
                    @endif
                @else
                    <button class="actionbutton primarybutton" type="button" id="openLoginModal"><i class="far fa-paper-plane me-2"></i> Sign in to send a message</button>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.getElementById('referralProfessionalSelect')?.addEventListener('change', function () {
            const option = this.selectedOptions[0];
            const recipient = document.getElementById('referralMessageRecipient');
            const appointmentLink = document.getElementById('referralAppointmentLink');
            const contactName = document.getElementById('referralContactName');
            const contactRole = document.getElementById('referralContactRole');
            const contactEmail = document.getElementById('referralContactEmail');

            if (recipient) recipient.value = option.value;
            if (contactName) contactName.textContent = option.dataset.name;
            if (contactRole) contactRole.textContent = option.dataset.role;
            if (contactEmail) contactEmail.textContent = option.dataset.email;
            if (appointmentLink) {
                const url = new URL(appointmentLink.href);
                url.searchParams.set('assign_id', option.dataset.assignmentId);
                appointmentLink.href = url.toString();
            }
        });
    </script>
@endpush