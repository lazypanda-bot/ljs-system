<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    // Explicitly tell Laravel to use your actual database table name
    protected $table = 'property_listings';

    // If your primary key column is not 'id', define it here too:
    protected $primaryKey = 'property_id';

    protected $guarded = [];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Relationships
    public function images()
    {
        return $this->hasMany(PropertyImage::class, 'property_id', 'property_id');
    }

    public function details()
    {
        return $this->hasOne(PropertyDetail::class, 'property_id', 'property_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the property assignments.
     */
    public function assignments()
    {
        return $this->hasMany(PropertyAssign::class, 'property_id', 'property_id');
    }

    public function referralCodes()
    {
        return $this->hasMany(ReferralCode::class, 'property_id', 'property_id');
    }

    public const DETAIL_FIELDS = ['lot_area', 'floor_area', 'bedrooms', 'bathrooms', 'parking_spaces', 'number_of_floors', 'property_condition', 'year_built'];

    /** Land types only use land fields; buildings and any custom type keep the full set (optional unless required). */
    public static function visibleDetailFields(?string $type): array
    {
        return match (strtolower(trim((string) $type))) {
            'lot', 'farm lot' => ['lot_area'],
            default => static::DETAIL_FIELDS,
        };
    }

    /** Residential presets always show bed/bath counts; other types show them only when filled in. */
    public static function isResidentialType(?string $type): bool
    {
        return in_array(strtolower(trim((string) $type)), ['house & lot', 'townhouse', 'condominium', 'apartment'], true);
    }

    /** Maps validated form input to property_details columns, resetting fields hidden for the type. */
    public static function normalizeDetails(array $validated): array
    {
        $visible = static::visibleDetailFields($validated['property_type'] ?? null);
        $pick = fn (string $field, $default = null) => in_array($field, $visible, true) ? ($validated[$field] ?? $default) : $default;

        return [
            'lot_area' => $pick('lot_area'),
            'floor_area' => $pick('floor_area'),
            'bedroom' => $pick('bedrooms', 0),
            'bathroom' => $pick('bathrooms', 0),
            'parking_spaces' => $pick('parking_spaces', 0),
            'number_of_floors' => $pick('number_of_floors', 1),
            'property_condition' => $pick('property_condition'),
            'year_built' => $pick('year_built'),
            'remarks' => $validated['remarks'] ?? null,
        ];
    }

    /** Detail fields a listing must fill in, by property type. Unknown or custom types require none. */
    public static function requiredDetailFields(?string $type): array
    {
        return match (strtolower(trim((string) $type))) {
            'house & lot', 'townhouse' => ['lot_area', 'floor_area', 'bedrooms', 'bathrooms'],
            'condominium', 'apartment' => ['floor_area', 'bedrooms', 'bathrooms'],
            'commercial', 'warehouse' => ['lot_area', 'floor_area'],
            'lot', 'farm lot' => ['lot_area'],
            default => [],
        };
    }

    public static function detailRules(?string $type): array
    {
        $required = static::requiredDetailFields($type);
        $base = [
            'lot_area' => ['numeric', 'min:0'],
            'floor_area' => ['numeric', 'min:0'],
            'bedrooms' => ['integer', 'min:0'],
            'bathrooms' => ['integer', 'min:0'],
        ];

        return collect($base)->map(fn ($rules, $field) => [in_array($field, $required, true) ? 'required' : 'nullable', ...$rules])->all();
    }

    /** Sold, reserved and unavailable properties cannot be booked for viewings. */
    public function acceptsAppointments(): bool
    {
        return ! in_array($this->property_status, ['Sold', 'Reserved', 'Unavailable'], true);
    }

    /** Lowercased type => required fields, for the form scripts. */
    public static function detailRequirementMap(): array
    {
        return collect(static::typeOptions())->mapWithKeys(fn ($type) => [strtolower($type) => static::requiredDetailFields($type)])->all();
    }

    /** Lowercased type => visible fields, for the form scripts. */
    public static function detailVisibilityMap(): array
    {
        return collect(static::typeOptions())->mapWithKeys(fn ($type) => [strtolower($type) => static::visibleDetailFields($type)])->all();
    }

    /** Common property types plus any custom types already used by listings. */
    public static function typeOptions(): array
    {
        return collect(['House & Lot', 'Condominium', 'Townhouse', 'Apartment', 'Commercial', 'Warehouse', 'Farm Lot', 'Lot'])
            ->merge(static::withTrashed()->whereNotNull('property_type')->distinct()->pluck('property_type'))
            ->map(fn ($type) => trim((string) $type))
            ->filter()
            ->unique(fn ($type) => strtolower($type))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Scope properties to what the given user is allowed to see, based on their
     * acting role: admins see everything, brokers see their whole team's
     * properties (themselves + approved team members), agents see only their own.
     */
    public function scopeVisibleTo($query, User $user)
    {
        $acting = $user->actingRole();

        if ($acting === 'agent') {
            return $query->where(function ($query) use ($user) {
                $query->where('user_id', $user->user_id)
                    ->orWhereHas('assignments', fn ($query) => $query->where('user_id', $user->user_id)->where('status', 'Approved'));
            });
        }

        if ($acting === 'broker') {
            $teamIds = $user->teams()->pluck('team_id');

            $memberIds = TeamMember::whereIn('team_id', $teamIds)
                ->where('status', 'Approved')
                ->pluck('user_id')
                ->push($user->user_id);

            return $query->where(function ($query) use ($memberIds, $teamIds) {
                $query->whereIn('user_id', $memberIds)
                    ->orWhereHas('assignments', function ($query) use ($teamIds) {
                        $query->whereIn('user_id', TeamMember::whereIn('team_id', $teamIds)->where('status', 'Approved')->pluck('user_id'))->where('status', 'Approved');
                    });
            });
        }

        if ($acting === 'admin') {
            return $query->where(fn ($query) => $query->whereNull('listing_mode')->orWhere('listing_mode', '!=', 'admin')->orWhere('user_id', $user->user_id));
        }

        return $query;
    }

    /** Admin-listed projects belong to the admin who created them; other listings are open to every admin. */
    public function isManageableByAdmin(User $user): bool
    {
        return $this->listing_mode !== 'admin' || (int) $this->user_id === (int) $user->user_id;
    }
}