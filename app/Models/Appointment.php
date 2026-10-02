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
        'client_id',
        'contact_name',
        'contact_number',
        'contact_email',
        'facebook_link',
        'appointment_type',
        'preferred_date',
        'preferred_time',
        'starts_at_utc',
        'client_timezone',
        'additional_note',
        'appointment_status',
        'completed_at',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'starts_at_utc' => 'immutable_datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id', 'user_id');
    }

    public function proposals()
    {
        return $this->hasMany(AppointmentTimeProposal::class, 'appointment_id', 'appointment_id');
    }

    public function reminders()
    {
        return $this->hasMany(AppointmentReminder::class, 'appointment_id', 'appointment_id');
    }

    public function startInTimezone(string $timezone): \Carbon\CarbonImmutable
    {
        if ($this->starts_at_utc) {
            return \Carbon\CarbonImmutable::parse($this->starts_at_utc, 'UTC')->setTimezone($timezone);
        }

        return \Carbon\CarbonImmutable::parse(
            $this->preferred_date->format('Y-m-d') . ' ' . $this->preferred_time,
            $this->client_timezone ?: config('appointments.default_timezone')
        )->setTimezone($timezone);
    }
}