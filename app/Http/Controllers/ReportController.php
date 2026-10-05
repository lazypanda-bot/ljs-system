<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ConversationMessage;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();
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
        $visibleOwnerIds = (clone $visibleProperties)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $isAdminView = $user->actingRole() === 'admin';
        if ($isAdminView) {
            // Admin sees every staff member, including those with no listings yet.
            $visibleOwnerIds = User::query()->where('role_name', '!=', 'Client')->pluck('user_id');
        }
        $selectedOwnerId = isset($filters['user_id']) && $visibleOwnerIds->contains((int) $filters['user_id'])
            ? (int) $filters['user_id']
            : null;
        $staff = User::query()->whereIn('user_id', $visibleOwnerIds)->orderBy('name')->get(['user_id', 'name', 'role_name']);
        $propertyScope = fn ($query) => $query->whereIn('property_id', $visiblePropertyIds)
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId));

        $properties = (clone $visibleProperties)->whereBetween('created_at', [$from, $to])
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId))
            ->with('user')->get();
        $inquiriesQuery = PropertyInquiry::query()->whereBetween('created_at', [$from, $to])
            ->whereHas('property', $propertyScope);
        $appointmentsQuery = Appointment::query()->whereBetween('created_at', [$from, $to])
            ->whereHas('assignment.property', $propertyScope);
        $completedQuery = Appointment::query()->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$from, $to])->whereHas('assignment.property', $propertyScope);
        $soldQuery = (clone $visibleProperties)->where('property_status', 'Sold')->whereBetween('sold_at', [$from, $to])
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId));

        $inquiries = $inquiriesQuery->count();
        $appointments = $appointmentsQuery->count();
        $completed = $completedQuery->count();
        $sold = $soldQuery->count();
        $soldProperties = $soldQuery->with('user')->get();
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
        $perUser = $reportStaff->map(function (User $staffUser) use ($from, $to, $visiblePropertyIds) {
            $ownedPropertyIds = Property::query()->whereIn('property_id', $visiblePropertyIds)
                ->where('user_id', $staffUser->user_id)->pluck('property_id');
            $propertiesCount = Property::query()->whereIn('property_id', $ownedPropertyIds)->whereBetween('created_at', [$from, $to])->count();
            $inquiriesCount = PropertyInquiry::query()->whereIn('property_id', $ownedPropertyIds)->whereBetween('created_at', [$from, $to])->count();
            $appointmentsCount = Appointment::query()->whereBetween('created_at', [$from, $to])
                ->whereHas('assignment.property', fn ($query) => $query->whereIn('property_id', $ownedPropertyIds))->count();
            $completedCount = Appointment::query()->whereNotNull('completed_at')->whereBetween('completed_at', [$from, $to])
                ->whereHas('assignment.property', fn ($query) => $query->whereIn('property_id', $ownedPropertyIds))->count();
            $soldCount = Property::query()->whereIn('property_id', $ownedPropertyIds)->where('property_status', 'Sold')
                ->whereBetween('sold_at', [$from, $to])->count();

            return compact('staffUser', 'propertiesCount', 'inquiriesCount', 'appointmentsCount', 'completedCount', 'soldCount');
        });

        $ownersPage = max(1, (int) request()->query('owners_page', 1));
        $perUser = new \Illuminate\Pagination\LengthAwarePaginator(
            $perUser->forPage($ownersPage, 5)->values(),
            $perUser->count(),
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

        return view('pages.reports-live', compact(
            'properties', 'appointments', 'completed', 'sold', 'soldProperties', 'inquiries', 'overview',
            'staff', 'perUser', 'selectedOwnerId', 'reportFrom', 'reportTo', 'trend', 'trendMax', 'trendDateValue'
        ));
    }

    public function export(Request $request)
    {
        $user = $this->user();
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer'],
        ]);
        $to = CarbonImmutable::parse($filters['to'] ?? today()->toDateString())->endOfDay();
        $from = CarbonImmutable::parse($filters['from'] ?? $to->subDays(29)->toDateString())->startOfDay();
        $visibleProperties = $this->reportProperties($user);
        $visibleOwnerIds = (clone $visibleProperties)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $visiblePropertyIds = (clone $visibleProperties)->pluck('property_id');
        $selectedOwnerId = isset($filters['user_id']) && $visibleOwnerIds->contains((int) $filters['user_id'])
            ? (int) $filters['user_id']
            : null;
        $propertyScope = fn ($query) => $query->whereIn('property_id', $visiblePropertyIds)
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId));
        $properties = (clone $visibleProperties)->whereBetween('created_at', [$from, $to])
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId))
            ->with('user')->get(['property_id', 'user_id', 'property_name', 'property_status', 'price', 'created_at']);
        $inquiries = PropertyInquiry::query()->whereBetween('created_at', [$from, $to])->whereHas('property', $propertyScope)->count();
        $appointments = Appointment::query()->whereBetween('created_at', [$from, $to])->whereHas('assignment.property', $propertyScope)->count();
        $completed = Appointment::query()->whereNotNull('completed_at')->whereBetween('completed_at', [$from, $to])->whereHas('assignment.property', $propertyScope)->count();
        $sold = (clone $visibleProperties)->where('property_status', 'Sold')->whereBetween('sold_at', [$from, $to])
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId))->count();
        $owners = User::query()->whereIn('user_id', $visibleOwnerIds)
            ->when($selectedOwnerId, fn ($query) => $query->where('user_id', $selectedOwnerId))->orderBy('name')->get();

        return response()->streamDownload(function () use ($properties, $owners, $from, $to, $inquiries, $appointments, $completed, $sold, $visibleProperties) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date range', $from->toDateString() . ' to ' . $to->toDateString()]);
            fputcsv($handle, ['Metric', 'Count']);
            fputcsv($handle, ['Properties listed', $properties->count()]);
            fputcsv($handle, ['Property inquiries', $inquiries]);
            fputcsv($handle, ['Appointments requested', $appointments]);
            fputcsv($handle, ['Appointments completed', $completed]);
            fputcsv($handle, ['Properties sold', $sold]);
            fputcsv($handle, []);
            fputcsv($handle, ['Listing owner', 'Role', 'Properties listed', 'Inquiries', 'Appointments', 'Completed', 'Sold']);
            foreach ($owners as $owner) {
                $ownerPropertyIds = (clone $visibleProperties)->where('user_id', $owner->user_id)->pluck('property_id');
                $ownerProperties = Property::query()->whereIn('property_id', $ownerPropertyIds);
                fputcsv($handle, [
                    $owner->name,
                    $owner->role_name,
                    (clone $ownerProperties)->whereBetween('created_at', [$from, $to])->count(),
                    PropertyInquiry::query()->whereIn('property_id', $ownerPropertyIds)->whereBetween('created_at', [$from, $to])->count(),
                    Appointment::query()->whereBetween('created_at', [$from, $to])->whereHas('assignment.property', fn ($query) => $query->whereIn('property_id', $ownerPropertyIds))->count(),
                    Appointment::query()->whereNotNull('completed_at')->whereBetween('completed_at', [$from, $to])->whereHas('assignment.property', fn ($query) => $query->whereIn('property_id', $ownerPropertyIds))->count(),
                    (clone $ownerProperties)->where('property_status', 'Sold')->whereBetween('sold_at', [$from, $to])->count(),
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Property ID', 'Property', 'Status', 'Price', 'Seller/Professional', 'Role', 'Created']);
            foreach ($properties as $property) {
                fputcsv($handle, [$property->property_id, $property->property_name, $property->property_status, $property->price, $property->user?->name, $property->user?->role_name, $property->created_at?->toDateString()]);
            }
            fclose($handle);
        }, 'ljs-property-report.csv', ['Content-Type' => 'text/csv']);
    }

    /** Admins report on everything; professionals only on the listings they own. */
    private function reportProperties(User $user)
    {
        return $user->actingRole() === 'admin'
            ? Property::query()
            : Property::query()->where('user_id', $user->user_id);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        return $user;
    }
}