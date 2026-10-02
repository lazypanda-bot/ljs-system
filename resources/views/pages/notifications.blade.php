@extends(auth()->user()?->normalizedRole() === 'client' ? 'layouts.app' : 'layouts.client')

@section('title', 'Notifications')

@section('content')
    <div class="notifications-page {{ auth()->user()?->normalizedRole() === 'client' ? 'notifications-client-page' : 'staff-page-surface' }}">
        <div class="notifications-page-header">
            <div><span>ACCOUNT UPDATES</span><h1>Notifications</h1><p>Select a notification to view its details and actions.</p></div>
        </div>

        @php
            $selected = $selectedNotification ?? null;
            $proposal = $selected?->appointmentProposal;
            $appointment = $proposal?->appointment ?? $selected?->appointment;
            $viewer = auth()->user();
            $viewerTimezone = $viewer?->timezone ?? config('appointments.default_timezone');
            $proposer = $proposal?->proposedBy;
            $proposalProposerIsClient = $proposal && $appointment && $proposer
                && $proposer->normalizedRole() === 'client'
                && ((int) $appointment->client_id === (int) $proposer->user_id
                    || ($appointment->client_id === null && strcasecmp($appointment->contact_email, $proposer->email) === 0));
            $viewerIsAssignedProfessional = (int) $appointment?->assignment?->user_id === (int) $viewer?->user_id;
            $viewerIsClient = $appointment && $viewer?->normalizedRole() === 'client'
                && ((int) $appointment->client_id === (int) $viewer?->user_id
                    || ($appointment->client_id === null && strcasecmp($appointment->contact_email, $viewer?->email ?? '') === 0));
            $canRespondToProposal = $proposal?->status === 'Pending'
                && (($proposalProposerIsClient && $viewerIsAssignedProfessional)
                    || (! $proposalProposerIsClient && $viewerIsClient));
            $appointmentStart = $appointment?->startInTimezone($viewerTimezone);
            $proposedStart = $proposal?->starts_at_utc?->setTimezone($viewerTimezone);
        @endphp

        <div class="notifications-workspace">
            <nav class="notifications-list" aria-label="Notification list">
            @forelse ($notifications as $notification)
                <a class="notification-list-item notification-{{ $notification->type }} {{ $notification->read_at ? 'read' : 'unread' }} {{ (int) $selected?->notification_id === (int) $notification->notification_id ? 'active' : '' }}"
                   href="{{ route('notifications.index', ['selected' => $notification->notification_id]) }}"
                   @if ((int) $selected?->notification_id === (int) $notification->notification_id) aria-current="true" @endif>
                    <span class="notification-page-icon">{{ $notification->type === 'message' ? 'M' : ($notification->type === 'team' ? 'T' : ($notification->appointment_proposal_id ? 'A' : 'L')) }}</span>
                    <span class="notification-list-copy"><strong>{{ $notification->title }}</strong><small>{{ $notification->body }}</small><time>{{ $notification->created_at->diffForHumans() }}</time></span>
                </a>
            @empty
                <p class="notifications-empty">You have no notifications yet.</p>
            @endforelse
                {{ $notifications->links() }}
            </nav>

            <section class="notification-detail-panel" aria-live="polite" aria-label="Notification details">
                @if ($selected)
                    <header class="notification-detail-header">
                        <span class="notification-detail-kicker">{{ $selected->type === 'listing' ? 'APPOINTMENT & LISTING' : strtoupper($selected->type) }}</span>
                        <h2>{{ $selected->title }}</h2>
                        <p>{{ $selected->body }}</p>
                        <time>{{ $selected->created_at->format('M j, Y · g:i A') }}</time>
                    </header>

                    @if ($appointment)
                        <section class="notification-appointment-details" aria-labelledby="appointmentDetailsTitle">
                            <h3 id="appointmentDetailsTitle">Appointment details</h3>
                            <dl>
                                <div><dt>Property</dt><dd>{{ $appointment->assignment?->property?->property_name ?? 'Property appointment' }}</dd></div>
                                <div><dt>Scheduled time</dt><dd>{{ $appointmentStart?->format('M j, Y · g:i A') }}</dd></div>
                                <div><dt>Status</dt><dd><span class="appointment-status status-{{ strtolower($appointment->appointment_status) }}">{{ $appointment->appointment_status }}</span></dd></div>
                                <div><dt>Appointment type</dt><dd>{{ $appointment->appointment_type }}</dd></div>
                                <div><dt>Client</dt><dd>{{ $appointment->contact_name }}</dd></div>
                                <div><dt>Professional</dt><dd>{{ $appointment->assignment?->user?->name ?? 'Listing team' }}</dd></div>
                                <div><dt>Contact</dt><dd>{{ $appointment->contact_email }}{{ $appointment->contact_number ? ' · ' . $appointment->contact_number : '' }}</dd></div>
                                @if ($appointment->additional_note)<div><dt>Notes</dt><dd>{{ $appointment->additional_note }}</dd></div>@endif
                            </dl>
                        </section>
                    @endif

                    @if ($proposal)
                        <section class="notification-proposal-detail" aria-labelledby="proposalDetailsTitle">
                            <h3 id="proposalDetailsTitle">Proposed time</h3>
                            <p>{{ $proposedStart?->format('M j, Y · g:i A') }}</p>
                            <p class="notification-proposal-by">Proposed by {{ $proposal->proposedBy?->name ?? 'Appointment participant' }}</p>
                            @if ($proposal->message)<p>{{ $proposal->message }}</p>@endif
                            <span class="notification-proposal-status">Proposal {{ strtolower($proposal->status) }}</span>
                            @if ($canRespondToProposal)
                                <form method="POST" action="{{ route('appointments.proposals.respond', $proposal) }}" class="notification-proposal-actions">
                                    @csrf @method('PATCH')
                                    <button type="submit" name="decision" value="Accepted">Accept new time</button>
                                    <button type="submit" name="decision" value="Rejected">Decline</button>
                                </form>
                            @elseif ($proposal->status === 'Pending')
                                <p class="notification-proposal-pending">Waiting for the other participant to respond.</p>
                            @endif
                        </section>
                    @endif
                @else
                    <div class="notification-detail-empty"><h2>Select a notification</h2><p>Choose one from the list to see its details here.</p></div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/staff-pages.css') }}?v={{ filemtime(public_path('css/staff-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('css/notification-proposals.css') }}?v={{ filemtime(public_path('css/notification-proposals.css')) }}">
@endpush