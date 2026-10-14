@extends('layouts.client')

@section('title', 'My Agents')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
@endpush

@section('content')
    <div class="listings-management-container staff-page-surface au-page-card">
        <header class="staff-property-admin-header">
            <div>
                <h2>My Agents</h2>
                <p>Agents in your team. Their listings and appointments are view only.</p>
            </div>
        </header>

        <div class="listings-toolbar">
            <div class="search-box">
                <span class="search-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                <input id="searchAgents" type="search" aria-label="Search agents" placeholder="Search by name...">
            </div>
        </div>

        <div class="au-table-container">
            <div class="staff-property-table-wrap">
                <table class="staff-property-table au-users-table" id="agentsTable">
                    <thead>
                        <tr>
                            <th>AGENT</th>
                            <th>EMAIL</th>
                            <th>CONTACT NUMBER</th>
                            <th>PRC LICENSE</th>
                            <th>ROLE</th>
                            <th>LISTINGS</th>
                            <th>APPOINTMENTS</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($managedAgents as $agent)
                            <tr>
                                <td>
                                    <div class="au-user-cell">
                                        @if ($agent->profile_photo)
                                            <img src="{{ str_starts_with($agent->profile_photo, 'http') ? $agent->profile_photo : asset('storage/' . $agent->profile_photo) }}" alt="{{ $agent->name }}" class="rounded-circle shadow-sm" width="40" height="40">
                                        @else
                                            <span class="au-user-avatar role-{{ $agent->normalizedRole() }}" aria-label="{{ $agent->name }}">{{ strtoupper(substr($agent->name, 0, 1)) }}</span>
                                        @endif
                                        <span class="fw-semibold text-dark">{{ $agent->name }}</span>
                                    </div>
                                </td>
                                <td class="text-muted">{{ $agent->email }}</td>
                                <td>{{ $agent->contact_number ?: '—' }}</td>
                                <td>{{ $agent->professionalProfile?->license_number ?: 'Not listed' }}</td>
                                <td><span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-1">{{ $agent->role_name }}</span></td>
                                <td>{{ ($listingsByAgent[$agent->user_id] ?? collect())->count() }}</td>
                                <td>{{ ($appointmentsByAgent[$agent->user_id] ?? collect())->count() }}</td>
                                <td class="text-end">
                                    <div class="au-action-group au-row-buttons">
                                        <button type="button" class="au-row-btn" data-agent-modal="profile-{{ $agent->user_id }}">Profile</button>
                                        <button type="button" class="au-row-btn au-row-btn-primary" data-agent-modal="listings-{{ $agent->user_id }}">Listings</button>
                                        <button type="button" class="au-row-btn au-row-btn-primary" data-agent-modal="appointments-{{ $agent->user_id }}">Appointments</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="staff-table-empty">No agents have been added to your team yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach ($managedAgents as $agent)
        @php
            $profile = $agent->professionalProfile;
            $agentListings = $listingsByAgent[$agent->user_id] ?? collect();
            $agentAppointments = $appointmentsByAgent[$agent->user_id] ?? collect();
        @endphp

        <div class="au-modal-overlay" id="agent-modal-profile-{{ $agent->user_id }}" aria-hidden="true">
            <div class="au-form-card" role="dialog" aria-modal="true">
                <button type="button" class="au-modal-close" data-agent-modal-close aria-label="Close">&times;</button>
                <h3>{{ $agent->name }}</h3>
                <dl class="au-agent-profile">
                    <div><dt>Role</dt><dd>{{ $agent->role_name }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $agent->email }}</dd></div>
                    <div><dt>Contact</dt><dd>{{ $agent->contact_number ?: 'Not listed' }}</dd></div>
                    <div><dt>PRC license</dt><dd>{{ $profile?->license_number ?: 'Not listed' }}</dd></div>
                    @if ($profile?->professional_bio)<div class="au-agent-profile-wide"><dt>Bio</dt><dd>{{ $profile->professional_bio }}</dd></div>@endif
                </dl>
            </div>
        </div>

        <div class="au-modal-overlay" id="agent-modal-listings-{{ $agent->user_id }}" aria-hidden="true">
            <div class="au-form-card au-agent-wide" role="dialog" aria-modal="true">
                <button type="button" class="au-modal-close" data-agent-modal-close aria-label="Close">&times;</button>
                <h3>{{ $agent->name }}'s listings</h3>
                <div class="staff-property-table-wrap">
                    <table class="staff-property-table">
                        <thead><tr><th>PROPERTY</th><th>TYPE</th><th>PRICE</th><th>STATUS</th><th>APPROVAL</th></tr></thead>
                        <tbody>
                            @forelse ($agentListings as $property)
                                <tr>
                                    <td class="fw-semibold"><a href="{{ route('staff.properties.show', $property) }}">{{ $property->property_name }}</a></td>
                                    <td>{{ $property->property_type }}</td>
                                    <td class="staff-property-price-cell">PHP {{ number_format($property->price, 0) }}</td>
                                    <td>{{ $property->property_status }}</td>
                                    <td><span class="staff-status-pill status-{{ strtolower($property->approval_status) }}">{{ $property->approval_status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="staff-table-empty">No listings yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="au-modal-overlay" id="agent-modal-appointments-{{ $agent->user_id }}" aria-hidden="true">
            <div class="au-form-card au-agent-wide" role="dialog" aria-modal="true">
                <button type="button" class="au-modal-close" data-agent-modal-close aria-label="Close">&times;</button>
                <h3>{{ $agent->name }}'s appointments</h3>
                <div class="staff-property-table-wrap">
                    <table class="staff-property-table">
                        <thead><tr><th>PROPERTY</th><th>CLIENT</th><th>DATE &amp; TIME</th><th>TYPE</th><th>STATUS</th></tr></thead>
                        <tbody>
                            @forelse ($agentAppointments as $appointment)
                                @php($start = $appointment->startInTimezone($viewerTimezone))
                                <tr>
                                    <td class="fw-semibold">{{ $appointment->assignment?->property?->property_name ?? 'Property appointment' }}</td>
                                    <td>
                                        {{ $appointment->contact_name }}
                                        <br><small class="text-muted">{{ $appointment->contact_email }}</small>
                                        <br><small class="text-muted">Contact #: {{ $appointment->contact_number ?: 'Not provided' }}</small>
                                        @if ($appointment->additional_note)
                                            <br><small class="text-muted">Notes: {{ $appointment->additional_note }}</small>
                                        @endif
                                        @if ($appointment->cancellation_reason)
                                            <br><small class="text-muted">Cancel reason: {{ $appointment->cancellation_reason }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $start->format('M j, Y') }}<br><small class="text-muted">{{ $start->format('g:i A') }}</small></td>
                                    <td>{{ $appointment->appointment_type }}</td>
                                    <td><span class="staff-status-pill status-{{ strtolower($appointment->appointment_status) }}">{{ $appointment->appointment_status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="staff-table-empty">No appointments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
<script>
document.getElementById('searchAgents')?.addEventListener('input', function () {
    const term = this.value.trim().toLowerCase();
    const table = document.getElementById('agentsTable');
    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tbody tr'))
        .filter((row) => !row.matches('[data-search-empty]') && !(row.cells.length === 1 && row.cells[0].hasAttribute('colspan')));
    const emptyRows = Array.from(table.querySelectorAll('tbody tr'))
        .filter((row) => !row.matches('[data-search-empty]') && row.cells.length === 1 && row.cells[0].hasAttribute('colspan'));
    let searchEmptyRow = table.querySelector('[data-search-empty]');
    if (!searchEmptyRow) {
        searchEmptyRow = document.createElement('tr');
        searchEmptyRow.setAttribute('data-search-empty', '');
        searchEmptyRow.hidden = true;
        const cell = document.createElement('td');
        cell.colSpan = table.tHead?.rows[0]?.cells.length || 8;
        cell.className = 'staff-table-empty';
        cell.textContent = 'No agents match your search.';
        searchEmptyRow.appendChild(cell);
        table.tBodies[0]?.appendChild(searchEmptyRow);
    }

    if (rows.length === 0) {
        emptyRows.forEach((row) => { row.style.display = term ? 'none' : ''; });
        searchEmptyRow.hidden = true;
        return;
    }

    emptyRows.forEach((row) => { row.style.display = 'none'; });
    let visibleCount = 0;
    rows.forEach(function (row) {
        const isVisible = !term || row.textContent.toLowerCase().includes(term);
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount += 1;
    });
    searchEmptyRow.hidden = !term || visibleCount > 0;
});
const closeAgentModals = () => document.querySelectorAll('.au-modal-overlay.open').forEach((overlay) => overlay.classList.remove('open'));
document.querySelectorAll('[data-agent-modal]').forEach((button) => {
    button.addEventListener('click', () => document.getElementById('agent-modal-' + button.dataset.agentModal)?.classList.add('open'));
});
document.querySelectorAll('[data-agent-modal-close]').forEach((button) => button.addEventListener('click', closeAgentModals));
document.querySelectorAll('.au-modal-overlay').forEach((overlay) => overlay.addEventListener('click', (event) => { if (event.target === overlay) closeAgentModals(); }));
document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeAgentModals(); });
</script>
@endpush
