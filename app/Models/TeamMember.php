<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;

    protected $table = 'team_members';
    protected $primaryKey = 'member_id';

    protected $fillable = [
        'team_id',
        'user_id',
        'status',
        'requested_at',
        'approved_at',
    ];

    protected $dates = [
        'requested_at',
        'approved_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the team this member belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
