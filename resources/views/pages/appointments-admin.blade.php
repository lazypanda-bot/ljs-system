@extends('layouts.client')

@section('title', 'Appointments')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/appointments.css') }}?v={{ filemtime(public_path('css/appointments.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/auto-filter.js') }}?v={{ filemtime(public_path('js/auto-filter.js')) }}" defer></script>
    <script src="{{ asset('js/availability-picker.js') }}?v={{ filemtime(public_path('js/availability-picker.js')) }}" defer></script>
    <script src="{{ asset('js/appointment-proposals.js') }}?v={{ filemtime(public_path('js/appointment-proposals.js')) }}" defer></script>
@endpush

@section('content')
    @php($staffMode = $staffMode ?? false)
    @php($isProjects = $staffMode || $tab === 'projects')
    <div class="listings-management-container staff-page-surface au-page-card">
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        <header class="staff-property-admin-header">
            <div>
                <h2>Appointments</h2>
                <p>{{ $staffMode ? 'Appointments booked on your listings.' : ($isProjects ? 'Appointments on admin project listings. You handle these requests.' : 'Monitoring appointments of agents and brokers. View only.') }}</p>
            </div>
        </header>

        @unless ($staffMode)
        <div class="au-tabs-container staff-property-tabs staff-user-tabs" role="tablist" aria-label="Appointment views">
            <a class="au-tab-btn {{ $isProjects ? '' : 'active' }}" href="{{ route('appointments.index', ['tab' => 'monitoring']) }}">Agent &amp; Broker Appointments <span class="staff-property-tab-count">{{ $counts['monitoring'] }}</span></a>
            <a class="au-tab-btn {{ $isProjects ? 'active' : '' }}" href="{{ route('appointments.index', ['tab' => 'projects']) }}">Project Listings <span class="staff-property-tab-count">{{ $counts['projects'] }}</span></a>
        </div>
        @endunless

        <form class="staff-filter-bar" method="GET" action="{{ route('appointments.index') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="search-box">
                <span class="search-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                <input type="search" name="q" value="{{ $search }}" aria-label="Search appointments" placeholder="Search client, property or professional...">
            </div>
            <select name="status" aria-label="Filter by status" data-auto-submit>
                <option value="">All statuses</option>
                @foreach (['Pending', 'Confirmed', 'Cancelled', 'Reschedule'] as $status)
                    <option value="{{ $status }}" @selected($filter === $status)>{{ $status === 'Reschedule' ? 'Reschedule requested' : $status }}</option>
                @endforeach
            </select>
        </form>

        <div class="au-table-container">
            <div class="staff-property-table-wrap">
                <table class="staff-property-table">
                    <thead>
                        <tr>
                            <th>PROPERTY</th>
                            <th>CLIENT</th>
                            <th>PROFESSIONAL</th>
                            <th>DATE &amp; TIME</th>
                            <th>TYPE</th>
                            <th>STATUS</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments as $appointment)
                            @php($start = $appointment->startInTimezone($viewerTimezone))
                            @php($isOwnerAdmin = $isProjects && (int) $appointment->assignment?->user_id === (int) auth()->id())
                            @php($hasPendingProposal = $appointment->proposals->contains('status', 'Pending'))
                            @php($canUpdateStatus = ! $hasPendingProposal && ($staffMode || $isOwnerAdmin) && (int) $appointment->assignment?->user_id === (int) auth()->id() && in_array($appointment->appointment_status, ['Pending', 'Confirmed'], true))
                            @php($canProposeTime = $canUpdateStatus && ! $appointment->proposals->contains('status', 'Pending'))
                            @php($statusLabel = $appointment->displayStatus())
                            @php($statusClass = $appointment->displayStatusClass())
                            <tr>
                                <td class="fw-semibold">{{ $appointment->assignment?->property?->property_name ?? 'Property appointment' }}</td>
                                <td>{{ $appointment->contact_name }}<br><small class="text-muted">{{ $appointment->contact_email }}</small></td>
                                <td>{{ $appointment->assignment?->user?->name ?? 'Listing team' }}<br><small class="text-muted">{{ $appointment->assignment?->user?->role_name }}</small></td>
                                <td>{{ $start->format('M j, Y') }}<br><small class="text-muted">{{ $start->format('g:i A') }}</small></td>
                                <td>{{ $appointment->appointment_type }}</td>
                                <td>
                                    <span class="staff-status-pill status-{{ $statusClass }}">{{ $statusLabel }}</span>
                                    @if ($hasPendingProposal)<br><small class="staff-readonly-note">Reschedule pending</small>@endif
                                    @if ($canUpdateStatus)
                                        <form method="POST" action="{{ route('appointments.status', $appointment) }}" class="appointment-status-control">
                                            @csrf @method('PATCH')
                                            <select name="appointment_status" aria-label="Update status" data-auto-submit>
                                                @if ($appointment->appointment_status === 'Pending')
                                                    <option value="" selected disabled>Update status</option>
                                                @endif
                                                <option value="Confirmed" @selected($appointment->appointment_status === 'Confirmed')>Confirm</option>
                                                <option value="Completed">Completed</option>
                                                <option value="Cancelled">Cancelled</option>
                                            </select>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($isProjects && $appointment->appointment_status === 'Pending' && ! $canUpdateStatus)
                                        <span class="staff-readonly-note">Handled by {{ $appointment->assignment?->user?->name ?? 'the assigned admin' }}</span>
                                    @elseif ($isProjects && ! $canProposeTime)
                                        <span class="staff-readonly-note">No action needed</span>
                                    @elseif (! $isProjects)
                                        <span class="staff-readonly-note">View only</span>
                                    @endif
                                    @if ($canProposeTime)
                                        <button type="button" class="appointment-reschedule-trigger" data-open-reschedule-modal="reschedule-modal-{{ $appointment->appointment_id }}" aria-haspopup="dialog">Reschedule</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="staff-table-empty">No appointments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($appointments->hasPages())
                {{ $appointments->links('pagination.ljs') }}
            @endif
        </div>
    </div>

    @foreach ($appointments as $appointment)
        @php($canProposeTime = ($staffMode || ($isProjects && (int) $appointment->assignment?->user_id === (int) auth()->id())) && in_array($appointment->appointment_status, ['Pending', 'Confirmed'], true) && ! $appointment->proposals->contains('status', 'Pending') && (int) $appointment->assignment?->user_id === (int) auth()->id())
        @if ($canProposeTime)
            <div class="appointment-reschedule-modal" id="reschedule-modal-{{ $appointment->appointment_id }}" data-reschedule-modal hidden>
                <section class="appointment-reschedule-dialog" role="dialog" aria-modal="true" aria-labelledby="reschedule-title-{{ $appointment->appointment_id }}" tabindex="-1">
                    <button type="button" class="appointment-reschedule-close" data-reschedule-close aria-label="Close reschedule form">&times;</button>
                    <span class="appointment-reschedule-kicker">APPOINTMENT CHANGE</span>
                    <h2 id="reschedule-title-{{ $appointment->appointment_id }}">Reschedule appointment</h2>
                    <p class="appointment-reschedule-context">{{ $appointment->assignment?->property?->property_name ?? 'Property appointment' }} · {{ $appointment->contact_name }}</p>
                    <p class="appointment-propose-note">The other participant must accept. The scheduled time stays unchanged until then.</p>
                    <div class="appointment-propose-time appointment-reschedule-form-wrap">
                        <form method="POST" action="{{ route('appointments.proposals.store', $appointment) }}" data-proposal-availability-url="{{ route('appointments.proposals.availability', $appointment) }}">
                            @csrf
                            <label>New date<input type="date" name="preferred_date" min="{{ now($viewerTimezone)->toDateString() }}" required></label>
                            <label>Available time<select name="preferred_time" required disabled><option value="">Choose a date first</option></select></label>
                            <input type="hidden" name="slot_start_utc">
                            <input type="hidden" name="timezone" value="{{ $viewerTimezone }}">
                            <label>Message (optional)<input type="text" name="message" maxlength="500" placeholder="Add a note for the other participant"></label>
                            <p class="appointment-slots-status" role="status" aria-live="polite">Times are shown in your local time.</p>
                            <button type="submit">Send proposal</button>
                        </form>
                    </div>
                </section>
            </div>
        @endif
    @endforeach
@endsection
