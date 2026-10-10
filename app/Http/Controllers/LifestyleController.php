<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LifestyleController extends Controller
{
    private const BUDGET_RANGES = [
        'under_1m' => [0, 1_000_000],
        '1m_5m'    => [1_000_000, 5_000_000],
        '5m_10m'   => [5_000_000, 10_000_000],
        '10m_20m'  => [10_000_000, 20_000_000],
        'over_20m' => [20_000_000, PHP_INT_MAX],
    ];

    private const HOUSEHOLD_MIN_BEDROOMS = [
        'just_me'      => 1,
        'couple'       => 2,
        'small_family' => 3,
        'large_family' => 5,
    ];

    private const PREFERENCE_LABELS = [
        'lifestyle' => [
            'nature_lover'     => 'Nature Lover',
            'city_explorer'    => 'City Explorer',
            'family_oriented'  => 'Family Oriented',
            'work_business'    => 'Work & Business',
            'beach_lover'      => 'Beach Lover',
            'peace_seeker'     => 'Peace Seeker',
        ],
        'location' => [
            'near_coast'         => 'Near the Coast',
            'city_center'        => 'City Center',
            'quiet_suburb'       => 'Quiet Suburb',
            'business_district'  => 'Business District',
            'countryside'        => 'Countryside',
        ],
        'property_type' => [
            'House'      => 'House',
            'Condo'      => 'Condo',
            'Townhouse'  => 'Townhouse',
            'Lot'        => 'Lot',
        ],
        'budget' => [
            'under_1m' => 'Under ₱1M',
            '1m_5m'    => '₱1M - ₱5M',
            '5m_10m'   => '₱5M - ₱10M',
            '10m_20m'  => '₱10M - ₱20M',
            'over_20m' => '₱20M+',
        ],
        'household' => [
            'just_me'      => 'Just Me',
            'couple'       => 'Couple',
            'small_family' => 'Small Family',
            'large_family' => 'Large Family',
        ],
    ];

    public function index()
    {
        $properties = Property::query()
            ->with('details.amenities')
            ->where('approval_status', 'Approved')
            ->whereNotIn('property_status', ['Pending', 'Sold', 'Unavailable'])
            ->get(['property_id', 'lifestyle_tag', 'location_tag']);

        $toOptions = static function ($values, string $icon, string $description, bool $slugValues = false): array {
            return collect($values)
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique(fn ($value) => mb_strtolower($value))
                ->map(fn ($value) => [
                    'value' => $value,
                    'icon' => $icon,
                    'label' => $slugValues ? Str::headline(str_replace('_', ' ', $value)) : $value,
                    'description' => $description,
                ])
                ->values()
                ->all();
        };

        $lifestyleValues = $properties->flatMap(fn ($property) => explode(',', (string) $property->lifestyle_tag));
        $locationValues = $properties->flatMap(fn ($property) => explode(',', (string) $property->location_tag));
        $amenityValues = $properties->flatMap(fn ($property) => $property->details?->amenities->pluck('amenity_name') ?? collect());
        $matchChoices = [
            'lifestyle' => $toOptions($lifestyleValues, 'sparkles', 'Matches this property lifestyle tag.', true),
            'location' => $toOptions($locationValues, 'map-pin', 'Matches this property location preference.', true),
            'amenities' => $toOptions($amenityValues, 'house', 'Available on one or more listed properties.'),
        ];

        return view('customer.lifestyle', compact('matchChoices'));
    }

    public function results(Request $request)
    {
        $answers = $request->validate([
            'lifestyle'      => 'nullable|string',
            'location'       => 'nullable|string',
            'property_type'  => 'nullable|string',
            'budget'         => 'nullable|string',
            'household'      => 'nullable|string',
            'must_haves'     => 'nullable|array',
            'must_haves.*'   => 'string',
            'amenities'      => 'nullable|array',
            'amenities.*'    => 'string',
        ]);

        $properties = Property::with(['images', 'details.amenities'])
            ->where('approval_status', 'Approved')
            ->whereNotIn('property_status', ['Pending', 'Sold', 'Unavailable'])
            ->get();

        $scored = $properties
            ->map(fn (Property $property) => [
                'property' => $property,
                'score'    => $this->score($property, $answers),
            ])
            ->sortByDesc('score')
            ->take(6)
            ->values();

        $matches = $scored->map(function ($entry) {
            $property = $entry['property'];
            $firstImage = $property->images->first();

            return [
                'id'       => $property->property_id,
                'name'     => $property->property_name,
                'price'    => (float) $property->price,
                'status'   => $property->property_status,
                'location' => trim(collect([$property->city, $property->province])->filter()->implode(', ')),
                'bedrooms' => $property->details->bedroom ?? 0,
                'bathrooms'=> $property->details->bathroom ?? 0,
                'floorArea'=> $property->details->floor_area ?? 0,
                'image'    => $firstImage ? asset('storage/' . $firstImage->image_path) : asset('images/night.png'),
                'score'    => $entry['score'],
                'url'      => route('properties.show', $property->property_id),
            ];
        });

        return response()->json([
            'matches'     => $matches,
            'matchScore'  => $matches->max('score') ?? 0,
            'preferences' => $this->preferenceSummary($answers),
        ]);
    }

    private function score(Property $property, array $answers): int
    {
        $earned = 0;
        $possible = 0;

        // Lifestyle tag (25 pts)
        if (! empty($answers['lifestyle'])) {
            $possible += 25;
            if (in_array($answers['lifestyle'], $this->storedTags($property->lifestyle_tag), true)) {
                $earned += 25;
            }
        }

        // Location tag (20 pts)
        if (! empty($answers['location'])) {
            $possible += 20;
            if (in_array($answers['location'], $this->storedTags($property->location_tag), true)) {
                $earned += 20;
            }
        }

        // Property type (15 pts)
        if (! empty($answers['property_type'])) {
            $possible += 15;
            $wanted = strtolower($answers['property_type']);
            $actual = strtolower((string) $property->property_type);
            if ($actual !== '' && (str_contains($actual, $wanted) || str_contains($wanted, $actual))) {
                $earned += 15;
            }
        }

        // Budget range (15 pts)
        if (! empty($answers['budget']) && isset(self::BUDGET_RANGES[$answers['budget']])) {
            $possible += 15;
            [$min, $max] = self::BUDGET_RANGES[$answers['budget']];
            if ($property->price >= $min && $property->price <= $max) {
                $earned += 15;
            }
        }

        // Household size -> minimum bedrooms (10 pts)
        if (! empty($answers['household']) && isset(self::HOUSEHOLD_MIN_BEDROOMS[$answers['household']])) {
            $possible += 10;
            $minBedrooms = self::HOUSEHOLD_MIN_BEDROOMS[$answers['household']];
            if (($property->details->bedroom ?? 0) >= $minBedrooms) {
                $earned += 10;
            }
        }

        $amenityNames = $property->details
            ? $property->details->amenities->pluck('amenity_name')->map(fn ($name) => strtolower($name))
            : collect();

        // Must-have features (10 pts, proportional)
        if (! empty($answers['must_haves'])) {
            $possible += 10;
            $earned += $this->proportionalMatch($answers['must_haves'], $amenityNames) * 10;
        }

        // Amenities (5 pts, proportional)
        if (! empty($answers['amenities'])) {
            $possible += 5;
            $earned += $this->proportionalMatch($answers['amenities'], $amenityNames) * 5;
        }

        if ($possible === 0) {
            return 0;
        }

        return (int) round(($earned / $possible) * 100);
    }

    private function proportionalMatch(array $wanted, $amenityNames): float
    {
        if (empty($wanted)) {
            return 0;
        }

        $matchCount = collect($wanted)
            ->filter(fn ($item) => $amenityNames->contains(fn ($name) => str_contains($name, strtolower($item))))
            ->count();

        return $matchCount / count($wanted);
    }

    private function storedTags(?string $tags): array
    {
        return collect(explode(',', (string) $tags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
    }

    private function preferenceSummary(array $answers): array
    {
        $summary = [];

        foreach (['lifestyle', 'location', 'property_type', 'budget', 'household'] as $key) {
            if (empty($answers[$key])) {
                continue;
            }

            $summary[$key] = self::PREFERENCE_LABELS[$key][$answers[$key]]
                ?? (in_array($key, ['lifestyle', 'location'], true)
                    ? Str::headline(str_replace('_', ' ', $answers[$key]))
                    : $answers[$key]);
        }

        if (! empty($answers['must_haves'])) {
            $summary['must_haves'] = implode(', ', $answers['must_haves']);
        }

        if (! empty($answers['amenities'])) {
            $summary['amenities'] = implode(', ', $answers['amenities']);
        }

        return $summary;
    }
}
