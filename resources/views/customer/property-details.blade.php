@extends('layouts.app')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/appointment-create.css') }}?v={{ filemtime(public_path('css/appointment-create.css')) }}">
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

    <section class="property-gallery {{ $property->images->count() > 1 ? 'has-thumbnails' : 'single-image' }}" aria-label="Property photos">
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
                <div class="property-details-title-row">
                    <p class="property-details-price">PHP {{ number_format($property->price, 2) }}</p>
                    <span class="property-details-status property-details-status-{{ \Illuminate\Support\Str::slug($property->property_status ?: 'available') }}">{{ $property->property_status ?: 'Available' }}</span>
                </div>
                <h1>{{ $property->property_name }}</h1>
                <p class="property-details-location"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> {{ collect([$property->city, $property->province])->filter()->implode(', ') }}</p>
                @if (! $property->acceptsAppointments())
                    <p class="property-status-notice property-status-{{ \Illuminate\Support\Str::slug($property->property_status) }}" role="status"><i class="fas fa-circle-info" aria-hidden="true"></i> This property is <strong>{{ $property->property_status }}</strong>. Appointments are closed.</p>
                @endif
            </header>

            @php
                $details = $property->details;
                $visible = \App\Models\Property::visibleDetailFields($property->property_type);
                $show = fn (string $field) => in_array($field, $visible, true);
                $isResidential = \App\Models\Property::isResidentialType($property->property_type);
                $hasValue = fn ($value) => $value !== null && $value !== '' && (float) $value > 0;

                // Which facts make sense for this property type; custom types show whatever was filled in.
                $metrics = collect([
                    ['icon' => 'fa-bed', 'label' => 'Bedrooms', 'value' => ($details?->bedroom ?? 0) . ' beds', 'show' => $show('bedrooms') && ($isResidential || $hasValue($details?->bedroom))],
                    ['icon' => 'fa-bath', 'label' => 'Bathrooms', 'value' => ($details?->bathroom ?? 0) . ' baths', 'show' => $show('bathrooms') && ($isResidential || $hasValue($details?->bathroom))],
                    ['icon' => 'fa-car', 'label' => 'Parking', 'value' => ($details?->parking_spaces ?? 0) . ' parking', 'show' => $show('parking_spaces') && $hasValue($details?->parking_spaces)],
                    ['icon' => 'fa-vector-square', 'label' => 'Floor area', 'value' => number_format($details?->floor_area ?? 0, 2) . ' m²', 'show' => $show('floor_area') && $hasValue($details?->floor_area)],
                    ['icon' => 'fa-expand', 'label' => 'Lot area', 'value' => number_format($details?->lot_area ?? 0, 2) . ' m²', 'show' => $show('lot_area') && $hasValue($details?->lot_area)],
                ])->where('show', true);

                $detailTiles = $metrics
                    ->map(fn ($metric) => [
                        'icon' => $metric['icon'],
                        'label' => $metric['label'],
                        'value' => $metric['value'],
                    ])
                    ->merge(collect([
                        ['icon' => 'fa-house', 'label' => 'Property type', 'value' => $property->property_type],
                        ['icon' => 'fa-layer-group', 'label' => 'Floors', 'value' => $show('number_of_floors') ? $details?->number_of_floors : null],
                        ['icon' => 'fa-calendar', 'label' => 'Year built', 'value' => $show('year_built') ? $details?->year_built : null],
                        ['icon' => 'fa-clipboard-check', 'label' => 'Condition', 'value' => $show('property_condition') ? $details?->property_condition : null],
                    ])->filter(fn ($item) => $item['value'] !== null && $item['value'] !== '' && $item['value'] !== 0))
                    ->values();
            @endphp
            @if ($detailTiles->isNotEmpty() || $details?->remarks)
                <section class="property-details-panel">
                    <h2>Property details</h2>
                    @if ($detailTiles->isNotEmpty())
                        <div class="property-metrics" aria-label="Property facts">
                            @foreach ($detailTiles as $tile)
                                <div><i class="fas {{ $tile['icon'] }}" aria-hidden="true"></i><span>{{ $tile['label'] }}</span><strong>{{ $tile['value'] }}</strong></div>
                            @endforeach
                        </div>
                    @endif
                    @if ($details?->remarks)
                        <div class="property-remarks"><strong>Remarks</strong><p>{{ $details->remarks }}</p></div>
                    @endif
                </section>
            @endif

            @php
                $lifestyleTags = collect(explode(',', (string) $property->lifestyle_tag))->map(fn ($tag) => trim($tag))->filter();
                $locationTags = collect(explode(',', (string) $property->location_tag))->map(fn ($tag) => trim($tag))->filter();
            @endphp
            @if (filled($property->property_description))
                <section class="property-details-panel">
                    <h2>Description</h2>
                    <p class="property-description">{{ $property->property_description }}</p>
                </section>
            @endif

        </div>

        <aside class="property-sidebar">
            <section class="professional-contact-card" aria-labelledby="contactProfessionalHeading">
                <p class="contact-card-kicker">Listing {{ $isAdminListing ? 'administrator' : 'professional' }}</p>
                <h2 id="contactProfessionalHeading">Contact {{ $isAdminListing ? 'administrator' : 'professional' }}</h2>

                <div class="professional-contact-identity">
                    <div class="professional-avatar">
                        @if ($contactUser?->profile_photo)
                            <img src="{{ str_starts_with($contactUser->profile_photo, 'http') ? $contactUser->profile_photo : asset('storage/' . $contactUser->profile_photo) }}" data-fallback-src="{{ asset('images/ljs-logo.jpg') }}" alt="{{ $contactUser->name }}">
                        @else
                            <span aria-hidden="true">{{ strtoupper(substr($contactUser?->name ?? 'L', 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="professional-contact-name">
                        @php $contactIsProfessional = $contactUser && $contactUser->account_status === 'Active' && in_array($contactUser->role_name, ['Broker', 'Agent', 'Lead Broker'], true); @endphp
                        <h3><a id="referralContactName" class="professional-name-link" @if ($contactIsProfessional) href="{{ route('agents.show', $contactUser) }}" @endif>{{ $contactUser->name ?? 'LJS Realty Professional' }}</a></h3>
                        <p id="referralContactRole">{{ $contactUser->role_name ?? 'Listing Professional' }}</p>
                    </div>
                </div>

                <div class="professional-contact-details">
                    <p><i class="fas fa-phone" aria-hidden="true"></i><span id="referralContactPhone">{{ $contactUser?->contact_number ?: 'Contact number not provided' }}</span></p>
                    <p><i class="fas fa-envelope" aria-hidden="true"></i><span id="referralContactEmail">{{ $contactUser->email ?? 'Contact details unavailable' }}</span></p>
                </div>

                <div class="professional-contact-actions">
                    @auth
                        @if ($property->acceptsAppointments())
                            <button type="button" id="referralAppointmentLink" class="contact-action contact-action-secondary" @if ($hasActiveAppointment ?? false) onclick="showPopup('Already booked', 'You already have an active appointment for this property. Wait for it to finish or cancel it before booking again.')" @else onclick="openAppointmentModal()" @endif>
                                Book appointment
                            </button>
                        @else
                            <button type="button" class="contact-action contact-action-secondary" disabled>{{ $property->property_status }} · Booking closed</button>
                        @endif
                    @else
                        <!-- Triggers login or redirects if not logged in -->
                        <button type="button" class="contact-action contact-action-secondary" id="openLoginModal">
                            Sign in to book
                        </button>
                    @endauth
                    
                    @auth
                        @if (auth()->user()?->normalizedRole() === 'client' && ($isAdminListing || $referralCode || in_array($contactUser?->role_name, ['Broker', 'Agent', 'Lead Broker'], true)))
                            <form method="POST" action="{{ $messageUrl }}" id="propertyMessageForm">
                                @csrf
                                <input type="hidden" name="panel" value="1">
                                <input type="hidden" name="recipient_id" id="referralMessageRecipient" value="{{ $contactUser->user_id }}">
                                <button class="contact-action contact-action-primary" type="submit">Send message</button>
                            </form>
                        @endif
                    @else
                        <button class="contact-action contact-action-primary" type="button" id="openLoginModal">Sign in to send a message</button>
                    @endauth
                </div>
            </section>

            @if ($property->details?->amenities?->isNotEmpty())
                <section class="property-details-panel property-sidebar-panel">
                    <h2>Features &amp; amenities</h2>
                    <ul class="property-amenities-list">
                        @foreach ($property->details->amenities as $amenity)
                            <li><i class="fas fa-check-circle" aria-hidden="true"></i>{{ $amenity->amenity_name }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($lifestyleTags->isNotEmpty() || $locationTags->isNotEmpty())
                <section class="property-details-panel property-sidebar-panel">
                    <h2>Lifestyle Match</h2>
                    <div class="property-match-tags">
                        @foreach ($lifestyleTags as $tag)
                            <span><i class="fas fa-heart" aria-hidden="true"></i> {{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $tag)) }}</span>
                        @endforeach
                        @foreach ($locationTags as $tag)
                            <span><i class="fas fa-location-dot" aria-hidden="true"></i> {{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $tag)) }}</span>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </div>

    @auth
    <div id="appointmentModal" class="modal-overlay {{ $errors->any() ? 'active' : '' }}">
        <div class="modal-content appointment-create-page" role="dialog" aria-modal="true" aria-labelledby="appointmentModalTitle">
            <button type="button" class="appointment-modal-close" onclick="closeAppointmentModal()" aria-label="Close">&times;</button>
            <h1 id="appointmentModalTitle">Book a viewing</h1>
            <p>{{ $property->property_name }}</p>
            @if ($errors->any())
                <div class="appointment-errors" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('appointments.store', $property) }}" id="appointmentBookingForm" data-slots-url="{{ route('appointments.availability', $property) }}" data-no-confirm>
                @csrf
                <input type="hidden" name="client_timezone" id="appointmentTimezone" value="{{ auth()->user()->timezone ?? '' }}">
                <input type="hidden" name="slot_start_utc" id="appointmentSlotUtc">
                <input type="hidden" name="assign_id" value="{{ $selectedAssignmentId }}">
                <p>Appointment will be sent to {{ $contactUser?->name }}.</p>
                <label>Name<input name="contact_name" required minlength="2" maxlength="100" pattern="[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ .'\-]*" title="Letters, spaces, apostrophes, hyphens and periods only" value="{{ old('contact_name', auth()->user()->name) }}"></label>
                <label>Contact number<input name="contact_number" type="tel" inputmode="numeric" required maxlength="11" pattern="[0-9]{11}" title="11 digits, e.g. 09171234567" value="{{ old('contact_number', auth()->user()->contact_number) }}"></label>
                <label>Email<input type="email" name="contact_email" required value="{{ old('contact_email', auth()->user()->email) }}"></label>
                <label>Appointment type<select name="appointment_type" required><option>Property viewing</option><option>Consultation</option><option>Site visit</option></select></label>
                <label>Date<input type="date" name="preferred_date" id="appointmentDate" min="{{ now()->toDateString() }}" required></label>
                <label>Time period<select name="time_period" id="appointmentTimePeriod" required disabled>
                    <option value="">Choose a time period</option>
                    <option value="morning">Morning (8:00 AM–12:00 PM)</option>
                    <option value="afternoon">Afternoon (1:00 PM–5:00 PM)</option>
                </select></label>
                <label>Available time<select name="preferred_time" id="appointmentTime" required disabled><option value="">Choose a date first</option></select></label>
                <p class="appointment-slots-status" id="appointmentSlotsStatus" role="status" aria-live="polite">Times are shown in your time zone.</p>
                <label>Notes<textarea name="additional_note" rows="3"></textarea></label>
                <button type="submit">Request appointment</button>
            </form>
        </div>
    </div>
    @endauth
</article>
@endsection

@push('scripts')
    <script src="{{ asset('js/properties.js') }}"></script>
    <script src="{{ asset('js/availability-picker.js') }}?v={{ filemtime(public_path('js/availability-picker.js')) }}" defer></script>
    <script src="{{ asset('js/appointment-booking.js') }}?v={{ filemtime(public_path('js/appointment-booking.js')) }}" defer></script>
    <script>
        document.getElementById('propertyMessageForm')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                const data = await response.json();
                if (!response.ok || !data.url) throw new Error();
                const modal = document.getElementById('clientMessagesModal');
                const frame = document.getElementById('clientMessagesFrame');
                const opener = document.getElementById('clientMessagesButton');
                if (!modal || !frame || !opener) { window.location.href = data.url; return; }
                if (!modal.classList.contains('open')) opener.click();
                frame.src = data.url;
            } catch (error) {
                form.submit();
            } finally {
                button.disabled = false;
            }
        });
    </script>
@endpush
