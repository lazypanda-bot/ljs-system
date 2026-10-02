@extends('layouts.client')

@section('title', $pageTitle ?? 'Listings')

@section('content')
	<div class="listings-management-container staff-page-surface">
		<div class="listings-header staff-page-heading">
			<div>
				<h1>{{ $acting === 'broker' ? 'Team Listings' : 'My Listings' }}</h1>
				<p>{{ $acting === 'broker' ? 'Manage listings from your team and your own portfolio.' : 'Manage the properties you have submitted.' }}</p>
			</div>
			<a href="{{ route('admin.subfiles.addproperty') }}" class="btn-add-property"><i class="fas fa-plus" aria-hidden="true"></i> Add Property</a>
		</div>
		<div class="staff-listing-tabs" role="tablist">
			@if ($acting === 'broker')
				<button class="staff-listing-tab active" type="button" data-listing-panel="my-listings">My Listings</button>
				<button class="staff-listing-tab" type="button" data-listing-panel="team-listings">Team Listings</button>
			@else
				<button class="staff-listing-tab active" type="button" data-listing-panel="scoped-listings">{{ $acting === 'agent' ? 'My Listings' : 'All Listings' }}</button>
			@endif
		</div>

		@php
			$listingPanels = $acting === 'broker'
				? ['my-listings' => $myProperties, 'team-listings' => $teamProperties]
				: ['scoped-listings' => $properties];
		@endphp
		@foreach ($listingPanels as $panelId => $listingCollection)
		<div class="property-cards-grid staff-listing-panel {{ $loop->first ? 'active' : '' }}" id="{{ $panelId }}" @if(!$loop->first) hidden @endif>
			@forelse ($listingCollection as $property)
				@php
					$firstImage = $property->images->first();
					$imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
					$status = $property->property_status ?? 'Available';
					$referralCode = $property->referralCodes->firstWhere('user_id', auth()->id());
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
						@if ($property->approval_status === 'Pending')
							<p class="listing-review-status">Awaiting Admin approval</p>
						@elseif ($property->approval_status === 'Rejected')
							<div class="listing-rejection-note"><strong>Returned for changes</strong><p>{{ $property->rejection_note }}</p></div>
						@endif
						<div class="card-footer-actions"><div class="action-buttons"><a href="{{ route('staff.properties.show', $property) }}" title="View" class="btn-action view" aria-label="View {{ $property->property_name }}"><i class="fas fa-eye"></i></a>@if($property->user_id === auth()->id())<span class="listing-owner-badge">Your listing</span>@if($property->approval_status === 'Approved' && $referralCode)<span class="listing-referral-code">Referral: {{ $referralCode->code }}</span>@endif<a href="{{ route('admin.properties.edit', $property) }}" class="btn-action edit" title="Edit and resubmit your listing" aria-label="Edit {{ $property->property_name }}"><i class="fas fa-pen"></i></a>@endif</div></div>
					</div>
				</div>
			@empty
				<div class="empty-state"><p>No listings in this tab yet.</p></div>
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
