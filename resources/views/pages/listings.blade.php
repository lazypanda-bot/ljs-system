@extends('layouts.client')

@section('title', $pageTitle ?? 'Listings')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
@endpush

@section('content')
	@php
		$listingGroups = [
			'active' => ['label' => 'Active', 'description' => 'For sale, for rent, and reserved listings.'],
			'needs_action' => ['label' => 'Pending / Rejected', 'description' => 'Listings waiting for Admin review or returned for edits.'],
			'history' => ['label' => 'History', 'description' => 'Sold, unavailable, or duplicate-rejected listings.'],
		];
		$groupCounts = collect(array_keys($listingGroups))->mapWithKeys(fn ($group) => [$group => $myProperties->filter(fn ($property) => $property->listingGroup() === $group)->count()]);
	@endphp
	<div class="listings-management-container staff-page-surface">
		<div class="listings-header staff-page-heading">
			<div>
				<h1>My Listings</h1>
				<p>Properties you have listed. Rejected listings can be edited and resubmitted for Admin approval.</p>
			</div>
		</div>

		<div class="listing-group-tabs" role="tablist" aria-label="Listing groups">
			@foreach ($listingGroups as $group => $meta)
				<button type="button" class="listing-group-tab {{ $loop->first ? 'active' : '' }}" data-listing-group-filter="{{ $group }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
					<span>{{ $meta['label'] }}</span>
					<strong>{{ $groupCounts[$group] }}</strong>
				</button>
			@endforeach
		</div>

		<div class="staff-filter-bar listings-filter-bar" data-listing-filters>
			<label class="search-box" for="listingSearch">
				<i class="fas fa-search search-icon" aria-hidden="true"></i>
				<input id="listingSearch" type="search" placeholder="Search listing, type, location, or ID">
			</label>
			<select id="listingStatusFilter" aria-label="Filter listings by approval status">
				<option value="">All approval status</option>
				<option value="Approved">Approved</option>
				<option value="Pending">Pending</option>
				<option value="Rejected">Rejected</option>
			</select>
			<a href="{{ route('admin.subfiles.addproperty') }}" class="btn-add-property"><i class="fas fa-plus" aria-hidden="true"></i> Add Property</a>
		</div>

		<div class="au-table-container">
			<div class="staff-property-table-wrap">
				<table class="staff-property-table">
					<thead>
						<tr>
							<th>PHOTO</th>
							<th>PROPERTY</th>
							<th>TYPE</th>
							<th>PRICE</th>
							<th>STATUS</th>
							<th>APPROVAL</th>
							<th>REFERRAL</th>
							<th class="text-end">ACTIONS</th>
						</tr>
					</thead>
					<tbody>
						@forelse ($myProperties as $property)
							@php
								$firstImage = $property->images->first();
								$imageUrl = $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/ljs-logo.jpg');
								$referralCode = $property->referralCodes->firstWhere('user_id', auth()->id());
								$blocksResubmission = $property->blocksResubmission();
								$isSoldListing = $property->property_status === 'Sold';
								$listingGroup = $property->listingGroup();
								$listingSearchText = strtolower(implode(' ', [
									$property->property_name,
									'P' . str_pad($property->property_id, 3, '0', STR_PAD_LEFT),
									$property->property_type,
									$property->property_status,
									$property->approval_status,
									$property->city,
									$property->province,
									$property->rejection_note,
								]));
							@endphp
							<tr data-listing-row data-listing-id="{{ $property->property_id }}" data-listing-group="{{ $listingGroup }}" data-listing-status="{{ $property->approval_status }}" data-listing-text="{{ $listingSearchText }}">
								<td class="staff-property-image-cell"><img src="{{ $imageUrl }}" alt="{{ $property->property_name }}"></td>
								<td class="staff-property-name-cell"><strong>{{ $property->property_name }}</strong><small>ID: P{{ str_pad($property->property_id, 3, '0', STR_PAD_LEFT) }}</small></td>
								<td>{{ $property->property_type }}</td>
								<td class="staff-property-price-cell">PHP {{ number_format($property->price, 0, '.', ',') }}</td>
								<td>
									@if ($isSoldListing || $blocksResubmission)
										{{ $property->property_status ?? 'Available' }}
									@else
										<form method="POST" action="{{ route('admin.properties.status', $property) }}" class="staff-property-status-form">
											@csrf
											@method('PATCH')
											<select class="status-select" name="property_status" data-auto-submit aria-label="Property status for {{ $property->property_name }}">
												@foreach (array_unique(array_merge(['For Sale', 'For Rent', 'Rent to Own', 'Sold', 'Reserved', 'Unavailable'], [(string) ($property->property_status ?? 'Available')])) as $statusOption)
													<option value="{{ $statusOption }}" @selected(($property->property_status ?? 'Available') === $statusOption)>{{ $statusOption }}</option>
												@endforeach
											</select>
										</form>
									@endif
								</td>
								<td>
									<span class="staff-status-pill status-{{ strtolower($property->approval_status) }}">{{ $property->approval_status }}</span>
								</td>
								<td class="staff-property-referral-cell">
									@if (! $isSoldListing && $property->approval_status === 'Approved' && $referralCode)
										<code>{{ $referralCode->code }}</code>
										<button type="button" class="staff-referral-copy" data-copy-referral="{{ route('referrals.open', $referralCode->code) }}"><i class="fas fa-copy" aria-hidden="true"></i> Copy link</button>
									@elseif (! $isSoldListing && $property->approval_status === 'Approved')
										<form method="POST" action="{{ route('agent.properties.generate-code', $property->property_id) }}">
											@csrf
											<button type="submit" class="staff-referral-copy"><i class="fas fa-link" aria-hidden="true"></i> Generate link</button>
										</form>
									@else
										<span class="staff-unassigned">Not available</span>
									@endif
								</td>
								<td class="text-end">
									<div class="au-action-group">
										<a href="{{ route('staff.properties.show', $property) }}" class="btn-action view" title="View" aria-label="View {{ $property->property_name }}"><i class="fas fa-eye"></i></a>
										@if ($isSoldListing)
										@elseif ($blocksResubmission)
											<span class="btn-action edit is-disabled" title="Duplicate listing cannot be resubmitted" aria-label="Duplicate listing cannot be resubmitted"><i class="fas fa-ban"></i></span>
										@else
											<a href="{{ route('admin.properties.edit', $property) }}" class="btn-action edit" title="{{ $property->approval_status === 'Rejected' ? 'Edit and resubmit' : 'Edit' }}" aria-label="Edit {{ $property->property_name }}"><i class="fas fa-pen"></i></a>
										@endif
									</div>
								</td>
							</tr>
							@if ($property->approval_status === 'Rejected' && $property->rejection_note)
								<tr class="listing-rejection-row" data-listing-reason-row="{{ $property->property_id }}">
									<td colspan="8">
										<div class="listing-rejection-summary">
											<strong>Reason for rejection</strong>
											<p>{{ $property->rejection_note }}</p>
										</div>
									</td>
								</tr>
							@endif
						@empty
							<tr><td colspan="8" class="staff-table-empty">You have not listed any properties yet.</td></tr>
						@endforelse
						<tr data-listing-empty hidden><td colspan="8" class="staff-table-empty">No listings match your filters.</td></tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const searchInput = document.getElementById('listingSearch');
			const statusFilter = document.getElementById('listingStatusFilter');
			const groupButtons = Array.from(document.querySelectorAll('[data-listing-group-filter]'));
			const rows = Array.from(document.querySelectorAll('[data-listing-row]'));
			const emptyRow = document.querySelector('[data-listing-empty]');
			let activeGroup = groupButtons.find((button) => button.classList.contains('active'))?.dataset.listingGroupFilter || 'active';

			const applyListingFilters = () => {
				const term = (searchInput?.value || '').trim().toLowerCase();
				const status = statusFilter?.value || '';
				let visibleCount = 0;

				rows.forEach((row) => {
					const matchesGroup = !activeGroup || row.dataset.listingGroup === activeGroup;
					const matchesSearch = !term || (row.dataset.listingText || '').includes(term);
					const matchesStatus = !status || row.dataset.listingStatus === status;
					const isVisible = matchesGroup && matchesSearch && matchesStatus;
					const reasonRow = document.querySelector(`[data-listing-reason-row="${row.dataset.listingId}"]`);

					row.hidden = !isVisible;
					if (reasonRow) reasonRow.hidden = !isVisible;
					if (isVisible) visibleCount += 1;
				});

				if (emptyRow) emptyRow.hidden = visibleCount !== 0;
			};

			searchInput?.addEventListener('input', applyListingFilters);
			statusFilter?.addEventListener('change', applyListingFilters);
			groupButtons.forEach((button) => {
				button.addEventListener('click', () => {
					activeGroup = button.dataset.listingGroupFilter || 'active';
					groupButtons.forEach((item) => {
						const active = item === button;
						item.classList.toggle('active', active);
						item.setAttribute('aria-selected', active ? 'true' : 'false');
					});
					applyListingFilters();
				});
			});
			applyListingFilters();

			document.querySelectorAll('[data-copy-referral]').forEach((button) => {
				button.addEventListener('click', async () => {
					const referralUrl = button.dataset.copyReferral;
					try {
						await navigator.clipboard.writeText(referralUrl);
						button.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Copied';
					} catch {
						window.prompt('Copy this referral link', referralUrl);
					}
				});
			});
		});
	</script>
@endpush
