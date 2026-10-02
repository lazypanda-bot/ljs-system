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
	<form method="POST" action="{{ route('appointments.store', $property) }}" id="appointmentBookingForm" data-slots-url="{{ route('appointments.availability', $property) }}">
		@csrf
		<input type="hidden" name="client_timezone" id="appointmentTimezone" value="{{ $clientTimezone ?? '' }}">
		<input type="hidden" name="slot_start_utc" id="appointmentSlotUtc">
		@if ($isAdminListing)
			<input type="hidden" name="assign_id" value="{{ $selectedAssignmentId }}">
			<p>Appointment will be sent to {{ $property->user->name }}.</p>
		@elseif ($referralCode)
			<input type="hidden" name="ref" value="{{ $referralCode->code }}">
			@if ($isAdminListing && $assignments->count() > 1)
				<label>Professional<select name="assign_id" required>@foreach($assignments as $assignment)<option value="{{ $assignment->assign_id }}" @selected((int) $assignment->assign_id === (int) $selectedAssignmentId)>{{ $assignment->user->name }} ({{ $assignment->user->role_name }})</option>@endforeach</select></label>
				<p>The professional from your referral is selected first. You can choose another assigned professional.</p>
			@else
				<input type="hidden" name="assign_id" value="{{ $selectedAssignmentId }}">
				<p>Appointment will be sent to {{ $assignments->first()->user->name }}.</p>
			@endif
		@else
			<label>Professional
				<select name="assign_id" required>
					@foreach($assignments as $assignment)
						<option value="{{ $assignment->assign_id }}" @selected((int) $assignment->assign_id === (int) $selectedAssignmentId)>
							{{ $assignment->user?->name ?? 'Unknown Professional' }}
						</option>
					@endforeach
				</select>
			</label>
		@endif
		<label>Name<input name="contact_name" required value="{{ auth()->user()->name ?? '' }}"></label>
		<label>Contact number<input name="contact_number" required value="{{ auth()->user()->contact_number ?? '' }}"></label>
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
	<script src="{{ asset('js/appointment-booking.js') }}?v={{ filemtime(public_path('js/appointment-booking.js')) }}" defer></script>
@endpush