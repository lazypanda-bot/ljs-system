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

        return $query;
    }
}