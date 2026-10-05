@extends('layouts.client')

@section('title', 'Admin Property')

@section('content')
    <div class="listings-management-container staff-page-surface">
        <div class="staff-property-admin-header">
            <div><h2>Property inventory</h2><p>Review submissions and manage listing status.</p></div>
        </div>

        <div class="au-tabs-container staff-property-tabs" role="tablist" aria-label="Property views">
            <button type="button" class="au-tab-btn active" data-admin-property-tab="project-listings" aria-selected="true">Project Listings</button>
            <button type="button" class="au-tab-btn" data-admin-property-tab="listing-requests" aria-selected="false">Listing Requests <span class="staff-property-tab-count">{{ $pendingListings->count() }}</span></button>
        </div>

        <section class="staff-property-tab-panel active" id="project-listings-panel" data-admin-property-panel="project-listings">
            <div class="listings-toolbar">
                <div class="search-box">
                    <span class="search-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                    <input id="propertySearch" type="search" placeholder="Search by name...">
                </div>
                <select id="propertyStatusFilter" class="btn-filter" aria-label="Filter properties by status">
                    <option value="">All statuses</option>
                    <option value="For Sale">For Sale</option>
                    <option value="For Rent">For Rent</option>
                    <option value="Sold">Sold</option>
                    <option value="Reserved">Reserved</option>
                    <option value="Unavailable">Unavailable</option>
                </select>
                <a href="{{ route('admin.subfiles.addproperty') }}" class="btn-add-property"><i class="fas fa-plus" aria-hidden="true"></i> Add Property</a>
            </div>

            <div class="staff-property-table-wrap">
                <table class="staff-property-table">
                    <thead><tr><th>Image</th><th>Property</th><th>Price</th><th>Type</th><th>Status</th><th>Referral link</th><th>Actions</th></tr></thead>
                    <tbody>
                @forelse (($properties ?? collect())->filter(fn ($property) => $property->listing_mode === 'admin')->sortBy('created_at') as $property)
                    @php
                        $firstImage = $property->images->first();
                        $imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
                        $status = $property->property_status ?? 'Available';
                        $listingReferralCode = $property->approval_status === 'Approved'
                            ? $property->referralCodes->firstWhere('user_id', $property->user_id)
                            : null;
                    @endphp
                    <tr data-property-card data-property-name="{{ strtolower($property->property_name) }}" data-property-status="{{ $status }}">
                        <td class="staff-property-image-cell"><img src="{{ $imageUrl }}" alt="{{ $property->property_name }}"></td>
                        <td class="staff-property-name-cell"><strong>{{ $property->property_name }}</strong><small>ID: P{{ str_pad($property->property_id, 3, '0', STR_PAD_LEFT) }}</small><span class="staff-approval-state state-{{ strtolower($property->approval_status) }}">{{ $property->approval_status }}</span></td>
                        <td class="staff-property-price-cell">PHP {{ number_format($property->price, 0, '.', ',') }}</td>
                        <td>{{ $property->property_type }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.properties.status', $property) }}" class="staff-property-status-form">
                                @csrf
                                @method('PATCH')
                                <label for="status-{{ $property->property_id }}">Status:</label>
                                <select id="status-{{ $property->property_id }}" class="status-select" name="property_status" data-auto-submit aria-label="Property status">
                                    @foreach (array_unique(array_merge(['For Sale', 'For Rent', 'Sold', 'Reserved', 'Unavailable'], [$status])) as $statusOption)
                                        <option value="{{ $statusOption }}" {{ $status === $statusOption ? 'selected' : '' }}>{{ $statusOption }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="staff-property-referral-cell">
                            @if ($listingReferralCode)
                                <code>{{ $listingReferralCode->code }}</code>
                                <button type="button" class="staff-referral-copy" data-copy-referral="{{ route('referrals.open', $listingReferralCode->code) }}"><i class="fas fa-copy" aria-hidden="true"></i> Copy link</button>
                            @elseif ($property->approval_status === 'Approved')
                                <form method="POST" action="{{ route('agent.properties.generate-code', $property->property_id) }}">
                                    @csrf
                                    <button type="submit" class="staff-referral-copy"><i class="fas fa-link" aria-hidden="true"></i> Generate link</button>
                                </form>
                            @else
                                <span class="staff-unassigned">Not available</span>
                            @endif
                        </td>
                        <td><div class="action-buttons staff-property-actions">
                            <a href="{{ route('staff.properties.show', $property) }}" title="View" aria-label="View {{ $property->property_name }}" class="btn-action view"><i class="fas fa-eye" aria-hidden="true"></i></a>
                            <a href="{{ route('admin.properties.edit', $property) }}" title="Edit" aria-label="Edit {{ $property->property_name }}" class="btn-action edit"><i class="fas fa-pen" aria-hidden="true"></i></a>
                            <form method="POST" action="{{ route('admin.properties.destroy', $property) }}" class="inline-action-form">@csrf @method('DELETE')<button title="Delete" aria-label="Delete {{ $property->property_name }}" class="btn-action delete" type="submit"><i class="fas fa-trash" aria-hidden="true"></i></button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="staff-table-empty">No properties added yet.</td></tr>
                @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="staff-property-tab-panel" id="listing-requests-panel" data-admin-property-panel="listing-requests" hidden>
            @php $requestListings = ($properties ?? collect())->filter(fn ($property) => $property->listing_mode !== 'admin')->sortBy('created_at'); @endphp
            <div class="listings-toolbar">
                <div class="search-box">
                    <span class="search-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                    <input id="requestSearch" type="search" placeholder="Search by property or submitter...">
                </div>
                <select id="requestStatusFilter" class="btn-filter" aria-label="Filter requests by approval status">
                    <option value="">All statuses</option>
                    <option value="Approved">Approved</option>
                    <option value="Pending">Pending</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <div class="staff-property-table-wrap">
                <table class="staff-property-table staff-property-requests-table">
                    <thead><tr><th>Image</th><th>Property submission</th><th>Price</th><th>Type</th><th>Submitted by</th><th>Status / Review</th></tr></thead>
                    <tbody>
                @forelse ($requestListings as $property)
                    @php
                        $firstImage = $property->images->first();
                        $imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
                    @endphp
                    <tr data-request-row data-request-text="{{ strtolower($property->property_name . ' ' . ($property->user?->name ?? '')) }}" data-request-status="{{ $property->approval_status }}">
                        <td class="staff-property-image-cell"><img src="{{ $imageUrl }}" alt="{{ $property->property_name }}"></td>
                        <td class="staff-property-name-cell"><strong>{{ $property->property_name }}</strong><small>{{ Str::limit($property->property_description, 110) }}</small></td>
                        <td class="staff-property-price-cell">PHP {{ number_format($property->price, 0, '.', ',') }}</td>
                        <td>{{ $property->property_type }}</td>
                        <td>{{ $property->user?->name ?? 'Unknown user' }}<small class="staff-property-role">{{ $property->user?->role_name }}</small></td>
                        <td>
                            @if ($property->approval_status === 'Pending')
                            <div class="staff-review-actions">
                                <a class="staff-view-button" href="{{ route('staff.properties.show', $property) }}"><i class="fas fa-eye" aria-hidden="true"></i> View details</a>
                                <div class="staff-review-decision-actions">
                                    <form method="POST" action="{{ route('admin.properties.review', $property) }}">
                                        @csrf
                                        <button class="staff-approve-button" type="submit" name="approval_status" value="Approved"><i class="fas fa-check" aria-hidden="true"></i> Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.properties.review', $property) }}" class="staff-reject-form" data-reject-form>
                                        @csrf
                                        <button class="staff-reject-button" type="button" data-reject-open><i class="fas fa-xmark" aria-hidden="true"></i> Reject</button>
                                        <div class="staff-reject-fields" hidden>
                                            <label class="visually-hidden" for="rejection-note-{{ $property->property_id }}">Rejection reason</label>
                                            <textarea id="rejection-note-{{ $property->property_id }}" name="rejection_note" rows="2" maxlength="2000" placeholder="Reason for rejecting"></textarea>
                                            <button class="staff-reject-button" type="submit" name="approval_status" value="Rejected">Confirm reject</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @else
                                <span class="staff-approval-state state-{{ strtolower($property->approval_status) }}">{{ $property->approval_status }}</span>
                                @if ($property->approval_status === 'Rejected' && $property->rejection_note)<small class="staff-property-role">{{ $property->rejection_note }}</small>@endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="staff-table-empty">No property submissions yet.</td></tr>
                @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-admin-property-tab]').forEach(function (tab) {
    tab.addEventListener('click', function () {
        document.querySelectorAll('[data-admin-property-tab]').forEach(function (item) {
            const active = item === tab;
            item.classList.toggle('active', active);
            item.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        document.querySelectorAll('[data-admin-property-panel]').forEach(function (panel) {
            panel.hidden = panel.dataset.adminPropertyPanel !== tab.dataset.adminPropertyTab;
            panel.classList.toggle('active', !panel.hidden);
        });
    });
});
(function () {
    document.querySelectorAll('[data-reject-form]').forEach(function (form) {
        const open = form.querySelector('[data-reject-open]');
        const fields = form.querySelector('.staff-reject-fields');
        const note = form.querySelector('textarea');
        open.addEventListener('click', function () {
            fields.hidden = !fields.hidden;
            note.required = !fields.hidden;
            if (!fields.hidden) note.focus();
        });
    });
})();
(function () {
    const search = document.getElementById('requestSearch');
    const status = document.getElementById('requestStatusFilter');
    const apply = function () {
        const term = (search?.value || '').trim().toLowerCase();
        document.querySelectorAll('[data-request-row]').forEach(function (row) {
            const matchesText = row.dataset.requestText.includes(term);
            const matchesStatus = !status?.value || row.dataset.requestStatus === status.value;
            row.hidden = !(matchesText && matchesStatus);
        });
    };
    search?.addEventListener('input', apply);
    status?.addEventListener('change', apply);
})();
document.querySelectorAll('[data-copy-referral]').forEach(function (button) {
    button.addEventListener('click', async function () {
        const referralUrl = button.dataset.copyReferral;
        try {
            await navigator.clipboard.writeText(referralUrl);
            button.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Copied';
        } catch {
            window.prompt('Copy this referral link', referralUrl);
        }
    });
});
</script>
@endpush