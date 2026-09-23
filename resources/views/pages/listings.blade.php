@extends('layouts.client')

@section('title', $pageTitle ?? 'Listings')

@section('content')
	<div class="listings-management-container staff-page-surface">
		<div class="listings-header staff-page-heading">
			<div>
				<h1>{{ $acting === 'agent' ? 'My Listings' : ($acting === 'broker' ? 'Listings' : 'All Listings') }}</h1>
				<p>{{ $acting === 'agent' ? "Properties you've listed." : ($acting === 'broker' ? 'Manage your listings and view your team inventory.' : 'Properties managed by your organization.') }}</p>
			</div>
		</div>
		<div class="staff-listing-tabs" role="tablist">
			@if ($acting === 'broker')
				<button class="staff-listing-tab active" type="button" data-listing-panel="my-listings">My Listings</button>
				<button class="staff-listing-tab" type="button" data-listing-panel="team-listings">Team Listings</button>
			@else
				<button class="staff-listing-tab active" type="button" data-listing-panel="scoped-listings">{{ $acting === 'agent' ? 'My Listings' : 'All Listings' }}</button>
			@endif
			<button class="staff-listing-tab" type="button" data-listing-panel="open-listings">Open Listings</button>
		</div>

		@php
			$listingPanels = $acting === 'broker'
				? ['my-listings' => $myProperties, 'team-listings' => $teamProperties, 'open-listings' => $openProperties]
				: ['scoped-listings' => $properties, 'open-listings' => $openProperties];
		@endphp
		@foreach ($listingPanels as $panelId => $listingCollection)
		<div class="property-cards-grid staff-listing-panel {{ $loop->first ? 'active' : '' }}" id="{{ $panelId }}" @if(!$loop->first) hidden @endif>
			@forelse ($listingCollection as $property)
				@php
					$firstImage = $property->images->first();
					$imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
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
						<div class="card-footer-actions"><div class="action-buttons"><a href="{{ route('staff.properties.show', $property) }}" title="View" class="btn-action view" aria-label="View {{ $property->property_name }}"><i class="fas fa-eye"></i></a>@if($property->user_id === auth()->id())<span class="listing-owner-badge">Your listing</span><button type="button" class="btn-action edit" title="Edit your listing"><i class="fas fa-pen"></i></button>@elseif($property->user?->normalizedRole() === 'admin' && $panelId === 'open-listings')<form method="POST" action="{{ route('staff.properties.request', $property) }}">@csrf<button type="submit" class="btn-request-listing">Request to sell</button></form>@endif</div></div>
					</div>
				</div>
			@empty
				<div class="empty-state"><p>{{ $panelId === 'open-listings' ? 'No open listings available.' : 'No listings in this tab yet.' }}</p></div>
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
