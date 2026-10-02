<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentTimeProposal extends Model
{
    protected $table = 'appointment_time_proposals';
    protected $primaryKey = 'proposal_id';

    protected $fillable = [
        'appointment_id',
        'proposed_by_user_id',
        'starts_at_utc',
        'preferred_date',
        'preferred_time',
        'timezone',
        'message',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at_utc' => 'immutable_datetime',
            'preferred_date' => 'date',
            'responded_at' => 'datetime',
        ];
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }

    public function proposedBy()
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id', 'user_id');
    }
}