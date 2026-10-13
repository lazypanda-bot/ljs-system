<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ConversationMessage;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\PropertyInquiry;
use App\Models\Review;
use App\Models\TeamMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();
        abort_if($user->actingRole() === 'client', 403);

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer'],
            'trend_date' => ['nullable', 'date'],
        ]);
        $to = CarbonImmutable::parse($filters['to'] ?? today()->toDateString())->endOfDay();
        $from = CarbonImmutable::parse($filters['from'] ?? $to->subDays(29)->toDateString())->startOfDay();

        $visibleProperties = $this->reportProperties($user);
        $visiblePropertyIds = (clone $visibleProperties)->pluck('property_id');
        $isAdminView = $user->actingRole() === 'admin';
        $reportMode = match ($user->actingRole()) {
            'admin' => 'Organization report',
            'broker' => 'Team report',
            default => 'Personal report',
        };
        $visibleProfessionalIds = $this->visibleProfessionalIds($user, $visiblePropertyIds);
        $selectedOwnerId = isset($filters['user_id']) && $visibleProfessionalIds->contains((int) $filters['user_id'])
            ? (int) $filters['user_id']
            : null;
        $staff = User::query()->whereIn('user_id', $visibleProfessionalIds)->orderBy('name')->get(['user_id', 'name', 'role_name']);
        $visiblePropertyScope = fn ($query) => $query->whereIn('property_id', $visiblePropertyIds);
        $propertyScope = fn ($query) => $query->whereIn('property_id', $visiblePropertyIds)
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId));
        $appointmentScope = fn ($query) => $query->whereHas('assignment.property', $visiblePropertyScope)
            ->when($selectedOwnerId, fn ($query) => $query->whereHas('assignment', fn ($assignment) => $assignment->where('user_id', $selectedOwnerId)));
        $inquiryScope = fn ($query) => $query->whereHas('property', $visiblePropertyScope)
            ->when($selectedOwnerId, fn ($query) => $query->where(fn ($scoped) => $scoped
                ->where('recipient_id', $selectedOwnerId)
                ->orWhereHas('property', fn ($property) => $property->where('user_id', $selectedOwnerId))));

        $properties = (clone $visibleProperties)->whereBetween('created_at', [$from, $to])
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId))
            ->with('user')->get();
        $inquiriesQuery = PropertyInquiry::query()->whereBetween('created_at', [$from, $to])
            ->where($inquiryScope);
        $appointmentsQuery = Appointment::query()->whereBetween('created_at', [$from, $to])
            ->where($appointmentScope);
        $completedQuery = Appointment::query()->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$from, $to])->where($appointmentScope);
        $cancelledQuery = Appointment::query()->where('appointment_status', 'Cancelled')
            ->whereBetween('updated_at', [$from, $to])->where($appointmentScope);
        $soldQuery = (clone $visibleProperties)->where('property_status', 'Sold')->whereBetween('sold_at', [$from, $to])
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId));

        $inquiries = $inquiriesQuery->count();
        $appointments = $appointmentsQuery->count();
        $completed = $completedQuery->count();
        $cancelled = $cancelledQuery->count();
        $sold = $soldQuery->count();
        $soldProperties = $soldQuery->with('user')->get();
        $rates = $this->summaryRates($properties->count(), $inquiries, $appointments, $completed, $sold);
        $trendDate = CarbonImmutable::parse($filters['trend_date'] ?? today()->toDateString());
        $trendFrom = $trendDate->startOfDay();
        $trendTo = $trendDate->endOfDay();
        $trend = [$trendDate->toDateString() => [
            'label' => $trendDate->format('M j, Y'),
            'inquiries' => (clone $inquiriesQuery)->whereBetween('created_at', [$trendFrom, $trendTo])->count(),
            'appointments' => (clone $appointmentsQuery)->whereBetween('created_at', [$trendFrom, $trendTo])->count(),
            'completed' => (clone $completedQuery)->whereBetween('completed_at', [$trendFrom, $trendTo])->count(),
            'sold' => (clone $soldQuery)->whereBetween('sold_at', [$trendFrom, $trendTo])->count(),
        ]];
        $trendMax = max(1, collect($trend)->flatMap(fn ($bucket) => array_values($bucket) )->filter(fn ($value) => is_int($value))->max() ?? 1);

        $reportStaff = $selectedOwnerId ? $staff->where('user_id', $selectedOwnerId)->values() : $staff;
        $performanceRows = $this->performanceRows($reportStaff, $visiblePropertyIds, $from, $to);
        $performanceTotals = [
            'propertiesCount' => $performanceRows->sum('propertiesCount'),
            'inquiriesCount' => $performanceRows->sum('inquiriesCount'),
            'appointmentsCount' => $performanceRows->sum('appointmentsCount'),
            'completedCount' => $performanceRows->sum('completedCount'),
            'soldCount' => $performanceRows->sum('soldCount'),
        ];
        $performanceRows = $performanceRows->map(function (array $row) use ($performanceTotals) {
            $row['listingShare'] = $this->percentage($row['propertiesCount'], $performanceTotals['propertiesCount']);
            $row['inquiryShare'] = $this->percentage($row['inquiriesCount'], $performanceTotals['inquiriesCount']);
            $row['appointmentShare'] = $this->percentage($row['appointmentsCount'], $performanceTotals['appointmentsCount']);
            $row['salesShare'] = $this->percentage($row['soldCount'], $performanceTotals['soldCount']);

            return $row;
        });

        $ownersPage = max(1, (int) request()->query('owners_page', 1));
        $perUser = new \Illuminate\Pagination\LengthAwarePaginator(
            $performanceRows->forPage($ownersPage, 5)->values(),
            $performanceRows->count(),
            5,
            $ownersPage,
            ['path' => request()->url(), 'pageName' => 'owners_page', 'query' => request()->except('owners_page')]
        );

        $reportFrom = $from->toDateString();
        $reportTo = $to->toDateString();
        $trendDateValue = $trendDate->toDateString();

        $overview = $isAdminView ? [
            'roles' => User::query()->selectRaw('role_name, count(*) as total')->groupBy('role_name')->pluck('total', 'role_name')->all(),
            'newUsers' => User::query()->whereBetween('created_at', [$from, $to])->count(),
            'reviews' => Review::query()->whereBetween('created_at', [$from, $to])->count(),
            'averageRating' => round((float) Review::query()->whereBetween('created_at', [$from, $to])->avg('rating'), 1),
            'messages' => ConversationMessage::query()->whereBetween('created_at', [$from, $to])->count(),
        ] : null;

        $topListings = $this->topListings($visiblePropertyIds, $from, $to, $selectedOwnerId);

        return view('pages.reports-live', compact(
            'properties', 'appointments', 'completed', 'cancelled', 'sold', 'soldProperties', 'inquiries', 'overview',
            'staff', 'perUser', 'selectedOwnerId', 'reportFrom', 'reportTo', 'trend', 'trendMax', 'trendDateValue',
            'reportMode', 'isAdminView', 'rates', 'topListings'
        ));
    }

    public function export(Request $request)
    {
        $user = $this->user();
        abort_if($user->actingRole() === 'client', 403);

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'in:summary,performance'],
        ]);
        $to = CarbonImmutable::parse($filters['to'] ?? today()->toDateString())->endOfDay();
        $from = CarbonImmutable::parse($filters['from'] ?? $to->subDays(29)->toDateString())->startOfDay();
        $visibleProperties = $this->reportProperties($user);
        $visiblePropertyIds = (clone $visibleProperties)->pluck('property_id');
        $visibleProfessionalIds = $this->visibleProfessionalIds($user, $visiblePropertyIds);
        $selectedOwnerId = isset($filters['user_id']) && $visibleProfessionalIds->contains((int) $filters['user_id'])
            ? (int) $filters['user_id']
            : null;
        $visiblePropertyScope = fn ($query) => $query->whereIn('property_id', $visiblePropertyIds);
        $propertyScope = fn ($query) => $query->whereIn('property_id', $visiblePropertyIds)
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId));
        $appointmentScope = fn ($query) => $query->whereHas('assignment.property', $visiblePropertyScope)
            ->when($selectedOwnerId, fn ($query) => $query->whereHas('assignment', fn ($assignment) => $assignment->where('user_id', $selectedOwnerId)));
        $inquiryScope = fn ($query) => $query->whereHas('property', $visiblePropertyScope)
            ->when($selectedOwnerId, fn ($query) => $query->where(fn ($scoped) => $scoped
                ->where('recipient_id', $selectedOwnerId)
                ->orWhereHas('property', fn ($property) => $property->where('user_id', $selectedOwnerId))));
        $properties = (clone $visibleProperties)->whereBetween('created_at', [$from, $to])
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId))
            ->with('user')->get(['property_id', 'user_id', 'property_name', 'property_status', 'price', 'created_at']);
        $inquiries = PropertyInquiry::query()->whereBetween('created_at', [$from, $to])->where($inquiryScope)->count();
        $appointments = Appointment::query()->whereBetween('created_at', [$from, $to])->where($appointmentScope)->count();
        $completed = Appointment::query()->whereNotNull('completed_at')->whereBetween('completed_at', [$from, $to])->where($appointmentScope)->count();
        $cancelled = Appointment::query()->where('appointment_status', 'Cancelled')->whereBetween('updated_at', [$from, $to])->where($appointmentScope)->count();
        $sold = (clone $visibleProperties)->where('property_status', 'Sold')->whereBetween('sold_at', [$from, $to])
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId))->count();
        $rates = $this->summaryRates($properties->count(), $inquiries, $appointments, $completed, $sold);
        $owners = User::query()->whereIn('user_id', $visibleProfessionalIds)
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId))->orderBy('name')->get();
        $performanceRows = $this->performanceRows($owners, $visiblePropertyIds, $from, $to);
        $performanceTotals = [
            'propertiesCount' => $performanceRows->sum('propertiesCount'),
            'inquiriesCount' => $performanceRows->sum('inquiriesCount'),
            'appointmentsCount' => $performanceRows->sum('appointmentsCount'),
            'soldCount' => $performanceRows->sum('soldCount'),
        ];
        $performanceRows = $performanceRows->map(function (array $row) use ($performanceTotals) {
            $row['listingShare'] = $this->percentage($row['propertiesCount'], $performanceTotals['propertiesCount']);
            $row['inquiryShare'] = $this->percentage($row['inquiriesCount'], $performanceTotals['inquiriesCount']);
            $row['appointmentShare'] = $this->percentage($row['appointmentsCount'], $performanceTotals['appointmentsCount']);
            $row['salesShare'] = $this->percentage($row['soldCount'], $performanceTotals['soldCount']);

            return $row;
        });

        $type = $filters['type'] ?? 'summary';
        $fileName = $type === 'performance' ? 'ljs-professional-performance.csv' : 'ljs-summary-report.csv';

        return response()->streamDownload(function () use ($type, $properties, $performanceRows, $from, $to, $inquiries, $appointments, $completed, $cancelled, $sold, $rates) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date range', $from->toDateString() . ' to ' . $to->toDateString()]);

            if ($type === 'performance') {
                fputcsv($handle, ['Professional', 'Role', 'Properties listed', 'Active listings', 'Inquiries', 'Appointments requested', 'Completed appointments', 'Cancelled appointments', 'Properties sold', 'Completion rate', 'Sales conversion rate', 'Average rating', 'Reviews', '% of listings', '% of inquiries', '% of appointments', '% of sales']);
                foreach ($performanceRows as $row) {
                    $staffUser = $row['staffUser'];
                    fputcsv($handle, [
                        $staffUser->name,
                        $staffUser->role_name,
                        $row['propertiesCount'],
                        $row['activeListingsCount'],
                        $row['inquiriesCount'],
                        $row['appointmentsCount'],
                        $row['completedCount'],
                        $row['cancelledCount'],
                        $row['soldCount'],
                        $row['completionRate'] . '%',
                        $row['salesConversionRate'] . '%',
                        $row['averageRating'] ?: 'n/a',
                        $row['reviewsCount'],
                        $row['listingShare'] . '%',
                        $row['inquiryShare'] . '%',
                        $row['appointmentShare'] . '%',
                        $row['salesShare'] . '%',
                    ]);
                }
                fclose($handle);

                return;
            }

            fputcsv($handle, ['Metric', 'Count']);
            fputcsv($handle, ['Properties listed', $properties->count()]);
            fputcsv($handle, ['Property inquiries', $inquiries]);
            fputcsv($handle, ['Appointments requested', $appointments]);
            fputcsv($handle, ['Appointments completed', $completed]);
            fputcsv($handle, ['Appointments cancelled', $cancelled]);
            fputcsv($handle, ['Properties sold', $sold]);
            fputcsv($handle, ['Inquiry to appointment rate', $rates['inquiryToAppointment'] . '%']);
            fputcsv($handle, ['Completion rate', $rates['completion'] . '%']);
            fputcsv($handle, ['Sales conversion rate', $rates['salesConversion'] . '%']);
            fputcsv($handle, []);
            fputcsv($handle, ['Professional', 'Role', 'Properties listed', 'Inquiries', 'Appointments', 'Completed', 'Sold', 'Completion rate', 'Sales conversion rate']);
            foreach ($performanceRows as $row) {
                $staffUser = $row['staffUser'];
                fputcsv($handle, [
                    $staffUser->name,
                    $staffUser->role_name,
                    $row['propertiesCount'],
                    $row['inquiriesCount'],
                    $row['appointmentsCount'],
                    $row['completedCount'],
                    $row['soldCount'],
                    $row['completionRate'] . '%',
                    $row['salesConversionRate'] . '%',
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Property ID', 'Property', 'Status', 'Price', 'Seller/Professional', 'Role', 'Created']);
            foreach ($properties as $property) {
                fputcsv($handle, [$property->property_id, $property->property_name, $property->property_status, $property->price, $property->user?->name, $property->user?->role_name, $property->created_at?->toDateString()]);
            }
            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /** Admins report on everything; professionals report on their visible professional scope. */
    private function reportProperties(User $user)
    {
        return $user->actingRole() === 'admin'
            ? Property::query()->reportable()
            : Property::query()->reportable()->visibleTo($user);
    }

    private function visibleProfessionalIds(User $user, $visiblePropertyIds)
    {
        if ($user->actingRole() === 'admin') {
            return User::query()->where('role_name', '!=', 'Client')->pluck('user_id');
        }

        $ids = collect([(int) $user->user_id])
            ->merge(Property::query()->whereIn('property_id', $visiblePropertyIds)->whereNotNull('user_id')->pluck('user_id'))
            ->merge(PropertyAssign::query()->whereIn('property_id', $visiblePropertyIds)->where('status', 'Approved')->pluck('user_id'));

        if ($user->actingRole() === 'broker') {
            $teamIds = $user->teams()->pluck('team_id');
            $ids = $ids->merge(TeamMember::query()->whereIn('team_id', $teamIds)->where('status', 'Approved')->pluck('user_id'));
        }

        return $ids->filter()->unique()->values();
    }

    private function performanceRows($staff, $visiblePropertyIds, CarbonImmutable $from, CarbonImmutable $to)
    {
        return $staff->map(function (User $staffUser) use ($from, $to, $visiblePropertyIds) {
            $ownedPropertyIds = Property::query()->whereIn('property_id', $visiblePropertyIds)
                ->where('user_id', $staffUser->user_id)->pluck('property_id');
            $assignedPropertyIds = PropertyAssign::query()->whereIn('property_id', $visiblePropertyIds)
                ->where('user_id', $staffUser->user_id)->where('status', 'Approved')->pluck('property_id');
            $relatedPropertyIds = $ownedPropertyIds->merge($assignedPropertyIds)->unique()->values();

            $propertiesCount = Property::query()->whereIn('property_id', $ownedPropertyIds)->whereBetween('created_at', [$from, $to])->count();
            $activeListingsCount = Property::query()->whereIn('property_id', $ownedPropertyIds)
                ->whereNotIn('property_status', ['Sold', 'Unavailable'])->count();
            $inquiriesCount = PropertyInquiry::query()->whereBetween('created_at', [$from, $to])
                ->where(fn ($query) => $query->where('recipient_id', $staffUser->user_id)->orWhereIn('property_id', $ownedPropertyIds))->count();
            $appointmentsCount = Appointment::query()->whereBetween('created_at', [$from, $to])
                ->whereHas('assignment', fn ($query) => $query->where('user_id', $staffUser->user_id)->whereIn('property_id', $visiblePropertyIds))->count();
            $completedCount = Appointment::query()->whereNotNull('completed_at')->whereBetween('completed_at', [$from, $to])
                ->whereHas('assignment', fn ($query) => $query->where('user_id', $staffUser->user_id)->whereIn('property_id', $visiblePropertyIds))->count();
            $cancelledCount = Appointment::query()->where('appointment_status', 'Cancelled')->whereBetween('updated_at', [$from, $to])
                ->whereHas('assignment', fn ($query) => $query->where('user_id', $staffUser->user_id)->whereIn('property_id', $visiblePropertyIds))->count();
            $soldCount = Property::query()->whereIn('property_id', $ownedPropertyIds)->where('property_status', 'Sold')
                ->whereBetween('sold_at', [$from, $to])->count();
            $reviewsCount = Review::query()->where('reviewed_user_id', $staffUser->user_id)->whereBetween('created_at', [$from, $to])->count();
            $averageRating = round((float) Review::query()->where('reviewed_user_id', $staffUser->user_id)->whereBetween('created_at', [$from, $to])->avg('rating'), 1);
            $completionRate = $this->percentage($completedCount, $appointmentsCount);
            $salesConversionRate = $this->percentage($soldCount, $propertiesCount);

            return compact(
                'staffUser', 'propertiesCount', 'activeListingsCount', 'inquiriesCount', 'appointmentsCount',
                'completedCount', 'cancelledCount', 'soldCount', 'reviewsCount', 'averageRating',
                'completionRate', 'salesConversionRate', 'relatedPropertyIds'
            );
        });
    }

    private function summaryRates(int $properties, int $inquiries, int $appointments, int $completed, int $sold): array
    {
        return [
            'inquiryToAppointment' => $this->percentage($appointments, $inquiries),
            'completion' => $this->percentage($completed, $appointments),
            'salesConversion' => $this->percentage($sold, $properties),
        ];
    }

    private function percentage(int $value, int $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0.0;
    }

    private function topListings($visiblePropertyIds, CarbonImmutable $from, CarbonImmutable $to, ?int $selectedOwnerId)
    {
        return Property::query()
            ->reportable()
            ->whereIn('property_id', $visiblePropertyIds)
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId))
            ->with('user:user_id,name,role_name')
            ->get()
            ->map(function (Property $property) use ($from, $to) {
                $inquiriesCount = PropertyInquiry::query()->where('property_id', $property->property_id)->whereBetween('created_at', [$from, $to])->count();
                $appointmentsCount = Appointment::query()->whereBetween('created_at', [$from, $to])
                    ->whereHas('assignment', fn ($query) => $query->where('property_id', $property->property_id))->count();
                $completedCount = Appointment::query()->whereNotNull('completed_at')->whereBetween('completed_at', [$from, $to])
                    ->whereHas('assignment', fn ($query) => $query->where('property_id', $property->property_id))->count();
                $score = $inquiriesCount + $appointmentsCount + $completedCount + ($property->property_status === 'Sold' ? 5 : 0);

                return compact('property', 'inquiriesCount', 'appointmentsCount', 'completedCount', 'score');
            })
            ->sortByDesc('score')
            ->take(6)
            ->values();
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        return $user;
    }
}
