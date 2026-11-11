@extends(auth()->user()?->normalizedRole() === 'client' ? 'layouts.app' : 'layouts.client')

@section('title', 'Appointments')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/appointments.css') }}?v={{ filemtime(public_path('css/appointments.css')) }}">
@endpush

@section('content')
    <div data-split-view="appointments" data-split-selected="{{ $selectedAppointment?->appointment_id }}" class="appointments-page appointments-split-page {{ auth()->user()?->normalizedRole() === 'client' ? 'appointments-client-page' : 'staff-page-surface' }}">
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
                   href="{{ route('appointments.index', array_merge(request()->only('status'), ['page' => $appointments->currentPage(), 'selected' => $appointment->appointment_id]), false) }}"
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

            <section class="appointment-detail-panel" @if ($selectedAppointment) id="appointment-{{ $selectedAppointment->appointment_id }}" @endif aria-live="polite" aria-label="Appointment details">
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
                        @if ($selectedAppointment->cancellation_reason)<div><dt>Cancellation reason</dt><dd>{{ $selectedAppointment->cancellation_reason }}</dd></div>@endif
                    </dl>

                    @if ($pendingProposal)
                        @include('pages.partials.appointment-proposal', ['appointment' => $selectedAppointment])
                    @endif

                    <div class="appointment-detail-actions">
                        @php $reviewable = $selectedAppointment->assignment?->user; @endphp
                        @if ($isAppointmentClient && $selectedAppointment->appointment_status === 'Completed' && $reviewable && in_array($reviewable->role_name, ['Broker', 'Agent', 'Lead Broker'], true))
                            <a class="settings-appointment-link" href="{{ route('agents.show', $reviewable) }}#reviews">Rate your experience with {{ $reviewable->name }}</a>
                        @endif
                        @php($canCancelAppointment = ($isAppointmentClient && $selectedAppointment->appointment_status === 'Pending') || (($isAppointmentAdmin || $isAssignedProfessional) && in_array($selectedAppointment->appointment_status, ['Pending', 'Confirmed'], true)))
                        @php($canUseStatusDropdown = (($isAppointmentAdmin || $isAssignedProfessional) && in_array($selectedAppointment->appointment_status, ['Pending', 'Confirmed'], true)) || $canCancelAppointment)
                        @if ($canUseStatusDropdown)
                            <form method="POST" action="{{ route('appointments.status', $selectedAppointment) }}">
                                @csrf @method('PATCH')
                                <label for="appointmentStatusUpdate">Update status</label>
                                <select id="appointmentStatusUpdate" name="appointment_status" data-auto-submit data-cancel-select data-cancel-action="{{ route('appointments.status', $selectedAppointment) }}" data-cancel-context="{{ $selectedAppointment->assignment?->property?->property_name ?? 'Property appointment' }} · {{ $selectedAppointment->contact_name }}">
                                    <option value="" selected disabled>Choose status</option>
                                    @if (($isAppointmentAdmin || $isAssignedProfessional) && $selectedAppointment->appointment_status === 'Pending')
                                        <option value="Confirmed">Confirm</option>
                                        <option value="Completed">Completed</option>
                                    @elseif (($isAppointmentAdmin || $isAssignedProfessional) && $selectedAppointment->appointment_status === 'Confirmed')
                                        <option value="Completed">Completed</option>
                                    @endif
                                    @if ($canCancelAppointment)
                                        <option value="Cancelled">Cancel</option>
                                    @endif
                                </select>
                            </form>
                        @endif

                        @if (($isAssignedProfessional || $isAppointmentClient) && in_array($selectedAppointment->appointment_status, ['Pending', 'Confirmed'], true) && ! $selectedAppointment->proposals->contains('status', 'Pending'))
                            <details class="appointment-propose-time">
                                <summary>Propose a new time</summary>
                                <p class="appointment-propose-note">The other participant must accept; the scheduled time stays unchanged until then.</p>
                                @include('components.appointment-hours')
                                <form method="POST" action="{{ route('appointments.proposals.store', $selectedAppointment) }}" data-proposal-availability-url="{{ route('appointments.proposals.availability', $selectedAppointment) }}">
                                    @csrf
                                    <label>New date<input type="date" name="preferred_date" min="{{ now($viewerTimezone)->toDateString() }}" required></label>
                                    <label>Time period<select name="time_period" required disabled>
                                        <option value="">Choose a time period</option>
                                        <option value="morning">Morning (8:00 AM–12:00 PM)</option>
                                        <option value="afternoon">Afternoon (1:00 PM–5:00 PM)</option>
                                    </select></label>
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

    <div class="appointment-cancel-modal" id="appointmentCancelModal" hidden>
        <section class="appointment-cancel-dialog" role="dialog" aria-modal="true" aria-labelledby="appointmentCancelTitle" tabindex="-1">
            <span class="appointment-reschedule-kicker">CANCEL APPOINTMENT</span>
            <h2 id="appointmentCancelTitle">Reason for cancellation</h2>
            <p class="appointment-reschedule-context" data-cancel-context></p>
            <form method="POST" data-cancel-form data-no-confirm>
                @csrf @method('PATCH')
                <input type="hidden" name="appointment_status" value="Cancelled">
                <label>Cancellation reason<textarea name="cancellation_reason" rows="4" maxlength="1000" required placeholder="Explain why this appointment is being cancelled."></textarea></label>
                <div class="appointment-cancel-actions">
                    <button type="button" class="appointment-cancel-dismiss" data-cancel-dismiss>Cancel</button>
                    <button type="submit" class="appointment-cancel-submit">Confirm cancellation</button>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/split-view.js') }}?v={{ filemtime(public_path('js/split-view.js')) }}" defer></script>
    <script src="{{ asset('js/availability-picker.js') }}?v={{ filemtime(public_path('js/availability-picker.js')) }}" defer></script>
    <script src="{{ asset('js/appointment-proposals.js') }}?v={{ filemtime(public_path('js/appointment-proposals.js')) }}" defer></script>
    <script src="{{ asset('js/appointment-cancel.js') }}?v={{ filemtime(public_path('js/appointment-cancel.js')) }}" defer></script>
@endpush
