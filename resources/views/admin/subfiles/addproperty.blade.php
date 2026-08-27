@extends('layouts.client')

@section('title', 'Add New Property')

@section('content')
    <div class="main-content-container">
        <div class="page-header-flex">
            <div>
                <h2>Add New Property Listing</h2>
                <p class="page-subtitle">Fill in the details below to publish a new property.</p>
            </div>
            <a href="{{ route('admin.subfiles.addproperty') }}" class="btn-cancel text-decoration-none">Cancel</a>
        </div>

        <form action="{{ route('admin.properties.store') }}" method="POST" enctype="multipart/form-data" class="content-card-form">
            @csrf
            <div class="modal-body-grid">
                <div class="form-main-content">
                    <label class="section-label">Images *</label>
                    <div class="image-upload-box-row">
                        <div class="upload-dropzone-mini" id="dropzoneTrigger">
                            <span>&#128228;</span>
                            <p>Drag &amp; drop images here<br><small>or click to browse</small></p>
                        </div>
                        <div class="image-preview-slots" id="imagePreviewContainer">
                            <div class="upload-add-more" id="addMoreTrigger">+</div>
                        </div>
                        <input type="file" id="propertyImages" name="images[]" multiple accept="image/png, image/jpeg, image/webp" class="d-none" required>
                    </div>
                    <small class="help-text">You can upload up to 12 images (JPG, PNG, WebP)</small>

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
                        <div class="form-group">
                            <label>Complete Address *</label>
                            <div class="input-with-icon">
                                <input type="text" name="address" placeholder="e.g. Cebu City, Cebu, Philippines" required>
                                <span class="input-icon">&#128205;</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Price (₱) *</label>
                            <input type="text" name="price" required>
                        </div>
                    </div>

                    <h3 class="form-section-title">Property Details</h3>
                    
                    <div class="form-grid-4">
                        <div class="form-group"><label>Lot Area (sqm) *</label><input type="number" name="lot_area" required></div>
                        <div class="form-group"><label>Floor Area (sqm) *</label><input type="number" name="floor_area" required></div>
                        <div class="form-group"><label>Bedrooms *</label><input type="number" name="bedrooms" required></div>
                        <div class="form-group"><label>Bathrooms *</label><input type="number" name="bathrooms" required></div>
                    </div>

                    <div class="form-grid-4">
                        <div class="form-group"><label>Living Rooms</label><input type="number" name="living_rooms"></div>
                        <div class="form-group"><label>Dining Area</label><input type="number" name="dining_area"></div>
                        <div class="form-group"><label>Kitchen</label><input type="number" name="kitchen"></div>
                        <div class="form-group">
                            <label>Balcony</label>
                            <select name="balcony">
                                <option value="">Select</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid-4">
                        <div class="form-group"><label>Garage</label><input type="text" name="garage" placeholder="e.g. 2 Car Garage"></div>
                        <div class="form-group">
                            <label>Furnishing</label>
                            <select name="furnishing">
                                <option value="">Select</option>
                                <option>Unfurnished</option>
                                <option>Semi-Furnished</option>
                                <option>Fully Furnished</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Parking</label><input type="number" name="parking"></div>
                        <div class="form-group"><label>Year Built</label><input type="number" name="year_built" placeholder="2022"></div>
                    </div>

                    <div class="form-group amenities-group">
                        <label>Other Features / Amenities</label>
                        <input type="text" name="amenities" placeholder="Garden, Security System, Swimming Pool">
                    </div>
                </div>

                <div class="form-sidebar-status">
                    <div class="form-group">
                        <label class="section-label required">Property Status</label>
                        <div class="radio-status-group">
                            <label><input type="radio" name="property_status" value="For Sale" checked> For Sale</label>
                            <label><input type="radio" name="property_status" value="For rent"> For Rent</label>
                            <label><input type="radio" name="property_status" value="Sold"> Sold</label>
                            <label><input type="radio" name="property_status" value="Pending"> Pending</label>
                        </div>
                    </div>

                    <div class="form-group reference-group">
                        <label class="section-label required">Reference Code *</label>
                        <div class="reference-input-row">
                            <input type="text" id="referenceCodeInput" name="reference_code" placeholder="e.g. P001" required>
                            <button type="button" id="generateRefBtn" class="btn-secondary">Generate</button>
                        </div>
                        <small class="help-text reference-help">Unique identifier code.</small>
                    </div>

                    <div class="form-actions-sidebar">
                        <button type="submit" class="btn-save">Save Property</button>
                        <a href="{{ route('admin.subfiles.addproperty') }}" class="btn-cancel text-decoration-none text-center">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Isolated Page Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Reference Code Generator
            const generateBtn = document.getElementById('generateRefBtn');
            const refInput = document.getElementById('referenceCodeInput');
            
            if (generateBtn && refInput) {
                generateBtn.addEventListener('click', function() {
                    const randomNum = Math.floor(100 + Math.random() * 900);
                    refInput.value = 'P' + randomNum;
                });
            }

            // File Upload Triggers
            const fileInput = document.getElementById('propertyImages');
            const dropzone = document.getElementById('dropzoneTrigger');
            const addMore = document.getElementById('addMoreTrigger');

            if (dropzone && fileInput) {
                dropzone.addEventListener('click', () => fileInput.click());
            }
            if (addMore && fileInput) {
                addMore.addEventListener('click', () => fileInput.click());
            }
        });
    </script>
@endsection