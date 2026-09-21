@extends('layouts.client')

@section('title', 'Add New Property')

@section('content')
    <div class="back-btn">
        <a href="{{ route('app.page', ['page' => 'property']) }}" class="back">
            <i class="arrow"></i> Properties
        </a>
    </div>
    <div class="main-content-container">
        <div class="page-header-flex">
            <div>
                <h2>Add New Property Listing</h2>
                <p class="page-subtitle">Fill in the details below to publish a new property.</p>
            </div>
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
                        <label for="price" class="form-label">Price *</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" placeholder="e.g. 2500000" required>
                    </div>

                    <div class="form-group">
                        <label>Property Description *</label>
                        <textarea name="description" rows="3" required></textarea>
                    </div>

                    <h3 class="form-section-title">Property Location</h3>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>House Number</label>
                            <input type="text" name="house_number" placeholder="e.g. Blk 4 Lot 2">
                        </div>
                        <div class="form-group">
                            <label>Street *</label>
                            <input type="text" name="street" placeholder="e.g. V. Rama Ave, Guadalupe" required>
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label>City *</label>
                            <input type="text" name="city" placeholder="e.g. Cebu City" required>
                        </div>
                        <div class="form-group">
                            <label>Province *</label>
                            <input type="text" name="province" placeholder="e.g. Cebu" required>
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

                    <h3 class="form-section-title">Lifestyle Match</h3>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Lifestyle Tag</label>
                            <select name="lifestyle_tag">
                                <option value="">Select Lifestyle</option>
                                <option value="nature_lover">Nature Lover</option>
                                <option value="city_explorer">City Explorer</option>
                                <option value="family_oriented">Family Oriented</option>
                                <option value="work_business">Work &amp; Business</option>
                                <option value="beach_lover">Beach Lover</option>
                                <option value="peace_seeker">Peace Seeker</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Location Preference</label>
                            <select name="location_tag">
                                <option value="">Select Location Preference</option>
                                <option value="near_coast">Near the Coast</option>
                                <option value="city_center">City Center</option>
                                <option value="quiet_suburb">Quiet Suburb</option>
                                <option value="business_district">Business District</option>
                                <option value="countryside">Countryside</option>
                            </select>
                        </div>
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

                    <div class="form-group">
                        <label><input type="checkbox" name="featured" value="1"> Feature this listing on the home page</label>
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
            const previewContainer = document.getElementById('imagePreviewContainer');

            if (fileInput && previewContainer) {
                fileInput.addEventListener('change', function() {
                    previewContainer.querySelectorAll('.image-preview').forEach((preview) => preview.remove());
                    Array.from(fileInput.files).forEach((file) => {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const image = document.createElement('img');
                            image.className = 'image-preview';
                            image.src = event.target.result;
                            image.alt = file.name;
                            previewContainer.insertBefore(image, addMore);
                        };
                        reader.readAsDataURL(file);
                    });
                });
            }

            if (dropzone && fileInput) {
                dropzone.addEventListener('click', () => fileInput.click());
            }
            if (addMore && fileInput) {
                addMore.addEventListener('click', () => fileInput.click());
            }
        });
    </script>
@endsection