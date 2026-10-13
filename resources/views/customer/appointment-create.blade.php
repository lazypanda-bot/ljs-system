@extends('layouts.app')
@section('title', 'Book Appointment')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/appointment-create.css') }}?v={{ filemtime(public_path('css/appointment-create.css')) }}">
@endpush
@section('content')
<div class="appointment-create-page">
	<a href="{{ $referralCode ? route('properties.show', ['id' => $property->property_id, 'ref' => $referralCode->code]) : route('properties.show', $property) }}">&larr; Back to property</a>
	<h1>Book a viewing</h1>
	<p>{{ $property->property_name }}</p>
	@if ($errors->any())
		<div class="appointment-errors" role="alert">{{ $errors->first() }}</div>
	@endif
	<form method="POST" action="{{ route('appointments.store', $property) }}" id="appointmentBookingForm" data-slots-url="{{ route('appointments.availability', $property) }}" data-no-confirm>
		@csrf
		<input type="hidden" name="client_timezone" id="appointmentTimezone" value="{{ $clientTimezone ?? '' }}">
		<input type="hidden" name="slot_start_utc" id="appointmentSlotUtc">
		<input type="hidden" name="assign_id" value="{{ $selectedAssignmentId }}">
		<p>Appointment will be sent to {{ $property->user->name }}.</p>
		<label>Name<input name="contact_name" required minlength="2" maxlength="100" pattern="[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ .'\-]*" title="Letters, spaces, apostrophes, hyphens and periods only" value="{{ auth()->user()->name ?? '' }}"></label>
		<label>Contact number<input name="contact_number" type="tel" inputmode="numeric" required maxlength="11" pattern="[0-9]{11}" title="11 digits, e.g. 09171234567" value="{{ auth()->user()->contact_number ?? '' }}"></label>
		<label>Email<input type="email" name="contact_email" required value="{{ auth()->user()->email ?? '' }}"></label>
		<label>Appointment type<select name="appointment_type" required><option>Property viewing</option><option>Consultation</option><option>Site visit</option></select></label>
		<label>Date<input type="date" name="preferred_date" id="appointmentDate" min="{{ now()->toDateString() }}" required></label>
		<label>Available time<select name="preferred_time" id="appointmentTime" required disabled><option value="">Choose a date and professional first</option></select></label>
		<p class="appointment-slots-status" id="appointmentSlotsStatus" role="status" aria-live="polite">Times are shown in your time zone.</p>
		<label>Notes<textarea name="additional_note" rows="4"></textarea></label>
		<button type="submit">Request appointment</button>
	</form>
</div>
@endsection

@push('scripts')
	<script src="{{ asset('js/availability-picker.js') }}?v={{ filemtime(public_path('js/availability-picker.js')) }}" defer></script>
	<script src="{{ asset('js/appointment-booking.js') }}?v={{ filemtime(public_path('js/appointment-booking.js')) }}" defer></script>
@endpush
