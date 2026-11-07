@extends(auth()->user()?->normalizedRole() === 'client' ? 'layouts.app' : 'layouts.client')

@section('title', 'Notifications')

@section('content')
    <div data-split-view="notifications" data-split-selected="{{ $selectedNotification?->notification_id }}" class="notifications-page {{ auth()->user()?->normalizedRole() === 'client' ? 'notifications-client-page' : 'staff-page-surface' }}" data-mobile-selected="{{ request()->filled('selected') && $selectedNotification ? 'true' : 'false' }}">
        @php
            $activeFilter = $filter ?? 'all';
            $filterOptions = [
                'all' => ['label' => 'All', 'icon' => 'bell'],
                'unread' => ['label' => 'Unread', 'icon' => 'circle-dot'],
                'appointments' => ['label' => 'Appointments', 'icon' => 'calendar-check-2'],
                'messages' => ['label' => 'Messages', 'icon' => 'messages-square'],
                'team' => ['label' => 'Team', 'icon' => 'users-round'],
            ];
            $filterUrl = fn (string $key) => route('notifications.index', $key === 'all' ? [] : ['filter' => $key]);
        @endphp

        <div class="notifications-page-header">
            <div><span>ACCOUNT UPDATES</span><h1>Notifications</h1><p>Select a notification to view its details and actions.</p></div>
            <button class="notification-filter-menu-toggle" type="button" aria-expanded="false" aria-controls="notificationFilterCards">
                <i data-lucide="menu" aria-hidden="true"></i><span>Filters</span>
            </button>
            <section class="notifications-overview" id="notificationFilterCards" aria-label="Notification filters">
                @foreach ($filterOptions as $key => $option)
                    <a class="notification-summary-card {{ $activeFilter === $key ? 'active' : '' }}" href="{{ $filterUrl($key) }}" @if ($activeFilter === $key) aria-current="page" @endif>
                        <span class="notification-summary-icon"><i data-lucide="{{ $option['icon'] }}" aria-hidden="true"></i></span>
                        <span><small>{{ $option['label'] }}</small><strong>{{ number_format($notificationSummary[$key] ?? 0) }}</strong></span>
                    </a>
                @endforeach
            </section>
        </div>

        @php
            $selected = $selectedNotification ?? null;
            $proposal = $selected?->appointmentProposal;
            $appointment = $proposal?->appointment ?? $selected?->appointment;
            $viewer = auth()->user();
            $viewerTimezone = $viewer?->timezone ?? config('appointments.default_timezone');
            $appointmentUrl = $appointment ? route('appointments.index', array_merge(
                ['selected' => $appointment->appointment_id],
                $viewer?->actingRole() === 'admin'
                    ? ['tab' => $appointment->assignment?->property?->listing_mode === 'admin' ? 'projects' : 'monitoring']
                    : []
            ), false) . '#appointment-' . $appointment->appointment_id : null;
            $appointmentStart = $appointment?->startInTimezone($viewerTimezone);
            $proposedStart = $proposal?->starts_at_utc?->setTimezone($viewerTimezone);
            $appointmentStatusLabel = $appointment?->displayStatus();
            $appointmentStatusClass = $appointment?->displayStatusClass();
        @endphp

        <div class="notifications-workspace">
            @include('pages.partials.notification-list', ['notifications' => $notifications, 'selectedNotification' => $selected])

            <section class="notification-detail-panel" aria-live="polite" aria-label="Notification details">
                @if ($selected)
                    <header class="notification-detail-header">
                        <a class="notification-detail-back" data-close-notification-detail href="{{ route('notifications.index', array_merge(request()->only('filter'), ['page' => $notifications->currentPage()]), false) }}" aria-label="Back to notifications"><i data-lucide="arrow-left" aria-hidden="true"></i><span>Notifications</span></a>
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
                                <div><dt>Status</dt><dd><span class="appointment-status status-{{ $appointmentStatusClass }}">{{ $appointmentStatusLabel }}</span></dd></div>
                                <div><dt>Appointment type</dt><dd>{{ $appointment->appointment_type }}</dd></div>
                                <div><dt>Client</dt><dd>{{ $appointment->contact_name }}</dd></div>
                                <div><dt>Professional</dt><dd>{{ $appointment->assignment?->user?->name ?? 'Listing team' }}</dd></div>
                                <div><dt>Contact</dt><dd>{{ $appointment->contact_email }}{{ $appointment->contact_number ? ' · ' . $appointment->contact_number : '' }}</dd></div>
                                @if ($appointment->additional_note)<div><dt>Notes</dt><dd>{{ $appointment->additional_note }}</dd></div>@endif
                                @if ($appointment->cancellation_reason)<div><dt>Cancellation reason</dt><dd>{{ $appointment->cancellation_reason }}</dd></div>@endif
                            </dl>
                            <a class="notification-appointment-link" href="{{ $appointmentUrl }}">View appointment</a>
                        </section>
                    @endif

                    @if ($proposal)
                        <section class="notification-proposal-detail" aria-labelledby="proposalDetailsTitle">
                            <h3 id="proposalDetailsTitle">Proposed time</h3>
                            <p>{{ $proposedStart?->format('M j, Y · g:i A') }}</p>
                            <p class="notification-proposal-by">Proposed by {{ $proposal->proposedBy?->name ?? 'Appointment participant' }}</p>
                            @if ($proposal->message)<p>{{ $proposal->message }}</p>@endif
                            <span class="notification-proposal-status">Proposal {{ strtolower($proposal->status) }}</span>
                            @if ($proposal->status === 'Pending')
                                <p class="notification-proposal-pending">Reschedule awaiting a response in Appointments.</p>
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

@push('scripts')
    <script src="{{ asset('js/split-view.js') }}?v={{ filemtime(public_path('js/split-view.js')) }}" defer></script>
    <script src="{{ asset('js/notifications.js') }}?v={{ filemtime(public_path('js/notifications.js')) }}" defer></script>
@endpush
