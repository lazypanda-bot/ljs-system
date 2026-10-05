@extends('layouts.client')

@section('title', 'Edit Property')

@section('content')
    @php($isAdmin = auth()->user()?->actingRole() === 'admin')
    <div class="property-edit-page staff-page-surface">
        <a class="property-edit-back" href="{{ route('app.page', ['page' => $isAdmin ? 'property' : 'listings']) }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to {{ $isAdmin ? 'properties' : 'listings' }}</a>
        <header class="property-edit-heading"><span>LISTING MANAGEMENT</span><h1>Edit Property</h1><p>Update the listing, property details, and photo gallery.</p></header>
        @if ($property->rejection_note)
            <div class="listing-rejection-note"><strong>Admin requested these changes</strong><p>{{ $property->rejection_note }}</p></div>
        @endif
        @if ($errors->any())
            <div class="property-edit-errors" role="alert"><strong>Please review the highlighted form errors.</strong></div>
        @endif

        <form method="POST" action="{{ route('admin.properties.update', $property) }}" enctype="multipart/form-data" class="property-edit-form" data-type-requirements='@json(\App\Models\Property::detailRequirementMap())' data-type-visibility='@json(\App\Models\Property::detailVisibilityMap())'>
            @csrf @method('PUT')
            <section class="property-edit-section" aria-labelledby="editBasicHeading">
                <div class="property-edit-section-heading"><span>01</span><div><h2 id="editBasicHeading">Basic information</h2><p>Public listing title, type, price, and status.</p></div></div>
                <div class="property-edit-fields">
                    <label class="property-edit-field"><span>Property name</span><input name="property_name" value="{{ old('property_name', $property->property_name) }}" required>@error('property_name')<small>{{ $message }}</small>@enderror</label>
                    @php($typeOptions = \App\Models\Property::typeOptions())
                    @php($selectedType = old('property_type', $property->property_type))
                    <div class="property-edit-field property-edit-field-wide">
                        <fieldset class="property-choice-fieldset"><legend class="property-choice-heading">Property type</legend>
                            <div class="property-choice-list" id="typeChoices">
                                @foreach ($typeOptions as $type)
                                    <label class="property-choice"><input type="radio" name="property_type" value="{{ $type }}" @checked($selectedType === $type) required><span>{{ $type }}</span></label>
                                @endforeach
                                @if ($selectedType && ! in_array($selectedType, $typeOptions, true))
                                    <label class="property-choice is-custom"><input type="radio" name="property_type" value="{{ $selectedType }}" checked required><span>{{ $selectedType }}</span></label>
                                @endif
                            </div>
                        </fieldset>
                        <div class="property-add-choice"><label for="customType">Add a property type not listed</label><div><input id="customType" type="text" maxlength="100" placeholder="e.g. Beach Resort"><button type="button" id="addTypeChoice">Add</button></div></div>
                        @error('property_type')<small>{{ $message }}</small>@enderror
                    </div>
                    <label class="property-edit-field"><span>Price (PHP)</span><input type="number" min="0" step="0.01" name="price" value="{{ old('price', $property->price) }}" required>@error('price')<small>{{ $message }}</small>@enderror</label>
                    <label class="property-edit-field"><span>Status</span><select name="property_status" required>@foreach (array_unique(array_merge(['For Sale', 'For Rent', 'Sold', 'Reserved', 'Unavailable'], [(string) $property->property_status])) as $status)<option value="{{ $status }}" @selected(old('property_status', $property->property_status) === $status)>{{ $status }}</option>@endforeach</select></label>
                    <label class="property-edit-field property-edit-field-wide"><span>Description</span><textarea name="property_description" rows="4">{{ old('property_description', $property->property_description) }}</textarea></label>
                </div>
            </section>

            <section class="property-edit-section" aria-labelledby="editLocationHeading">
                <div class="property-edit-section-heading"><span>02</span><div><h2 id="editLocationHeading">Location</h2><p>Address fields are used to refresh the map position.</p></div></div>
                <div class="property-edit-fields">
                    <label class="property-edit-field"><span>House / unit number</span><input name="house_number" value="{{ old('house_number', $property->house_number) }}"></label>
                    <label class="property-edit-field"><span>Street</span><input name="street" value="{{ old('street', $property->street) }}" required></label>
                    <label class="property-edit-field"><span>City</span><input name="city" value="{{ old('city', $property->city) }}" required></label>
                    <label class="property-edit-field"><span>Province</span><input name="province" value="{{ old('province', $property->province) }}" required></label>
                    <label class="property-edit-field"><span>Lot number</span><input name="lot_number" value="{{ old('lot_number', $property->lot_number) }}"></label>
                </div>
            </section>

            <section class="property-edit-section" aria-labelledby="editDetailsHeading">
                <div class="property-edit-section-heading"><span>03</span><div><h2 id="editDetailsHeading">Property details</h2><p>Measurements, rooms, condition, and amenities.</p></div></div>
                <p class="property-detail-hint" data-detail-hint aria-live="polite"></p>
                <div class="property-edit-fields property-edit-fields-four">
                    <label class="property-edit-field"><span>Lot area (sqm)<b class="detail-required-mark" hidden> *</b></span><input type="number" min="0" step="0.01" name="lot_area" value="{{ old('lot_area', $property->details?->lot_area) }}"></label>
                    <label class="property-edit-field"><span>Floor area (sqm)<b class="detail-required-mark" hidden> *</b></span><input type="number" min="0" step="0.01" name="floor_area" value="{{ old('floor_area', $property->details?->floor_area) }}"></label>
                    <label class="property-edit-field"><span>Bedrooms<b class="detail-required-mark" hidden> *</b></span><input type="number" min="0" name="bedrooms" value="{{ old('bedrooms', $property->details?->bedroom ?? 0) }}"></label>
                    <label class="property-edit-field"><span>Bathrooms<b class="detail-required-mark" hidden> *</b></span><input type="number" min="0" name="bathrooms" value="{{ old('bathrooms', $property->details?->bathroom ?? 0) }}"></label>
                    <label class="property-edit-field"><span>Parking spaces</span><input type="number" min="0" name="parking_spaces" value="{{ old('parking_spaces', $property->details?->parking_spaces ?? 0) }}"></label>
                    <label class="property-edit-field"><span>Number of floors</span><input type="number" min="1" name="number_of_floors" value="{{ old('number_of_floors', $property->details?->number_of_floors ?? 1) }}"></label>
                    <label class="property-edit-field"><span>Condition</span><input name="property_condition" value="{{ old('property_condition', $property->details?->property_condition) }}"></label>
                    <label class="property-edit-field"><span>Year built</span><input type="number" min="1800" max="{{ now()->year + 1 }}" name="year_built" value="{{ old('year_built', $property->details?->year_built) }}"></label>
                    <label class="property-edit-field property-edit-field-wide"><span>Amenities (comma separated)</span><input name="amenities" value="{{ old('amenities', $property->details?->amenities->pluck('amenity_name')->implode(', ')) }}" placeholder="Garden, security, swimming pool"></label>
                    <label class="property-edit-field property-edit-field-wide"><span>Additional remarks</span><textarea name="remarks" rows="3">{{ old('remarks', $property->details?->remarks) }}</textarea></label>
                </div>
            </section>

            <section class="property-edit-section" aria-labelledby="editImagesHeading">
                <div class="property-edit-section-heading"><span>04</span><div><h2 id="editImagesHeading">Property photos</h2><p>Choose photos to remove or add new images. Maximum 12 photos total.</p></div></div>
                @if ($property->images->isNotEmpty())
                    <div class="property-edit-gallery">
                        @foreach ($property->images as $image)
                            <label class="property-edit-image"><img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $property->property_name }} photo"><span><input type="checkbox" name="remove_images[]" value="{{ $image->image_id }}"> Remove</span></label>
                        @endforeach
                    </div>
                @else
                    <p class="property-edit-no-images">No photos uploaded yet.</p>
                @endif
                <label class="property-edit-upload"><i class="fas fa-cloud-arrow-up" aria-hidden="true"></i><span>Add photos</span><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple><small>JPG, PNG, or WebP; 2 MB each.</small></label>
                @error('images')<small class="property-edit-error">{{ $message }}</small>@enderror
                @error('images.*')<small class="property-edit-error">{{ $message }}</small>@enderror
            </section>

            <section class="property-edit-section" aria-labelledby="editDiscoveryHeading">
                <div class="property-edit-section-heading"><span>05</span><div><h2 id="editDiscoveryHeading">Discovery</h2><p>Optional tags that help clients find this listing.</p></div></div>
                <div class="property-edit-fields">
                    <label class="property-edit-field"><span>Lifestyle tag</span>
                        <select name="lifestyle_tag"><option value="">None</option>
                            @foreach (['nature_lover' => 'Nature Lover', 'city_explorer' => 'City Explorer', 'family_oriented' => 'Family Oriented', 'work_business' => 'Work & Business', 'beach_lover' => 'Beach Lover', 'peace_seeker' => 'Peace Seeker'] as $tag => $label)
                                <option value="{{ $tag }}" @selected(old('lifestyle_tag', $property->lifestyle_tag) === $tag)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="property-edit-field"><span>Location tag</span>
                        <select name="location_tag"><option value="">None</option>
                            @foreach (['near_coast' => 'Near the Coast', 'city_center' => 'City Center', 'quiet_suburb' => 'Quiet Suburb', 'business_district' => 'Business District', 'countryside' => 'Countryside'] as $tag => $label)
                                <option value="{{ $tag }}" @selected(old('location_tag', $property->location_tag) === $tag)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="property-edit-featured"><input type="checkbox" name="featured" value="1" @checked(old('featured', $property->featured))><span>Feature this listing on the home page</span></label>
                </div>
            </section>

            <footer class="property-edit-actions"><a href="{{ route('app.page', ['page' => $isAdmin ? 'property' : 'listings']) }}">Cancel</a><button class="btn-save" type="submit">{{ $isAdmin ? 'Save all changes' : 'Resubmit for approval' }}</button></footer>
        </form>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/property-edit.css') }}?v={{ filemtime(public_path('css/property-edit.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/property-create.css') }}?v={{ filemtime(public_path('css/property-create.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/property-type-fields.js') }}?v={{ filemtime(public_path('js/property-type-fields.js')) }}" defer></script>
@endpush