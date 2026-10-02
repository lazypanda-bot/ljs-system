<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentReminder extends Model
{
    public $timestamps = false;

    protected $table = 'appointment_reminders';
    protected $primaryKey = 'reminder_id';

    protected $fillable = [
        'appointment_id',
        'recipient_user_id',
        'minutes_before',
        'sent_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}