@extends('layouts.client')
@section('title', 'Reports')
@section('content')
<div class="live-report-page staff-page-surface report-page">
	<div class="live-page-header"><div><span>Performance</span><h1>Reports</h1><p>{{ $reportMode }} for inquiries, appointments, completions, and sales.</p></div></div>
	<div class="report-toolbar">
		<form class="report-filters" method="GET" action="{{ url()->current() }}">
			<label>From<input type="date" name="from" value="{{ $reportFrom }}" required></label>
			<label>To<input type="date" name="to" value="{{ $reportTo }}" required></label>
			@if ($staff->count() > 1)
				<label>Professional<select name="user_id"><option value="">{{ $isAdminView ? 'All staff' : 'All visible professionals' }}</option>@foreach ($staff as $staffUser)<option value="{{ $staffUser->user_id }}" @selected($selectedOwnerId === $staffUser->user_id)>{{ $staffUser->name }} ({{ $staffUser->role_name }})</option>@endforeach</select></label>
			@endif
			<button type="submit" class="report-filter-button"><i class="fas fa-filter" aria-hidden="true"></i> Apply</button>
		</form>
		<div class="report-export-actions">
			<a class="report-export-btn" href="{{ route('reports.export', array_merge(request()->query(), ['type' => 'summary'])) }}"><i class="fas fa-file-csv" aria-hidden="true"></i> Export Summary</a>
			<a class="report-export-btn secondary" href="{{ route('reports.export', array_merge(request()->query(), ['type' => 'performance'])) }}"><i class="fas fa-chart-line" aria-hidden="true"></i> Export Performance</a>
		</div>
	</div>
	<div class="report-stat-grid">
		<div><strong data-report-value="properties">{{ $properties->count() }}</strong><span>Properties listed</span></div>
		<div><strong data-report-value="inquiries">{{ $inquiries }}</strong><span>Property inquiries</span></div>
		<div><strong data-report-value="appointments">{{ $appointments }}</strong><span>Appointments requested</span></div>
		<div><strong data-report-value="completed">{{ $completed }}</strong><span>Appointments completed</span></div>
		<div><strong data-report-value="sold">{{ $sold }}</strong><span>Properties sold</span></div>
	</div>
	@if ($overview)
		<section class="report-section" aria-labelledby="reportOverviewHeading">
			<div class="report-section-heading"><div><h2 id="reportOverviewHeading">Overall system report</h2><p>All users and activity across the platform for the selected dates.</p></div></div>
			<div class="report-stat-grid">
				@foreach (['Admin' => 'Admins', 'Lead Broker' => 'Lead brokers', 'Broker' => 'Brokers', 'Agent' => 'Agents', 'Client' => 'Clients'] as $role => $label)
					<div><strong>{{ $overview['roles'][$role] ?? 0 }}</strong><span>{{ $label }}</span></div>
				@endforeach
				<div><strong>{{ $overview['newUsers'] }}</strong><span>New users in range</span></div>
				<div><strong>{{ $overview['messages'] }}</strong><span>Messages sent</span></div>
				<div><strong>{{ $overview['reviews'] }}</strong><span>Reviews ({{ $overview['averageRating'] ?: 'n/a' }} avg)</span></div>
			</div>
		</section>
	@endif
	<section class="report-section report-rate-panel" aria-labelledby="reportRatesHeading">
		<div class="report-section-heading"><div><h2 id="reportRatesHeading">Conversion rates</h2><p>Key rates for the selected range.</p></div></div>
		<div class="report-rate-grid">
			<div><span>Inquiry to appointment</span><strong>{{ $rates['inquiryToAppointment'] }}%</strong><small>Shows how many inquiries became appointment requests. Example: 10 inquiries, 4 appointments = 40%.</small></div>
			<div><span>Completion rate</span><strong>{{ $rates['completion'] }}%</strong><small>Shows how many requested appointments were completed. Example: 5 appointments, 3 completed = 60%.</small></div>
			<div><span>Sales conversion</span><strong>{{ $rates['salesConversion'] }}%</strong><small>Shows how many listed properties became sold. Example: 10 properties listed, 2 sold = 20%.</small></div>
		</div>
	</section>
	<section class="report-section report-trend-section" aria-labelledby="reportTrendHeading">
		<div class="report-section-heading"><div><h2 id="reportTrendHeading">Activity trend</h2><p>Daily activity for the selected day.</p></div>
			<form class="report-trend-filter" method="GET" action="{{ url()->current() }}">
				<input type="hidden" name="from" value="{{ $reportFrom }}"><input type="hidden" name="to" value="{{ $reportTo }}">
				@if ($selectedOwnerId)<input type="hidden" name="user_id" value="{{ $selectedOwnerId }}">@endif
				<label for="trendDate">Day</label><input id="trendDate" type="date" name="trend_date" value="{{ $trendDateValue }}" max="{{ today()->toDateString() }}" required>
				<button type="submit" class="report-filter-button"><i class="fas fa-magnifying-glass" aria-hidden="true"></i> Show day</button>
			</form>
		</div>
		<div class="report-trend-list">
			@foreach ($trend as $bucket)
				<div class="report-trend-row"><time>{{ $bucket['label'] }}</time><div class="report-trend-bars" aria-label="{{ $bucket['inquiries'] }} inquiries, {{ $bucket['appointments'] }} appointments, {{ $bucket['completed'] }} completed, {{ $bucket['sold'] }} sold">
					@foreach (['inquiries' => 'Inquiries', 'appointments' => 'Appointments', 'completed' => 'Completed', 'sold' => 'Sold'] as $key => $label)
						<span class="trend-bar trend-{{ $key }}" title="{{ $label }}: {{ $bucket[$key] }}" style="--bar-size: {{ max(3, ($bucket[$key] / $trendMax) * 100) }}%"></span>
					@endforeach
				</div><span class="report-trend-total">{{ array_sum(array_slice($bucket, 1)) }}</span></div>
			@endforeach
		</div>
		<div class="report-trend-legend"><span class="trend-inquiries">Inquiries</span><span class="trend-appointments">Appointments</span><span class="trend-completed">Completed</span><span class="trend-sold">Sold</span></div>
	</section>
	<section class="report-section report-owner-section" aria-labelledby="reportStaffHeading">
		<div class="report-section-heading"><div><h2 id="reportStaffHeading">Professional performance</h2><p>Includes staff visible to the current role or Lead Broker toggle.</p></div></div>
		<div class="report-table-wrap"><table class="report-staff-table"><thead><tr><th>Professional</th><th>Listings</th><th>Inquiries</th><th>Appointments</th><th>Completed</th><th>Cancelled</th><th>Sold</th><th>Completion</th><th>Sales</th><th>Rating</th><th>Team share</th></tr></thead><tbody>
			@forelse ($perUser as $row)
				<tr data-staff-row="{{ $row['staffUser']->user_id }}">
					<th scope="row">{{ $row['staffUser']->name }}<small>{{ $row['staffUser']->role_name }}</small></th>
					<td>{{ $row['propertiesCount'] }}<small>{{ $row['activeListingsCount'] }} active</small></td>
					<td>{{ $row['inquiriesCount'] }}</td>
					<td>{{ $row['appointmentsCount'] }}</td>
					<td>{{ $row['completedCount'] }}</td>
					<td>{{ $row['cancelledCount'] }}</td>
					<td>{{ $row['soldCount'] }}</td>
					<td>{{ $row['completionRate'] }}%</td>
					<td>{{ $row['salesConversionRate'] }}%</td>
					<td>{{ $row['averageRating'] ?: 'n/a' }}<small>{{ $row['reviewsCount'] }} reviews</small></td>
					<td><small>{{ $row['listingShare'] }}% listings</small><small>{{ $row['salesShare'] }}% sales</small></td>
				</tr>
			@empty
				<tr><td colspan="11" class="report-empty">No performance activity for this selection.</td></tr>
			@endforelse
		</tbody></table></div>
		{{ $perUser->links('pagination.ljs') }}
	</section>
	<section class="report-section report-top-listings" aria-labelledby="reportTopListingsHeading">
		<div class="report-section-heading"><div><h2 id="reportTopListingsHeading">Top listings</h2><p>Ranked by inquiries, appointments, completions, and sold status in the selected range.</p></div></div>
		<div class="report-table-wrap"><table class="report-staff-table"><thead><tr><th>Property</th><th>Owner</th><th>Status</th><th>Inquiries</th><th>Appointments</th><th>Completed</th></tr></thead><tbody>
			@forelse ($topListings as $row)
				<tr>
					<th scope="row">{{ $row['property']->property_name }}<small>ID: P{{ str_pad($row['property']->property_id, 3, '0', STR_PAD_LEFT) }}</small></th>
					<td>{{ $row['property']->user?->name ?? 'Unassigned' }}<small>{{ $row['property']->user?->role_name }}</small></td>
					<td>{{ $row['property']->property_status }}</td>
					<td>{{ $row['inquiriesCount'] }}</td>
					<td>{{ $row['appointmentsCount'] }}</td>
					<td>{{ $row['completedCount'] }}</td>
				</tr>
			@empty
				<tr><td colspan="6" class="report-empty">No listing activity for this selection.</td></tr>
			@endforelse
		</tbody></table></div>
	</section>
</div>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/staff-pages.css') }}?v={{ filemtime(public_path('css/staff-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v={{ filemtime(public_path('css/reports.css')) }}">
<link rel="stylesheet" href="{{ asset('css/report-chart.css') }}?v={{ filemtime(public_path('css/report-chart.css')) }}">
@endpush
