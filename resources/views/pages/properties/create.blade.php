@extends('layouts.client')

@section('title', 'Add New Property')

@section('content')
    @php
        $amenityOptions = ['Pool', 'Garden', 'Gym', 'Security', 'Playground', 'Clubhouse', 'Parking', 'Ocean View', 'Outdoor Space', 'Home Office', 'Pet-Friendly', 'Accessible'];
        $lifestyleOptions = [
            'nature_lover' => 'Nature Lover',
            'city_explorer' => 'City Explorer',
            'family_oriented' => 'Family Oriented',
            'work_business' => 'Work & Business',
            'beach_lover' => 'Beach Lover',
            'peace_seeker' => 'Peace Seeker',
        ];
        $locationOptions = [
            'near_coast' => 'Near the Coast',
            'city_center' => 'City Center',
            'quiet_suburb' => 'Quiet Suburb',
            'business_district' => 'Business District',
            'countryside' => 'Countryside',
        ];
        $oldAmenities = is_array(old('amenities')) ? old('amenities') : array_filter(explode(',', old('amenities', '')));
        $oldLifestyleTags = is_array(old('lifestyle_tags')) ? old('lifestyle_tags') : array_filter(explode(',', old('lifestyle_tag', '')));
        $oldLocationTags = is_array(old('location_tags')) ? old('location_tags') : array_filter(explode(',', old('location_tag', '')));
        $propertyIndexPage = auth()->user()->actingRole() === 'admin' ? 'property' : 'listings';
        $propertyIndexLabel = $propertyIndexPage === 'property' ? 'properties' : 'listings';
    @endphp

    <div class="property-create-page staff-page-surface">
        <a class="property-create-back" href="{{ route('app.page', ['page' => $propertyIndexPage]) }}">&larr; Back to {{ $propertyIndexLabel }}</a>
        <header class="property-create-heading"><span>LISTING MANAGEMENT</span><h1>Add New Property</h1><p>Enter listing information and publish it to the property catalogue.</p></header>
        @if ($errors->any())
            <div class="property-create-errors" role="alert"><strong>Please review the form errors before continuing.</strong></div>
        @endif

        <form action="{{ route('admin.properties.store') }}" method="POST" enctype="multipart/form-data" class="property-create-form" data-type-requirements='@json(\App\Models\Property::detailRequirementMap())' data-type-visibility='@json(\App\Models\Property::detailVisibilityMap())'>
            @csrf
            <section class="property-create-section" aria-labelledby="createPhotosHeading">
                <div class="property-create-section-title"><span>01</span><div><h2 id="createPhotosHeading">Property photos</h2><p>Add up to 12 JPG, PNG, or WebP images.</p></div></div>
                <button class="property-upload-zone" type="button" id="dropzoneTrigger"><i data-lucide="upload" aria-hidden="true"></i><strong>Choose or drop photos here</strong><span>Clear photos help clients understand the space.</span></button>
                <input type="file" id="propertyImages" name="images[]" multiple accept="image/png,image/jpeg,image/webp" class="property-upload-input">
                <div class="property-image-previews" id="imagePreviewContainer" aria-live="polite"></div>
                @error('images')<small class="property-create-error">{{ $message }}</small>@enderror
                @error('images.*')<small class="property-create-error">{{ $message }}</small>@enderror
            </section>

            <section class="property-create-section" aria-labelledby="createBasicHeading">
                <div class="property-create-section-title"><span>02</span><div><h2 id="createBasicHeading">Basic information</h2><p>Core information shown in the public listing.</p></div></div>
                <div class="property-create-fields">
                    <label class="property-create-field"><span>Property name *</span><input name="property_name" value="{{ old('property_name') }}" maxlength="255" required>@error('property_name')<small>{{ $message }}</small>@enderror</label>
                    @php $typeOptions = \App\Models\Property::typeOptions(); $oldType = old('property_type'); @endphp
                    <div class="property-create-field-wide">
                        <fieldset class="property-choice-fieldset"><legend class="property-choice-heading">Property type *</legend>
                            <div class="property-choice-list" id="typeChoices">
                                @foreach ($typeOptions as $type)
                                    <label class="property-choice"><input type="radio" name="property_type" value="{{ $type }}" @checked($oldType === $type) required><span>{{ $type }}</span></label>
                                @endforeach
                                @if ($oldType && ! in_array($oldType, $typeOptions, true))
                                    <label class="property-choice is-custom"><input type="radio" name="property_type" value="{{ $oldType }}" checked required><span>{{ $oldType }}</span></label>
                                @endif
                            </div>
                        </fieldset>
                        <div class="property-add-choice"><label for="customType">Add a property type not listed</label><div><input id="customType" type="text" maxlength="100" placeholder="e.g. Beach Resort"><button type="button" id="addTypeChoice"><i data-lucide="plus" aria-hidden="true"></i> Add</button></div></div>
                        @error('property_type')<small class="property-create-error">{{ $message }}</small>@enderror
                    </div>
                    <label class="property-create-field"><span>Price (PHP) *</span><input type="number" min="0" step="0.01" name="price" value="{{ old('price') }}" placeholder="e.g. 2500000" required></label>
                    <label class="property-create-field"><span>Listing status *</span><select name="property_status" required>@foreach (['For Sale', 'For Rent', 'Rent to Own', 'Sold', 'Reserved', 'Unavailable'] as $status)<option value="{{ $status }}" @selected(old('property_status', 'For Sale') === $status)>{{ $status }}</option>@endforeach</select></label>
                    <label class="property-create-field property-create-field-wide property-create-description-field"><span>Description *</span><textarea name="description" rows="8" maxlength="5000" required>{{ old('description') }}</textarea></label>
                </div>
            </section>

            <section class="property-create-section" aria-labelledby="createDetailsHeading">
                <div class="property-create-section-title"><span>04</span><div><h2 id="createDetailsHeading">Property details</h2><p>Measurements and practical details for prospective clients.</p></div></div>
                <p class="property-detail-hint" data-detail-hint aria-live="polite"></p>
                <div class="property-create-fields property-create-fields-four">
                    <label class="property-create-field"><span>Lot area (sqm)</span><input type="number" min="0" step="0.01" name="lot_area" value="{{ old('lot_area') }}"></label>
                    <label class="property-create-field"><span>Floor area (sqm)</span><input type="number" min="0" step="0.01" name="floor_area" value="{{ old('floor_area') }}"></label>
                    <label class="property-create-field"><span>Bedrooms</span><input type="number" min="0" name="bedrooms" value="{{ old('bedrooms') }}"></label>
                    <label class="property-create-field"><span>Bathrooms</span><input type="number" min="0" name="bathrooms" value="{{ old('bathrooms') }}"></label>
                    <label class="property-create-field"><span>Parking spaces</span><input type="number" min="0" name="parking_spaces" value="{{ old('parking_spaces', 0) }}"></label>
                    <label class="property-create-field"><span>Number of floors</span><input type="number" min="1" name="number_of_floors" value="{{ old('number_of_floors', 1) }}"></label>
                    <label class="property-create-field"><span>Property condition</span><input name="property_condition" value="{{ old('property_condition') }}" maxlength="255" placeholder="Optional"></label>
                    <label class="property-create-field"><span>Year built</span><input type="number" min="1800" max="{{ now()->year + 1 }}" name="year_built" value="{{ old('year_built') }}"></label>
                </div>
            </section>

            <section class="property-create-section" aria-labelledby="createLocationHeading">
                <div class="property-create-section-title"><span>03</span><div><h2 id="createLocationHeading">Location</h2><p>Used to show the listing location and map position.</p></div></div>
                <div class="property-create-fields">
                    <label class="property-create-field"><span>House / unit number</span><input name="house_number" value="{{ old('house_number') }}" maxlength="255" placeholder="Optional"></label>
                    <label class="property-create-field"><span>Street *</span><input name="street" value="{{ old('street') }}" maxlength="255" required></label>
                    <label class="property-create-field"><span>City *</span><input name="city" value="{{ old('city') }}" maxlength="255" required></label>
                    <label class="property-create-field"><span>Province *</span><input name="province" value="{{ old('province') }}" maxlength="255" required></label>
                    <label class="property-create-field"><span>Lot number</span><input name="lot_number" value="{{ old('lot_number') }}" maxlength="255" placeholder="Optional"></label>
                </div>
            </section>

            <section class="property-create-section" aria-labelledby="createAmenitiesHeading">
                <div class="property-create-section-title"><span>05</span><div><h2 id="createAmenitiesHeading">Features and amenities</h2><p>Select all that apply or add a custom feature.</p></div></div>
                <fieldset class="property-choice-fieldset"><legend class="visually-hidden">Available amenities</legend>
                    <div class="property-choice-list" id="amenityChoices" data-choice-list="amenities">
                        @foreach ($amenityOptions as $amenity)
                            <label class="property-choice"><input type="checkbox" name="amenities[]" value="{{ $amenity }}" @checked(in_array($amenity, $oldAmenities, true))><span>{{ $amenity }}</span></label>
                        @endforeach
                        @foreach (array_diff($oldAmenities, $amenityOptions) as $customAmenity)
                            <label class="property-choice is-custom"><input type="checkbox" name="amenities[]" value="{{ $customAmenity }}" checked><span>{{ $customAmenity }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="property-add-choice"><label for="customAmenity">Add a feature not listed</label><div><input id="customAmenity" type="text" maxlength="100" data-custom-choice-input="amenities" placeholder="e.g. EV charging"><button type="button" data-add-choice="amenities"><i data-lucide="plus" aria-hidden="true"></i> Add</button></div></div>
                <label class="property-create-field property-create-field-wide property-remarks-field"><span>Additional remarks</span><textarea name="remarks" rows="3" maxlength="3000">{{ old('remarks') }}</textarea></label>
            </section>

            <section class="property-create-section" aria-labelledby="createLifestyleHeading">
                <div class="property-create-section-title"><span>06</span><div><h2 id="createLifestyleHeading">Lifestyle Match</h2><p>Select all lifestyles and settings that fit this property. Add a custom tag when needed.</p></div></div>
                <fieldset class="property-choice-fieldset">
                    <legend class="property-choice-heading">Lifestyle tags</legend>
                    <div class="property-choice-list" id="lifestyleChoices" data-choice-list="lifestyle_tags">
                        @foreach ($lifestyleOptions as $tag => $label)
                            <label class="property-choice"><input type="checkbox" name="lifestyle_tags[]" value="{{ $tag }}" @checked(in_array($tag, $oldLifestyleTags, true))><span>{{ $label }}</span></label>
                        @endforeach
                        @foreach (array_diff($oldLifestyleTags, array_keys($lifestyleOptions)) as $customTag)
                            <label class="property-choice is-custom"><input type="checkbox" name="lifestyle_tags[]" value="{{ $customTag }}" checked><span>{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $customTag)) }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="property-add-choice"><label for="customLifestyle">Add a lifestyle tag</label><div><input id="customLifestyle" type="text" maxlength="40" data-custom-choice-input="lifestyle_tags" placeholder="e.g. Retirement living"><button type="button" data-add-choice="lifestyle_tags"><i data-lucide="plus" aria-hidden="true"></i> Add</button></div></div>

                <fieldset class="property-choice-fieldset property-choice-heading-spaced">
                    <legend class="property-choice-heading">Location preferences</legend>
                    <div class="property-choice-list" id="locationChoices" data-choice-list="location_tags">
                        @foreach ($locationOptions as $tag => $label)
                            <label class="property-choice"><input type="checkbox" name="location_tags[]" value="{{ $tag }}" @checked(in_array($tag, $oldLocationTags, true))><span>{{ $label }}</span></label>
                        @endforeach
                        @foreach (array_diff($oldLocationTags, array_keys($locationOptions)) as $customTag)
                            <label class="property-choice is-custom"><input type="checkbox" name="location_tags[]" value="{{ $customTag }}" checked><span>{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $customTag)) }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="property-add-choice"><label for="customLocation">Add a location preference</label><div><input id="customLocation" type="text" maxlength="40" data-custom-choice-input="location_tags" placeholder="e.g. Near a university"><button type="button" data-add-choice="location_tags"><i data-lucide="plus" aria-hidden="true"></i> Add</button></div></div>
            </section>

            <section class="property-create-section property-create-publish" aria-labelledby="createPublishHeading">
                <div class="property-create-section-title"><span>07</span><div><h2 id="createPublishHeading">Publish</h2><p>Choose whether to feature this property on the home page.</p></div></div>
                <label class="property-choice property-feature-choice"><input type="checkbox" name="featured" value="1" @checked(old('featured'))><span>Feature this listing on the home page</span></label>
            </section>

            <footer class="property-create-actions"><a href="{{ route('app.page', ['page' => $propertyIndexPage]) }}">Cancel</a><button type="submit" class="btn-save"><i class="fas fa-check" aria-hidden="true"></i> Save Property</button></footer>
        </form>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/property-create.css') }}?v={{ filemtime(public_path('css/property-create.css')) }}">
@endpush

@push('scripts')
<script src="{{ asset('js/property-type-fields.js') }}?v={{ filemtime(public_path('js/property-type-fields.js')) }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-add-choice]').forEach(function (button) {
        button.addEventListener('click', function () {
            const group = button.dataset.addChoice;
            const input = document.querySelector('[data-custom-choice-input="' + group + '"]');
            const list = document.querySelector('[data-choice-list="' + group + '"]');
            const labelText = input.value.trim();
            if (!labelText) return;

            const value = group === 'amenities'
                ? labelText
                : labelText.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
            if (!value) return;
            const duplicate = Array.from(list.querySelectorAll('input[type="checkbox"]')).some(function (checkbox) {
                return checkbox.value.toLowerCase() === value.toLowerCase();
            });
            if (duplicate) {
                list.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                    if (checkbox.value.toLowerCase() === value.toLowerCase()) checkbox.checked = true;
                });
                input.value = '';
                return;
            }

            const choice = document.createElement('label');
            choice.className = 'property-choice is-custom';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = group + '[]';
            checkbox.value = value;
            checkbox.checked = true;
            const text = document.createElement('span');
            text.textContent = labelText;
            choice.append(checkbox, text);
            list.appendChild(choice);
            input.value = '';
        });
    });

    document.querySelectorAll('[data-custom-choice-input]').forEach(function (input) {
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                document.querySelector('[data-add-choice="' + input.dataset.customChoiceInput + '"]').click();
            }
        });
    });

    // const fileInput = document.getElementById('propertyImages');
    // const dropzone = document.getElementById('dropzoneTrigger');
    // const previews = document.getElementById('imagePreviewContainer');
    // if (fileInput && dropzone && previews) {
    //     dropzone.addEventListener('click', function () { fileInput.click(); });
    //     fileInput.addEventListener('change', function () {
    //         previews.replaceChildren();
    //         Array.from(fileInput.files).forEach(function (file) {
    //             const card = document.createElement('div');
    //             card.className = 'property-image-preview';
    //             const image = document.createElement('img');
    //             image.alt = file.name;
    //             image.src = URL.createObjectURL(file);
    //             const caption = document.createElement('span');
    //             caption.textContent = file.name;
    //             card.append(image, caption);
    //             previews.appendChild(card);
    //         });
    //     });
    //     dropzone.addEventListener('dragover', function (event) { event.preventDefault(); dropzone.classList.add('is-dragging'); });
    //     dropzone.addEventListener('dragleave', function () { dropzone.classList.remove('is-dragging'); });
    //     dropzone.addEventListener('drop', function (event) {
    //         event.preventDefault();
    //         dropzone.classList.remove('is-dragging');
    //         fileInput.files = event.dataTransfer.files;
    //         fileInput.dispatchEvent(new Event('change'));
    //     });
    // }

    const fileInput = document.getElementById('propertyImages');
    const dropzone = document.getElementById('dropzoneTrigger');
    const previews = document.getElementById('imagePreviewContainer');
    
    if (fileInput && dropzone && previews) {
        let selectedFiles = []; // Array to store accumulated files

        function updateFileList() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }

        function renderPreviews() {
            previews.replaceChildren();
            selectedFiles.forEach((file, index) => {
                const card = document.createElement('div');
                card.className = 'property-image-preview';
                
                const image = document.createElement('img');
                image.alt = file.name;
                image.src = URL.createObjectURL(file);
                
                const caption = document.createElement('span');
                caption.textContent = file.name;

                // Create individual remove button
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'property-remove-image';
                removeBtn.textContent = '×';
                
                removeBtn.addEventListener('click', function () {
                    selectedFiles.splice(index, 1);
                    updateFileList();
                    renderPreviews();
                });

                card.append(image, caption, removeBtn);
                previews.appendChild(card);
            });
        }

        dropzone.addEventListener('click', function () { fileInput.click(); });

        fileInput.addEventListener('change', function () {
            Array.from(fileInput.files).forEach(file => {
                // Prevent duplicate files based on name and size
                if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file);
                }
            });
            updateFileList();
            renderPreviews();
        });

        dropzone.addEventListener('dragover', function (event) { event.preventDefault(); dropzone.classList.add('is-dragging'); });
        dropzone.addEventListener('dragleave', function () { dropzone.classList.remove('is-dragging'); });
        
        dropzone.addEventListener('drop', function (event) {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
            
            Array.from(event.dataTransfer.files).forEach(file => {
                if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file);
                }
            });
            updateFileList();
            renderPreviews();
        });
    }
});
</script>
@endpush
