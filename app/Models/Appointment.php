<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';
    protected $primaryKey = 'appointment_id';

    protected $fillable = [
        'assign_id',
        'blocked_id',
        'contact_name',
        'contact_number',
        'contact_email',
        'facebook_link',
        'appointment_type',
        'preferred_date',
        'preferred_time',
        'additional_note',
        'appointment_status',
    ];

    protected $dates = [
        'preferred_date',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the property assignment for this appointment.
     */
    public function assignment()
    {
        return $this->belongsTo(PropertyAssign::class, 'assign_id', 'assign_id');
    }

    /**
     * Get the blocked date for this appointment.
     */
    public function blockedDate()
    {
        return $this->belongsTo(BlockedDate::class, 'blocked_id', 'block_id');
    }
}
