<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $primaryKey = 'team_id';
    protected $guarded = [];

    // Define the team leader relationship (matches 'user' in with())
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id'); 
        // Adjust foreign key/owner key fields if your primary key isn't standard 'id'
    }

    // Define team members relationship (matches 'members' in with())
    public function members()
    {
        return $this->hasMany(TeamMember::class, 'team_id', 'team_id');
    }
}