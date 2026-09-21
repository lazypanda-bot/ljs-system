@extends('layouts.client')

@section('title', 'Appointments')

@section('content')
    <div class="appointments-page">
        <div class="appointments-page-header"><div><span>PROPERTY SCHEDULE</span><h1>Appointments</h1><p>Review scheduled viewings and appointment requests assigned to your account.</p></div></div>
        @auth
            <form class="block-date-form" method="POST" action="{{ route('appointments.block-date') }}">
                @csrf <strong>Block date</strong><input type="date" name="date" min="{{ now()->toDateString() }}" required><input type="time" name="start_time" aria-label="Start time"><input type="time" name="end_time" aria-label="End time"><input type="text" name="reason" placeholder="Reason"><button type="submit">Block date</button>
            </form>
        @endauth
        <div class="appointments-list">
            @forelse ($appointments as $appointment)
                <article class="appointment-row">
                    <div class="appointment-date"><strong>{{ $appointment->preferred_date->format('d') }}</strong><span>{{ $appointment->preferred_date->format('M Y') }}</span></div>
                    <div class="appointment-main"><h2>{{ $appointment->assignment?->property?->property_name ?? 'Property appointment' }}</h2><p>{{ $appointment->contact_name }} · {{ $appointment->preferred_time }}</p><small>{{ $appointment->appointment_type }}{{ $appointment->additional_note ? ' · ' . $appointment->additional_note : '' }}</small></div>
                    <div class="appointment-actions"><span class="appointment-status status-{{ strtolower($appointment->appointment_status) }}">{{ $appointment->appointment_status }}</span>@auth @if(auth()->user()->actingRole() === 'admin' || $appointment->assignment?->user_id === auth()->id())<form method="POST" action="{{ route('appointments.status', $appointment) }}">@csrf @method('PATCH')<select name="appointment_status" onchange="this.form.submit()"><option selected disabled>Update</option><option>Confirmed</option><option>Completed</option><option>Cancelled</option><option>Rejected</option></select></form>@endif @endauth</div>
                </article>
            @empty
                <p class="appointments-empty">No appointments found.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('styles')
<style>
.appointments-page{max-width:1100px;margin:0 auto;padding:8px}.appointments-page-header{margin-bottom:22px}.appointments-page-header span{color:var(--color-primary);font-size:12px;font-weight:800;letter-spacing:.08em}.appointments-page-header h1{margin:5px 0;color:var(--color-text);font-size:28px}.appointments-page-header p{margin:0;color:var(--color-text-muted)}.block-date-form{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:0 0 18px;padding:14px;border:1px solid var(--color-border);border-radius:10px;background:var(--color-surface)}.block-date-form strong{margin-right:auto;color:var(--color-text)}.block-date-form input{padding:8px;border:1px solid var(--color-border);border-radius:6px}.block-date-form button{padding:8px 12px;border:0;border-radius:6px;background:var(--color-primary);color:#fff;font-weight:700}.appointments-list{overflow:hidden;border:1px solid var(--color-border);border-radius:12px;background:var(--color-surface)}.appointment-row{display:grid;grid-template-columns:72px 1fr auto;gap:16px;align-items:center;padding:18px;border-bottom:1px solid var(--color-border)}.appointment-row:last-child{border-bottom:0}.appointment-date{display:grid;place-items:center;padding:10px;border-radius:8px;background:var(--color-primary-soft);color:var(--color-primary)}.appointment-date strong{font-size:24px}.appointment-date span{font-size:11px}.appointment-main h2{margin:0;color:var(--color-text);font-size:16px}.appointment-main p{margin:4px 0;color:var(--color-text-dark)}.appointment-main small{color:var(--color-text-muted)}.appointment-actions{display:flex;align-items:center;gap:8px}.appointment-actions select{padding:5px;border:1px solid var(--color-border);border-radius:6px;font-size:11px}.appointment-status{padding:6px 10px;border-radius:999px;font-size:12px;font-weight:800}.status-pending{background:#fef3c7;color:#92400e}.status-confirmed{background:#dcfce7;color:#166534}.status-completed{background:#dbeafe;color:#1d4ed8}.status-cancelled,.status-rejected{background:#fee2e2;color:#991b1b}.appointments-empty{padding:30px;color:var(--color-text-muted);text-align:center}@media(max-width:650px){.appointment-row{grid-template-columns:60px 1fr}.appointment-actions{grid-column:2;justify-self:start}.appointment-status{grid-column:auto}}
</style>
@endpush