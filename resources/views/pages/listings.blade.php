@extends('layouts.client')

@section('title', $pageTitle ?? 'Listings')

@section('content')
	<div class="listings-management-container">
		<div class="listings-header">
			<div>
				<h1>{{ $acting === 'agent' ? 'My Listings' : 'Team Listings' }}</h1>
				<p>{{ $acting === 'agent' ? "Properties you've listed." : 'Properties listed by you and your team.' }}</p>
			</div>
		</div>

		<div class="property-cards-grid">
			@forelse ($properties as $property)
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
						<p class="property-price">PHP {{ number_format($property->price, 0, '.', ',') }}</p>
						<div class="card-footer-actions"><div class="action-buttons"><a href="{{ route('properties.show', $property->property_id) }}" title="View" class="btn-action view" aria-label="View {{ $property->property_name }}"><i class="fas fa-eye"></i></a></div></div>
					</div>
				</div>
			@empty
				<div class="empty-state"><p>No properties yet.</p></div>
			@endforelse
		</div>
	</div>
@endsection
