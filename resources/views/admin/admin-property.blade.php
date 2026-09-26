@extends('layouts.client')

@section('title', 'Admin Property')

@section('content')
    <div class="listings-management-container staff-page-surface">
        <div class="listings-header staff-page-heading">
            <a href="{{ route('admin.subfiles.addproperty') }}" class="btn-add-property"><i class="fas fa-plus" aria-hidden="true"></i> Add Property</a>
        </div>

        <div class="listings-toolbar">
            <div class="search-box">
                <span class="search-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                <input id="propertySearch" type="search" placeholder="Search by property name">
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

                        @if (($property->approval_status ?? 'Approved') === 'Pending')
                            <div class="property-approval-panel">
                                <p>Awaiting verification</p>
                                <form method="POST" action="{{ route('admin.properties.review', $property) }}" class="inline-action-form">
                                    @csrf
                                    <button name="approval_status" value="Approved" type="submit">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.properties.review', $property) }}" class="property-rejection-form">
                                    @csrf
                                    <label for="rejection-note-{{ $property->property_id }}">Reason for rejection</label>
                                    <textarea id="rejection-note-{{ $property->property_id }}" name="rejection_note" rows="2" maxlength="2000" required></textarea>
                                    <button name="approval_status" value="Rejected" type="submit">Reject and notify</button>
                                </form>
                            </div>
                        @endif

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

                        @foreach ($property->assignments()->where('status', 'Pending')->with('user')->get() as $assignment)
                            <div class="assignment-request-row">
                                <span>{{ $assignment->user->name }} requested to sell</span>
                                <form method="POST" action="{{ route('admin.property-assignments.review', $assignment) }}"><input type="hidden" name="status" value="Approved">@csrf<button type="submit">Approve</button></form>
                                <form method="POST" action="{{ route('admin.property-assignments.review', $assignment) }}"><input type="hidden" name="status" value="Rejected">@csrf<button type="submit">Reject</button></form>
                            </div>
                        @endforeach

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
@endsection