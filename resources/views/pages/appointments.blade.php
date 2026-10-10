@extends(auth()->user()?->normalizedRole() === 'client' ? 'layouts.app' : 'layouts.client')

@section('title', 'Appointments')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/appointments.css') }}?v={{ filemtime(public_path('css/appointments.css')) }}">
@endpush

@section('content')
    <div class="appointments-page appointments-split-page {{ auth()->user()?->normalizedRole() === 'client' ? 'appointments-client-page' : 'staff-page-surface' }}">
        <div class="appointments-page-header">
            <div>
                <span>Schedule</span>
                <h1>Appointments</h1>
                <p>{{ $isAdmin ? 'Global appointments across all listings and professionals.' : 'Your scheduled property viewings and time proposals.' }}</p>
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

        <div class="appointments-workspace">
            <div class="appointments-master-list" role="navigation" aria-label="Appointment list">
            @forelse ($appointments as $appointment)
                @php
                    $appointmentStart = $appointment->startInTimezone($viewerTimezone);
                    $statusLabel = $appointment->displayStatus();
                    $statusClass = $appointment->displayStatusClass();
                @endphp
                <a class="appointment-master-item {{ (int) ($selectedAppointment?->appointment_id) === (int) $appointment->appointment_id ? 'active' : '' }}"
                   href="{{ route('appointments.index', array_merge(request()->except('selected'), ['selected' => $appointment->appointment_id])) }}"
                   @if ((int) ($selectedAppointment?->appointment_id) === (int) $appointment->appointment_id) aria-current="true" @endif>
                    <span class="appointment-date"><strong>{{ $appointmentStart->format('d') }}</strong><span>{{ $appointmentStart->format('M Y') }}</span></span>
                    <span class="appointment-master-copy">
                        <strong>{{ $appointment->assignment?->property?->property_name ?? 'Property appointment' }}</strong>
                        <small>{{ $appointment->contact_name }} · {{ $appointmentStart->format('g:i A') }}</small>
                        <span class="appointment-status status-{{ $statusClass }}">{{ $statusLabel }}</span>
                    </span>
                </a>
            @empty
                <p class="appointments-empty">No appointments found.</p>
            @endforelse
                {{ $appointments->links('pagination.ljs') }}
            </div>

            <section class="appointment-detail-panel" aria-live="polite" aria-label="Appointment details">
                @if ($selectedAppointment)
                    @php
                        $viewer = auth()->user();
                        $selectedStart = $selectedAppointment->startInTimezone($viewerTimezone);
                        $isAppointmentAdmin = $viewer?->actingRole() === 'admin';
                        $isAssignedProfessional = (int) $selectedAppointment->assignment?->user_id === (int) $viewer?->user_id;
                        $isAppointmentClient = $viewer?->normalizedRole() === 'client'
                            && ((int) $selectedAppointment->client_id === (int) $viewer?->user_id
                                || ($selectedAppointment->client_id === null && strcasecmp($selectedAppointment->contact_email, $viewer?->email ?? '') === 0));
                        $selectedStatusLabel = $selectedAppointment->displayStatus();
                        $selectedStatusClass = $selectedAppointment->displayStatusClass();
                    @endphp
                    <header class="appointment-detail-header">
                        <span>APPOINTMENT DETAILS</span>
                        <h2>{{ $selectedAppointment->assignment?->property?->property_name ?? 'Property appointment' }}</h2>
                        <p>{{ $selectedAppointment->appointment_type }} · {{ $selectedStart->format('M j, Y · g:i A') }}</p>
                    </header>
                    <dl class="appointment-detail-grid">
                        <div><dt>Status</dt><dd><span class="appointment-status status-{{ $selectedStatusClass }}">{{ $selectedStatusLabel }}</span></dd></div>
                        <div><dt>Client</dt><dd>{{ $selectedAppointment->contact_name }}</dd></div>
                        <div><dt>Professional</dt><dd>{{ $selectedAppointment->assignment?->user?->name ?? 'Listing team' }}</dd></div>
                        <div><dt>Contact</dt><dd>{{ $selectedAppointment->contact_email }}{{ $selectedAppointment->contact_number ? ' · ' . $selectedAppointment->contact_number : '' }}</dd></div>
                        @if ($selectedAppointment->additional_note)<div><dt>Notes</dt><dd>{{ $selectedAppointment->additional_note }}</dd></div>@endif
                    </dl>

                    @if ($pendingProposal)
                        <div class="appointment-pending-proposal">
                            <strong>A time proposal is pending.</strong>
                            @if ($proposalNotification)
                                <a href="{{ route('notifications.index', ['selected' => $proposalNotification->notification_id]) }}">Review it in Notifications</a>
                            @else
                                <a href="{{ route('notifications.index') }}">Review it in Notifications</a>
                            @endif
                        </div>
                    @endif

                    <div class="appointment-detail-actions">
                        @php $reviewable = $selectedAppointment->assignment?->user; @endphp
                        @if ($isAppointmentClient && $selectedAppointment->appointment_status === 'Completed' && $reviewable && in_array($reviewable->role_name, ['Broker', 'Agent', 'Lead Broker'], true))
                            <a class="settings-appointment-link" href="{{ route('agents.show', $reviewable) }}#reviews">Rate your experience with {{ $reviewable->name }}</a>
                        @endif
                        @if ($selectedAppointment->appointment_status === 'Pending' && ($isAppointmentAdmin || $isAssignedProfessional || $isAppointmentClient))
                            <form method="POST" action="{{ route('appointments.status', $selectedAppointment) }}">
                                @csrf @method('PATCH')
                                <label for="appointmentStatusUpdate">Update status</label>
                                <select id="appointmentStatusUpdate" name="appointment_status" data-auto-submit>
                                    <option selected disabled>Choose status</option>
                                    @if ($isAppointmentAdmin || $isAssignedProfessional)
                                        <option value="Confirmed">Confirm</option>
                                        <option>Completed</option>
                                        <option>Rejected</option>
                                    @endif
                                    <option>Cancelled</option>
                                </select>
                            </form>
                        @endif

                        @if (($isAssignedProfessional || $isAppointmentClient) && in_array($selectedAppointment->appointment_status, ['Pending', 'Confirmed'], true) && ! $selectedAppointment->proposals->contains('status', 'Pending'))
                            <details class="appointment-propose-time">
                                <summary>Propose a new time</summary>
                                <p class="appointment-propose-note">The other participant must accept; the scheduled time stays unchanged until then.</p>
                                <form method="POST" action="{{ route('appointments.proposals.store', $selectedAppointment) }}" data-proposal-availability-url="{{ route('appointments.proposals.availability', $selectedAppointment) }}">
                                    @csrf
                                    <label>New date<input type="date" name="preferred_date" min="{{ now($viewerTimezone)->toDateString() }}" required></label>
                                    <label>Available time<select name="preferred_time" required disabled><option value="">Choose a date first</option></select></label>
                                    <input type="hidden" name="slot_start_utc">
                                    <input type="hidden" name="timezone" value="{{ $viewerTimezone }}">
                                    <label>Message (optional)<input type="text" name="message" maxlength="500" placeholder="Add a note for the other participant"></label>
                                    <p class="appointment-slots-status" role="status" aria-live="polite">Times are shown in your local time.</p>
                                    <button type="submit">Send proposal</button>
                                </form>
                            </details>
                        @endif
                    </div>
                @else
                    <div class="appointment-detail-empty"><h2>Select an appointment</h2><p>Choose an item from the list to see its details and available actions.</p></div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/availability-picker.js') }}?v={{ filemtime(public_path('js/availability-picker.js')) }}" defer></script>
    <script src="{{ asset('js/appointment-proposals.js') }}?v={{ filemtime(public_path('js/appointment-proposals.js')) }}" defer></script>
@endpush
