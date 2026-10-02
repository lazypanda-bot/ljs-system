<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';
    protected $primaryKey = 'notification_id';

    protected $fillable = ['user_id', 'type', 'title', 'body', 'url', 'appointment_proposal_id', 'appointment_id', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function appointmentProposal()
    {
        return $this->belongsTo(AppointmentTimeProposal::class, 'appointment_proposal_id', 'proposal_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }
}