@extends('layouts.client')

@section('title', 'Edit Property')

@section('content')
    <div class="main-content-container property-edit-page">
        <a class="back" href="{{ route('app.page', ['page' => 'property']) }}">&larr; Back to properties</a>
        <div class="page-header-flex"><div><h2>Edit Property</h2><p class="page-subtitle">Update the listing information.</p></div></div>
        <form method="POST" action="{{ route('admin.properties.update', $property) }}" class="content-card-form property-edit-form">
            @csrf @method('PUT')
            <div class="form-row-2"><div class="form-group"><label>Property Name</label><input name="property_name" value="{{ old('property_name', $property->property_name) }}" required></div><div class="form-group"><label>Price</label><input type="number" step="0.01" name="price" value="{{ old('price', $property->price) }}" required></div></div>
            <div class="form-group"><label>Description</label><textarea name="property_description" rows="5">{{ old('property_description', $property->property_description) }}</textarea></div>
            <div class="form-row-2"><div class="form-group"><label>Status</label><select name="property_status"><option {{ $property->property_status === 'For Sale' ? 'selected' : '' }}>For Sale</option><option {{ $property->property_status === 'For Rent' ? 'selected' : '' }}>For Rent</option><option {{ $property->property_status === 'Pending' ? 'selected' : '' }}>Pending</option><option {{ $property->property_status === 'Sold' ? 'selected' : '' }}>Sold</option><option {{ $property->property_status === 'Unavailable' ? 'selected' : '' }}>Unavailable</option></select></div><div class="form-group"><label>Street</label><input name="street" value="{{ old('street', $property->street) }}" required></div></div>
            <div class="form-row-2"><div class="form-group"><label>City</label><input name="city" value="{{ old('city', $property->city) }}" required></div><div class="form-group"><label>Province</label><input name="province" value="{{ old('province', $property->province) }}" required></div></div>
            <button class="btn-save" type="submit">Save Changes</button>
        </form>
    </div>
@endsection