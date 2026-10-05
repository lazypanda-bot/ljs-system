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

    /** A client's upcoming appointments that are still Pending or Confirmed. */
    public function scopeActiveForClient($query, User $client)
    {
        return $query
            ->whereIn('appointment_status', ['Pending', 'Confirmed'])
            ->where(fn ($q) => $q->whereNull('starts_at_utc')->orWhere('starts_at_utc', '>=', now('UTC')->format('Y-m-d H:i:s')))
            ->where(fn ($q) => $q->where('client_id', $client->user_id)
                ->orWhere(fn ($legacy) => $legacy->whereNull('client_id')->where('contact_email', $client->email)));
    }

    /** True when the client already has a Pending or Confirmed upcoming appointment on the property. */
    public static function clientHasActiveForProperty(User $client, int $propertyId): bool
    {
        return static::activeForClient($client)
            ->whereHas('assignment', fn ($query) => $query->where('property_id', $propertyId))
            ->exists();
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