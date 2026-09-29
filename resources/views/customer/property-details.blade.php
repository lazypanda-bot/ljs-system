@extends('layouts.app')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/appointment-create.css') }}">
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
<article class="property-details-page">
    <nav class="property-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('properties') }}">Properties</a>
        <span aria-hidden="true">/</span>
        <span>{{ $property->property_name }}</span>
    </nav>

    <section class="property-gallery" aria-label="Property photos">
        <div class="property-image-frame">
            @if ($property->images->isNotEmpty())
                <img id="mainImage" class="property-main-image" src="{{ asset('storage/' . $property->images->first()->image_path) }}" data-fallback-src="{{ asset('images/ljs-logo.jpg') }}" alt="{{ $property->property_name }}">
            @else
                <div class="property-image-placeholder">No property photos available</div>
            @endif
        </div>
        @if ($property->images->count() > 1)
            <div class="property-gallery-controls">
                <button class="gallery-nav-button" type="button" id="propertyImagePrevious" aria-label="Previous photo"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                <div class="property-thumbnail-strip">
                    @foreach ($property->images as $index => $image)
                        <img src="{{ asset('storage/' . $image->image_path) }}" class="property-thumbnail {{ $index === 0 ? 'active' : '' }}" data-thumbnail-target="mainImage" data-image-index="{{ $index }}" data-fallback-src="{{ asset('images/ljs-logo.jpg') }}" alt="{{ $property->property_name }} photo {{ $index + 1 }}">
                    @endforeach
                </div>
                <button class="gallery-nav-button" type="button" id="propertyImageNext" aria-label="Next photo"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
            </div>
        @endif
    </section>

    <div class="property-content-layout">
        <div class="property-information-column">
            <header class="property-details-heading">
                <p class="property-details-price">PHP {{ number_format($property->price, 2) }}</p>
                <h1>{{ $property->property_name }}</h1>
                <p class="property-details-location"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> {{ collect([$property->city, $property->province])->filter()->implode(', ') }}</p>
            </header>

            <section class="property-details-panel property-metrics" aria-label="Property details">
                <div><i class="fas fa-bed" aria-hidden="true"></i><span>{{ $property->details?->bedroom ?? 0 }} beds</span></div>
                <div><i class="fas fa-bath" aria-hidden="true"></i><span>{{ $property->details?->bathroom ?? 0 }} baths</span></div>
                <div><i class="fas fa-car" aria-hidden="true"></i><span>{{ $property->details?->parking ?? 0 }} parking</span></div>
                <div><i class="fas fa-vector-square" aria-hidden="true"></i><span>{{ number_format($property->details?->floor_area ?? 0, 2) }} m² floor</span></div>
                <div><i class="fas fa-expand" aria-hidden="true"></i><span>{{ number_format($property->details?->lot_area ?? 0, 2) }} m² lot</span></div>
            </section>

            <section class="property-details-panel">
                <h2>Description</h2>
                <p class="property-description">{{ $property->property_description ?: 'No additional description provided for this listing.' }}</p>
            </section>

            <section class="property-details-panel">
                <h2>Features &amp; amenities</h2>
                @if ($property->details?->amenities?->isNotEmpty())
                    <ul class="property-amenities-list">
                        @foreach ($property->details->amenities as $amenity)
                            <li><i class="fas fa-check-circle" aria-hidden="true"></i>{{ $amenity->amenity_name }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="property-description">No amenities listed for this property.</p>
                @endif
            </section>
        </div>

        <aside class="professional-contact-card" aria-labelledby="contactProfessionalHeading">
            <p class="contact-card-kicker">Listing professional</p>
            <h2 id="contactProfessionalHeading">Contact professional</h2>

            <div class="professional-contact-identity">
                <div class="professional-avatar">
                    @if ($contactUser?->profile_photo)
                        <img src="{{ str_starts_with($contactUser->profile_photo, 'http') ? $contactUser->profile_photo : asset('storage/' . $contactUser->profile_photo) }}" data-fallback-src="{{ asset('images/ljs-logo.jpg') }}" alt="{{ $contactUser->name }}">
                    @else
                        <span aria-hidden="true">{{ strtoupper(substr($contactUser?->name ?? 'L', 0, 1)) }}</span>
                    @endif
                </div>
                <div class="professional-contact-name">
                    <h3 id="referralContactName">{{ $contactUser->name ?? 'LJS Realty Professional' }}</h3>
                    <p id="referralContactRole">{{ $contactUser->role_name ?? 'Listing Professional' }}</p>
                </div>
            </div>

            <div class="professional-contact-details">
                <p><i class="fas fa-phone" aria-hidden="true"></i><span id="referralContactPhone">{{ $contactUser?->contact_number ?: 'Contact number not provided' }}</span></p>
                <p><i class="fas fa-envelope" aria-hidden="true"></i><span id="referralContactEmail">{{ $contactUser->email ?? 'Contact details unavailable' }}</span></p>
            </div>

            @if ($referralCode && $isAdminListing && isset($contactProfessionals) && $contactProfessionals->count() > 1)
                <label class="professional-select-label" for="referralProfessionalSelect">Choose a professional</label>
                <select class="professional-select" id="referralProfessionalSelect">
                    @foreach ($contactProfessionals as $professionalAssignment)
                        <option value="{{ $professionalAssignment->user_id }}" data-assignment-id="{{ $professionalAssignment->assign_id }}" data-name="{{ $professionalAssignment->user->name }}" data-role="{{ $professionalAssignment->user->role_name }}" data-email="{{ $professionalAssignment->user->email }}" data-phone="{{ $professionalAssignment->user->contact_number }}" @selected((int) $professionalAssignment->user_id === (int) $contactUser->user_id)>
                            {{ $professionalAssignment->user->name }} ({{ $professionalAssignment->user->role_name }})
                        </option>
                    @endforeach
                </select>
            @endif

            <div class="professional-contact-actions">
                @auth
                    <button type="button" id="referralAppointmentLink" class="contact-action contact-action-secondary" onclick="openAppointmentModal()">
                        <i class="far fa-calendar-alt" aria-hidden="true"></i> Book appointment
                    </button>
                @else
                    <!-- Triggers login or redirects if not logged in -->
                    <button type="button" class="contact-action contact-action-secondary" id="openLoginModal">
                        <i class="far fa-calendar-alt" aria-hidden="true"></i> Sign in to book
                    </button>
                @endauth
                
                @auth
                    @if (auth()->user()?->normalizedRole() === 'client' && ($referralCode || in_array($contactUser?->role_name, ['Broker', 'Agent', 'Lead Broker'], true)))
                        <form method="POST" action="{{ $messageUrl }}">
                            @csrf
                            @if ($referralCode)
                                <input type="hidden" name="recipient_id" id="referralMessageRecipient" value="{{ $contactUser->user_id }}">
                            @endif
                            <button class="contact-action contact-action-primary" type="submit"><i class="far fa-paper-plane" aria-hidden="true"></i> Send message</button>
                        </form>
                    @endif
                @else
                    <button class="contact-action contact-action-primary" type="button" id="openLoginModal"><i class="far fa-paper-plane" aria-hidden="true"></i> Sign in to send a message</button>
                @endauth
            </div>
        </aside>
    </div>

    <!-- APPOINTMENT MODAL CONTAINER -->
    <div id="appointmentModal" class="modal-overlay">
        <div class="modal-content appointment-create-page">
            
            <button type="button" onclick="closeAppointmentModal()">&times;</button>
            
            <h1>Book an Appointment</h1>
            <p>{{ $property->property_name }}</p>

            <form method="POST" action="{{ route('appointments.store', $property) }}">
                @csrf
                @if ($referralCode)
                    <input type="hidden" name="ref" value="{{ $referralCode->code }}">
                @endif

                <!-- Automatically locked to the correct listing/referral professional's assignment ID -->
                <input type="hidden" name="assign_id" value="{{ $selectedAssignmentId }}">

                <!-- Display info to user so they know who they are booking with -->
                <div class="form-group mb-3">
                    <label>Professional</label>
                    <input type="text" 
                        class="form-control" 
                        value="{{ $contactUser?->name ?? 'Default Listing Agent' }} ({{ $contactUser?->role_name ?? 'Agent' }})" 
                        disabled 
                        readonly>
                    <small class="text-muted">This appointment is automatically routed to the agent handling this listing.</small>
                </div>

                <label>Name<input name="contact_name" required value="{{ auth()->user()->name ?? '' }}"></label>
                <label>Contact number<input name="contact_number" required value="{{ auth()->user()->contact_number ?? '' }}"></label>
                <label>Email<input type="email" name="contact_email" required value="{{ auth()->user()->email ?? '' }}"></label>
                
                <label>Appointment type
                    <select name="appointment_type" required>
                        <option>Property viewing</option>
                        <option>Consultation</option>
                        <option>Site visit</option>
                    </select>
                </label>
                
                <label>Date<input type="date" name="preferred_date" min="{{ now()->toDateString() }}" required></label>
                <label>Time<input type="time" name="preferred_time" required></label>
                <label>Notes<textarea name="additional_note" rows="4"></textarea></label>
                
                <button type="submit">Request appointment</button>
            </form>
        </div>
    </div>
</article>
@endsection

@push('scripts')
    <script src="{{ asset('js/properties.js') }}"></script>
@endpush