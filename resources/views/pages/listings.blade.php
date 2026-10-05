@extends('layouts.client')

@section('title', $pageTitle ?? 'Listings')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
@endpush

@section('content')
	<div class="listings-management-container staff-page-surface">
		<div class="listings-header staff-page-heading">
			<div>
				<h1>My Listings</h1>
				<p>Properties you have listed. Edits are resubmitted for Admin approval.</p>
			</div>
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
							@endphp
							<tr>
								<td class="staff-property-image-cell"><img src="{{ $imageUrl }}" alt="{{ $property->property_name }}"></td>
								<td class="staff-property-name-cell"><strong>{{ $property->property_name }}</strong><small>ID: P{{ str_pad($property->property_id, 3, '0', STR_PAD_LEFT) }}</small></td>
								<td>{{ $property->property_type }}</td>
								<td class="staff-property-price-cell">PHP {{ number_format($property->price, 0, '.', ',') }}</td>
								<td>{{ $property->property_status ?? 'Available' }}</td>
								<td>
									<span class="staff-status-pill status-{{ strtolower($property->approval_status) }}">{{ $property->approval_status }}</span>
									@if ($property->approval_status === 'Rejected' && $property->rejection_note)
										<small class="d-block text-muted">{{ $property->rejection_note }}</small>
									@endif
								</td>
								<td>{{ $property->approval_status === 'Approved' && $referralCode ? $referralCode->code : '—' }}</td>
								<td class="text-end">
									<div class="au-action-group">
										<a href="{{ route('staff.properties.show', $property) }}" class="btn-action view" title="View" aria-label="View {{ $property->property_name }}"><i class="fas fa-eye"></i></a>
										<a href="{{ route('admin.properties.edit', $property) }}" class="btn-action edit" title="Edit and resubmit" aria-label="Edit {{ $property->property_name }}"><i class="fas fa-pen"></i></a>
									</div>
								</td>
							</tr>
						@empty
							<tr><td colspan="8" class="staff-table-empty">You have not listed any properties yet.</td></tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection
