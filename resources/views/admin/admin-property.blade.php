@extends('layouts.client')

@section('title', 'Admin Property')

@section('content')
    <div class="listings-management-container staff-page-surface">
        
        <!-- Top Bar: Tabs on the left, Add Button on the right -->
        <div class="staff-top-bar">
            <div class="tab-navigation staff-tabs-top">
                <button type="button" class="tab-btn active" data-tab="project-listings">
                    Project Listings
                </button>
                <button type="button" class="tab-btn" data-tab="listing-requests">
                    Listing Requests
                </button>
            </div>
            
            <div class="header-action-btn" id="addPropertyBtnContainer">
                <a href="{{ route('admin.subfiles.addproperty') }}" class="btn-add-user-style">
                    <i class="fas fa-plus" aria-hidden="true"></i> Add Property
                </a>
            </div>
        </div>

        <!-- TAB 1: Project Listings -->
        <div class="tab-pane-content" id="project-listings-tab">
            <div class="listings-toolbar">
                <div class="search-box">
                    <span class="search-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                    <input id="propertySearch" type="search" placeholder="Search by name...">
                </div>
                <select id="propertyStatusFilter" class="btn-filter" aria-label="Filter properties by status">
                    <option value="">All statuses</option>
                    <option value="Available">Available</option>
                    <option value="For Sale">For Sale</option>
                    <option value="For Rent">For Rent</option>
                    <option value="Reserved">Reserved</option>
                    <option value="Pending">Pending</option>
                    <option value="Sold">Sold</option>
                    <option value="Unavailable">Unavailable</option>
                </select>
            </div>

            <div class="property-cards-grid">
                @forelse ($properties ?? [] as $property)
                    @php
                        $firstImage = $property->images->first();
                        $imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
                        $status = $property->property_status ?? 'Available';
                    @endphp
                    <div class="property-card-item" data-property-card data-property-name="{{ strtolower($property->property_name) }}" data-property-status="{{ $status }}">
                        <div class="card-img-wrapper">
                            <img src="{{ $imageUrl }}" alt="{{ $property->property_name }}">
                            <span class="badge status-{{ strtolower(str_replace(' ', '-', $status)) }} floating-badge">{{ $status }}</span>
                        </div>
                        <div class="card-body">
                            <div class="card-meta-top">
                                <span class="prop-id">ID: P{{ str_pad($property->property_id, 3, '0', STR_PAD_LEFT) }}</span>
                                <span class="badge type-house">{{ $property->property_type }}</span>
                            </div>
                            <h3 class="property-title">{{ $property->property_name }}</h3>
                            <p class="property-price">₱ {{ number_format($property->price, 0, '.', ',') }}</p>

                            <form method="POST" action="{{ route('admin.properties.status', $property) }}" class="card-status-select-wrapper">
                                @csrf
                                @method('PATCH')
                                <label for="status-{{ $property->property_id }}">Status:</label>
                                <select id="status-{{ $property->property_id }}" class="status-select" name="property_status" data-auto-submit>
                                    @foreach (['Available', 'For Sale', 'For Rent', 'Reserved', 'Pending', 'Sold', 'Unavailable'] as $statusOption)
                                        <option value="{{ $statusOption }}" {{ $status === $statusOption ? 'selected' : '' }}>{{ $statusOption }}</option>
                                    @endforeach
                                </select>
                            </form>

                            <form method="POST" action="{{ route('admin.properties.assign', $property) }}" class="property-assign-form">
                                @csrf
                                <label for="assign-{{ $property->property_id }}">Assign selling rights</label>
                                <select id="assign-{{ $property->property_id }}" name="user_ids[]" multiple size="3">
                                    @foreach ($assignableUsers ?? [] as $assignableUser)
                                        <option value="{{ $assignableUser->user_id }}">{{ $assignableUser->name }} ({{ $assignableUser->role_name }})</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn-assign-property">Assign selected</button>
                            </form>

                            <div class="card-footer-actions">
                                <div class="action-buttons">
                                    <a href="{{ route('staff.properties.show', $property) }}" title="View" class="btn-action view"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                    <a href="{{ route('admin.properties.edit', $property) }}" title="Edit" class="btn-action edit"><i class="fas fa-pen" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('admin.properties.destroy', $property) }}" class="inline-action-form">@csrf @method('DELETE')<button title="Delete" class="btn-action delete" type="submit"><i class="fas fa-trash" aria-hidden="true"></i></button></form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <p>No properties added yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- TAB 2: Listing Requests -->
        <div class="tab-pane-content" id="listing-requests-tab" style="display: none;">
            <div class="property-cards-grid">
                @php
                    $pendingRequests = collect($properties ?? [])->flatMap(function($property) {
                        return $property->assignments()->where('status', 'Pending')->with(['user', 'property.images'])->get();
                    });
                @endphp

                @forelse ($pendingRequests as $assignment)
                    @php
                        $property = $assignment->property;
                        $firstImage = $property?->images->first();
                        $imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
                    @endphp
                    <div class="property-card-item">
                        <div class="card-img-wrapper">
                            <img src="{{ $imageUrl }}" alt="{{ $property?->property_name }}">
                            <span class="badge status-pending floating-badge">Pending Request</span>
                        </div>
                        <div class="card-body">
                            <h3 class="property-title">{{ $property?->property_name }}</h3>
                            <p class="property-price">₱ {{ number_format($property?->price ?? 0, 0, '.', ',') }}</p>
                            
                            <div class="assignment-request-row">
                                <p><strong>{{ $assignment->user->name ?? 'User' }}</strong> requested to sell this property.</p>
                                <div class="request-action-btns" style="display: flex; gap: 10px;">
                                    <form method="POST" action="{{ route('admin.property-assignments.review', $assignment) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="Approved">
                                        <button type="submit" class="btn-assign-property" style="background: #10b981; color: white; padding: 6px 12px; border: none; border-radius: 6px;">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.property-assignments.review', $assignment) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="Rejected">
                                        <button type="submit" class="btn-assign-property" style="background: #ef4444; color: white; padding: 6px 12px; border: none; border-radius: 6px;">Reject</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <p>No pending listing requests found.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Script to handle tab toggling and button visibility -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane-content');
            const addPropertyBtn = document.getElementById('addPropertyBtnContainer');

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');

                    const target = button.getAttribute('data-tab');
                    
                    // Toggle panes
                    tabPanes.forEach(pane => {
                        pane.style.display = pane.id === target + '-tab' ? 'block' : 'none';
                    });

                    // Hide button if Listing Requests tab is active, show if Project Listings
                    if (target === 'listing-requests') {
                        addPropertyBtn.style.display = 'none';
                    } else {
                        addPropertyBtn.style.display = 'block';
                    }
                });
            });
        });
    </script>
@endsection