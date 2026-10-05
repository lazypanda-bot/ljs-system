@extends('layouts.client')
@section('title', 'Reports')
@section('content')
<div class="live-report-page staff-page-surface report-page">
	<div class="live-page-header"><div><span>Performance</span><h1>Reports</h1><p>Track inquiries, appointments, completions, and sales.</p></div></div>
	<form class="report-filters" method="GET" action="{{ url()->current() }}">
		<label>From<input type="date" name="from" value="{{ $reportFrom }}" required></label>
		<label>To<input type="date" name="to" value="{{ $reportTo }}" required></label>
		@if ($staff->count() > 1)
			<label>Listed by<select name="user_id"><option value="">{{ $overview ? 'All users' : 'All visible staff' }}</option>@foreach ($staff as $staffUser)<option value="{{ $staffUser->user_id }}" @selected($selectedOwnerId === $staffUser->user_id)>{{ $staffUser->name }} ({{ $staffUser->role_name }})</option>@endforeach</select></label>
		@endif
		<button type="submit" class="report-filter-button"><i class="fas fa-filter" aria-hidden="true"></i> Apply</button>
	</form>
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
	<div class="report-split">
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
		<div class="report-section-heading"><div><h2 id="reportStaffHeading">By listing owner</h2><p>Only staff associated with properties visible to your role are included.</p></div></div>
		<div class="report-table-wrap"><table class="report-staff-table"><thead><tr><th>Staff member</th><th>Listings</th><th>Inquiries</th><th>Appointments</th><th>Completed</th><th>Sold</th></tr></thead><tbody>
			@forelse ($perUser as $row)
				<tr data-staff-row="{{ $row['staffUser']->user_id }}"><th scope="row">{{ $row['staffUser']->name }}<small>{{ $row['staffUser']->role_name }}</small></th><td>{{ $row['propertiesCount'] }}</td><td>{{ $row['inquiriesCount'] }}</td><td>{{ $row['appointmentsCount'] }}</td><td>{{ $row['completedCount'] }}</td><td>{{ $row['soldCount'] }}</td></tr>
			@empty
				<tr><td colspan="6" class="report-empty">No listing activity for this selection.</td></tr>
			@endforelse
		</tbody></table></div>
		{{ $perUser->links('pagination.ljs') }}
	</section>
	</div>
	<a class="report-export-btn" href="{{ route('reports.export', request()->query()) }}"><i class="fas fa-file-csv" aria-hidden="true"></i> Export CSV</a>
</div>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/staff-pages.css') }}?v={{ filemtime(public_path('css/staff-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v={{ filemtime(public_path('css/reports.css')) }}">
<link rel="stylesheet" href="{{ asset('css/report-chart.css') }}?v={{ filemtime(public_path('css/report-chart.css')) }}">
@endpush