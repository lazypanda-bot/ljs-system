@extends('layouts.client')

@section('title', 'Appointments')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/appointments.css') }}?v={{ filemtime(public_path('css/appointments.css')) }}">
@endpush

@section('content')
    <div class="appointments-page staff-page-surface">
        <div class="appointments-page-header">
            <div>
                <span>PROPERTY SCHEDULE</span>
                <h1>Appointments</h1>
                <p>Review scheduled viewings and appointment requests assigned to your account.</p>
            </div>
            <form method="GET">
                <select name="status" data-auto-submit>
                    <option value="">All statuses</option>
                    <option value="active" {{ ($filter ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="cancelled" {{ ($filter ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="done" {{ ($filter ?? '') === 'done' ? 'selected' : '' }}>Done</option>
                </select>
            </form>
        </div>

        <div class="appointments-list">
            @forelse ($appointments as $appointment)
                <article class="appointment-row">
                    <div class="appointment-date"><strong>{{ $appointment->preferred_date->format('d') }}</strong><span>{{ $appointment->preferred_date->format('M Y') }}</span></div>
                    <div class="appointment-main">
                        <h2>{{ $appointment->assignment?->property?->property_name ?? 'Property appointment' }}</h2>
                        <p>{{ $appointment->contact_name }} · {{ $appointment->preferred_time }}</p>
                        <small>{{ $appointment->appointment_type }}{{ $appointment->additional_note ? ' · ' . $appointment->additional_note : '' }}</small>
                    </div>
                    <div class="appointment-actions">
                        <span class="appointment-status status-{{ strtolower($appointment->appointment_status) }}">{{ $appointment->appointment_status }}</span>
                        @auth
                            @if (auth()->user()->actingRole() === 'admin' || $appointment->assignment?->user_id === auth()->id())
                                <form method="POST" action="{{ route('appointments.status', $appointment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="appointment_status" data-auto-submit>
                                        <option selected disabled>Update</option>
                                        <option>Confirmed</option>
                                        <option>Completed</option>
                                        <option>Cancelled</option>
                                        <option>Rejected</option>
                                    </select>
                                </form>
                            @endif
                        @endauth
                    </div>
                </article>
            @empty
                <p class="appointments-empty">No appointments found.</p>
            @endforelse
        </div>
    </div>
@endsection
