@extends('layouts.client')

@section('title', 'Admin Property')

@section('content')
    <div class="listings-management-container">
        <div class="listings-header">
            <div>
                <h1>My Listings Management</h1>
                <p>You can manage your property listings here.</p>
            </div>
            <a href="{{ route('admin.subfiles.addproperty') }}" class="btn-add-property" style="text-decoration: none;">+ Add Property</a>
        </div>

        <div class="listings-toolbar">
            <div class="search-box">
                <span class="search-icon">&#128269;</span>
                <input type="text" placeholder="Search by property name">
            </div>
            <button type="button" class="btn-filter">&#128268; Filter</button>
        </div>

        <div class="property-cards-grid">
            @forelse ($properties ?? [] as $property)
                @php
                    $firstImage = $property->images->first();
                    $imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/night.png');
                    $status = $property->property_status ?? 'Available';
                @endphp
                <div class="property-card-item">
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

                        <div class="card-status-select-wrapper">
                            <label>Status:</label>
                            <select class="status-select">
                                <option {{ $status === 'Available' ? 'selected' : '' }}>Available</option>
                                <option {{ $status === 'Rented' ? 'selected' : '' }}>Rented</option>
                                <option {{ $status === 'Sold' ? 'selected' : '' }}>Sold</option>
                                <option {{ $status === 'Unavailable' ? 'selected' : '' }}>Unavailable</option>
                            </select>
                        </div>

                        <div class="card-footer-actions">
                            <div class="action-buttons">
                                <button title="View" class="btn-action view">&#128065;</button>
                                <button title="Edit" class="btn-action edit">&#9998;</button>
                                <button title="Delete" class="btn-action delete">&#128465;</button>
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