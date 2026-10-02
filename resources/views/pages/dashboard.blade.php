@extends('layouts.client')

@section('content')
    <div class="dashboard-content">
        @php
            $propertyCount = $totalProperties ?? 0;
            $brokerCount = $totalBrokers ?? 0;
            $agentCount = $totalAgents ?? 0;
            
            $forSaleCount = $forSaleProperties ?? 0;
            $pendingCount = $pendingProperties ?? 0;
            $soldCount = $soldProperties ?? 0;
            $inactiveCount = $inactiveProperties ?? 0;
            $totalPropsForChart = $forSaleCount + $pendingCount + $soldCount + $inactiveCount;

            $forSalePct = $totalPropsForChart > 0 ? round(($forSaleCount / $totalPropsForChart) * 100) : 0;
            $pendingPct = $totalPropsForChart > 0 ? round(($pendingCount / $totalPropsForChart) * 100) : 0;
            $soldPct = $totalPropsForChart > 0 ? round(($soldCount / $totalPropsForChart) * 100) : 0;
            $inactivePct = 100 - ($forSalePct + $pendingPct + $soldPct);

            $currMonth = request('month', now()->month);
            $currYear = request('year', now()->year);
            
            $calendarTimezone = auth()->user()?->timezone ?? config('appointments.default_timezone');
            $startOfMonth = \Carbon\Carbon::createFromDate($currYear, $currMonth, 1, $calendarTimezone);
            $daysInMonth = $startOfMonth->daysInMonth;
            $firstDayOfWeek = $startOfMonth->dayOfWeek;
            $currentMonthName = $startOfMonth->format('F Y');

            $prevDate = (clone $startOfMonth)->subMonth();
            $nextDate = (clone $startOfMonth)->addMonth();
            $now = now();
        @endphp

        <div class="dashboard-grid">
            <div class="main-content">
                <div class="stats-section">
                    <div class="stat-card stat-properties">
                        <div class="stat-header">
                            <span class="stat-label">Total Properties</span>
                            <a href="{{ route('app.page', ['page' => 'property']) }}" class="stat-link" aria-label="View all properties"><i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-value">{{ $propertyCount }}</h3>
                        </div>
                        <div class="stat-graphic">
                            <i class="fas fa-home"></i>
                        </div>
                    </div>

                    @if(($dashboardMode ?? 'org') === 'personal')
                        <div class="stat-card stat-brokers">
                            <div class="stat-header">
                                <span class="stat-label">For Sale</span>
                            </div>
                            <div class="stat-body">
                                <h3 class="stat-value">{{ $forSaleCount }}</h3>
                            </div>
                            <div class="stat-graphic">
                                <i class="fas fa-tag"></i>
                            </div>
                        </div>

                        <div class="stat-card stat-agents">
                            <div class="stat-header">
                                <span class="stat-label">Sold</span>
                            </div>
                            <div class="stat-body">
                                <h3 class="stat-value">{{ $soldCount }}</h3>
                            </div>
                            <div class="stat-graphic">
                                <i class="fas fa-circle-check"></i>
                            </div>
                        </div>
                    @else
                        <div class="stat-card stat-brokers">
                            <div class="stat-header">
                                <span class="stat-label">Total Brokers</span>
                                <a href="{{ route('app.page', ['page' => 'users']) }}" class="stat-link" aria-label="View all brokers"><i class="fas fa-arrow-right"></i></a>
                            </div>
                            <div class="stat-body">
                                <h3 class="stat-value">{{ $brokerCount }}</h3>
                            </div>
                            <div class="stat-graphic">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        </div>

                        <div class="stat-card stat-agents">
                            <div class="stat-header">
                                <span class="stat-label">Total Agents</span>
                                <a href="{{ route('app.page', ['page' => 'users']) }}" class="stat-link" aria-label="View all agents"><i class="fas fa-arrow-right"></i></a>
                            </div>
                            <div class="stat-body">
                                <h3 class="stat-value">{{ $agentCount }}</h3>
                            </div>
                            <div class="stat-graphic">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="card appointments-card">
                    <div class="card-header">
                        <h2>Appointments Calendar</h2>
                        <div class="calendar-controls">
                            @if (in_array(auth()->user()?->actingRole(), ['admin', 'broker', 'agent', 'lead_broker'], true))
                                <button class="calendar-btn block-calendar-btn" type="button" id="openBlockDateModal"><i class="fas fa-calendar-minus" aria-hidden="true"></i> Block Date</button>
                            @endif
                            <a href="{{ url()->current() }}?month={{ $prevDate->month }}&year={{ $prevDate->year }}" class="calendar-btn nav-btn" aria-label="Previous month"><i class="fas fa-chevron-left"></i></a>
                            <a href="{{ url()->current() }}?month={{ $nextDate->month }}&year={{ $nextDate->year }}" class="calendar-btn nav-btn" aria-label="Next month"><i class="fas fa-chevron-right"></i></a>
                            <div class="calendar-month-display">{{ $currentMonthName }}</div>
                        </div>
                    </div>

                    <!-- Weekday Headers -->
                    <div class="calendar-days">
                        <span>Sun</span>
                        <span>Mon</span>
                        <span>Tue</span>
                        <span>Wed</span>
                        <span>Thu</span>
                        <span>Fri</span>
                        <span>Sat</span>
                    </div>

                    <!-- Calendar Days Grid -->
                    <div class="calendar-dates" id="calendarDates" data-appointments='@json($calendarAppointments ?? [])' data-blocked-dates='@json($calendarBlockedDates ?? [])'>
                        @for ($i = 0; $i < $firstDayOfWeek; $i++)
                            <div class="calendar-cell other-month"></div>
                        @endfor

                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            @php
                                $isToday = $day === now($calendarTimezone)->day && (int)$currMonth === now($calendarTimezone)->month && (int)$currYear === now($calendarTimezone)->year;
                            @endphp
                            <div class="calendar-cell {{ $isToday ? 'calendar-day-today' : '' }}">
                                <span class="calendar-day-number">{{ $day }}</span>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            <div class="dash-sidebar">
                <div class="card property-status-card">
                    <div class="card-header property-status-header">
                        <h3>Property Status Overview</h3>
                    </div>

                    <div class="property-status-controls">
                        <div class="status-month-label">{{ now()->translatedFormat('F Y') }}</div>
                    </div>

                    <div class="bar-chart-container">
                        <div class="bar-row">
                            <span class="bar-label">For Sale</span>
                            <div class="bar-track">
                                <div class="bar-fill bar-for-sale" data-width="{{ $forSalePct }}"></div>
                            </div>
                            <span class="bar-value">{{ $forSaleCount }} ({{ $forSalePct }}%)</span>
                        </div>

                        <div class="bar-row">
                            <span class="bar-label">Pending</span>
                            <div class="bar-track">
                                <div class="bar-fill bar-pending" data-width="{{ $pendingPct }}"></div>
                            </div>
                            <span class="bar-value">{{ $pendingCount }} ({{ $pendingPct }}%)</span>
                        </div>

                        <div class="bar-row">
                            <span class="bar-label">Sold</span>
                            <div class="bar-track">
                                <div class="bar-fill bar-sold" data-width="{{ $soldPct }}"></div>
                            </div>
                            <span class="bar-value">{{ $soldCount }} ({{ $soldPct }}%)</span>
                        </div>

                        <div class="bar-row">
                            <span class="bar-label">Inactive</span>
                            <div class="bar-track">
                                <div class="bar-fill bar-inactive" data-width="{{ $inactivePct }}"></div>
                            </div>
                            <span class="bar-value">{{ $inactiveCount }} ({{ $inactivePct }}%)</span>
                        </div>
                    </div>

                    <p class="total-properties">Total Properties: {{ $totalPropsForChart }}</p>
                </div>

                <div class="card activities-card">
                    <div class="card-header">
                        <h3>Recent System Activities</h3>
                        <a href="{{ route('notifications.index') }}" class="view-all-link">View all</a>
                    </div>

                    <div class="activities-list">
                        @forelse(($recentActivities ?? collect())->take(5) as $activity)
                            <div class="activity-item">
                                <div class="activity-icon {{ $activity->icon_class ?? 'new-user' }}">
                                    <i class="{{ $activity->icon ?? 'fas fa-user-plus' }}"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="activity-title">{{ $activity->title }}</p>
                                    <p class="activity-desc">{{ $activity->description }}</p>
                                </div>
                                <span class="activity-time">{{ $activity->created_at->diffForHumans() }}</span>
                            </div>
                        @empty
                            <div class="empty-activity-state">
                                <p>No activities yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="block-date-modal" id="blockDateModal" aria-hidden="true">
        <form method="POST" action="{{ route('appointments.block-date') }}" class="block-date-dialog">
            @csrf
            <input type="hidden" name="timezone" value="{{ auth()->user()?->timezone ?? config('appointments.default_timezone') }}">
            <div class="block-date-dialog-header"><h2>Block a date</h2><button type="button" id="closeBlockDateModal" aria-label="Close">&times;</button></div>
            <label>Date<input type="date" name="date" min="{{ now()->toDateString() }}" required></label>
            <label>Start time<input type="time" name="start_time"></label>
            <label>End time<input type="time" name="end_time"></label>
            <label>Reason<input type="text" name="reason" placeholder="Personal appointment"></label>
            <button type="submit">Block date</button>
        </form>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
@endpush
@push('scripts')
<script src="{{ asset('js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}" defer></script>
@endpush