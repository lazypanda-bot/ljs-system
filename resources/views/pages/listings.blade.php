@extends('layouts.client')

@section('title', $pageTitle ?? 'Listings')

@section('content')
	<div class="listings-management-container">
		<div class="listings-header">
			<div>
				<h1>{{ $acting === 'agent' ? 'My Listings' : ($acting === 'broker' ? 'Team Listings' : 'All Listings') }}</h1>
				<p>{{ $acting === 'agent' ? "Properties you've listed." : ($acting === 'broker' ? 'Properties listed by you and your team.' : 'Properties managed by your organization.') }}</p>
			</div>
		</div>
		<div class="staff-listing-tabs" role="tablist"><button class="staff-listing-tab active" type="button" data-listing-panel="scoped-listings">{{ $acting === 'agent' ? 'My Listings' : ($acting === 'broker' ? 'Team Listings' : 'All Listings') }}</button><button class="staff-listing-tab" type="button" data-listing-panel="open-listings">Open Listings</button></div>

		@foreach (['scoped-listings' => $properties, 'open-listings' => $openProperties] as $panelId => $listingCollection)
		<div class="property-cards-grid staff-listing-panel {{ $panelId === 'scoped-listings' ? 'active' : '' }}" id="{{ $panelId }}" @if($panelId !== 'scoped-listings') hidden @endif>
			@forelse ($listingCollection as $property)
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
						<div class="card-footer-actions"><div class="action-buttons"><a href="{{ route('staff.properties.show', $property) }}" title="View" class="btn-action view" aria-label="View {{ $property->property_name }}"><i class="fas fa-eye"></i></a></div></div>
					</div>
				</div>
			@empty
				<div class="empty-state"><p>No properties yet.</p></div>
			@endforelse
		</div>
		@endforeach
	</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.staff-listing-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
        document.querySelectorAll('.staff-listing-tab').forEach(function (item) { item.classList.toggle('active', item === tab); });
        document.querySelectorAll('.staff-listing-panel').forEach(function (panel) { panel.hidden = panel.id !== tab.dataset.listingPanel; panel.classList.toggle('active', panel.id === tab.dataset.listingPanel); });
    });
});
</script>
@endpush
