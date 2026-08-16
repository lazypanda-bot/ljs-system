@extends('layouts.client')

@section('title', 'Admin Property')

@section('content')
    <div class="listings-management-container">
        <div class="listings-header">
            <div>
                <h1>My Listings Management</h1>
                <p>You can manage your property listings here.</p>
            </div>
            <button type="button" class="btn-add-property" id="openAddModalBtn">+ Add Property</button>
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
                            {{-- FOR REF CODE --}}
                            {{-- <span class="ref-code">{{ $property->referral_code ?? 'REF-' . strtoupper(substr(md5($property->property_id), 0, 6)) }}</span> --}}
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

                            {{-- <form action="{{ route('agent.properties.generate-code', $property->property_id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-gen-code" title="Generate Code">
                                    Generate Code
                                </button>
                            </form> --}}

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

    <div class="modal-backdrop" id="addPropertyModal" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2>Add Property</h2>
                <button type="button" class="modal-close" id="closeAddModalBtn">&times;</button>
            </div>
            <form action="{{ route('admin.properties.store') }}" method="POST" enctype="multipart/form-data" class="modal-form">
                @csrf
                <div class="modal-body-grid">
                    <div class="form-main-content">
                        <label class="section-label">Images *</label>
                        <div class="image-upload-box">
                            <div class="upload-dropzone" onclick="document.getElementById('propertyImages').click()" style="cursor: pointer;">
                                <span>&#128228;</span>
                                <p>Click to browse images<br><small>or drag &amp; drop here</small></p>
                            </div>
                            <input type="file" id="propertyImages" name="images[]" multiple accept="image/png, image/jpeg, image/webp" style="display: none;" required>
                            <small class="help-text">You can upload up to 12 images (JPG, PNG, WebP)</small>
                        </div>

                        <h3 class="form-section-title">Basic Information</h3>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label>Property Name *</label>
                                <input type="text" name="property_name" required>
                            </div>
                            <div class="form-group">
                                <label>Property Type *</label>
                                <select name="property_type" required>
                                    <option value="">Select Type</option>
                                    <option>House &amp; Lot</option>
                                    <option>Condominium</option>
                                    <option>Commercial</option>
                                    <option>Lot</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Property Description *</label>
                            <textarea name="description" rows="3" required></textarea>
                        </div>

                        <div class="form-row-2">
                            {{-- <div class="form-group">
                                <label>Location *</label>
                                <input type="text" name="location" placeholder="e.g. Cebu City" required>
                            </div> --}}
                            <div class="form-group">
                                <label>Price (₱) *</label>
                                <input type="text" name="price" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Complete Address *</label>
                            <input type="text" name="address" required>
                        </div>

                        {{-- <input type="hidden" name="latitude" value="10.3157">
                        <input type="hidden" name="longitude" value="123.8854"> --}}

                        <h3 class="form-section-title">Property Details</h3>
                        <div class="form-grid-4">
                            <div class="form-group"><label>Lot Area (sqm) *</label><input type="number" name="lot_area" required></div>
                            <div class="form-group"><label>Floor Area (sqm) *</label><input type="number" name="floor_area" required></div>
                            <div class="form-group"><label>Bedrooms *</label><input type="number" name="bedrooms" required></div>
                            <div class="form-group"><label>Bathrooms *</label><input type="number" name="bathrooms" required></div>
                        </div>
                    </div>

                    <div class="form-sidebar-status">
                        <label class="section-label required">Property Status</label>
                        <div class="radio-status-group">
                            <label><input type="radio" name="property_status" value="For Sale" checked> For Sale</label>
                            <label><input type="radio" name="property_status" value="For Rent"> For Rent</label>
                            <label><input type="radio" name="property_status" value="Sold"> Sold</label>
                            <label><input type="radio" name="property_status" value="Pending"> Pending</label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancelModalBtn">Cancel</button>
                    <button type="submit" class="btn-save">Save Property</button>
                </div>
            </form>
        </div>
    </div>
@endsection
