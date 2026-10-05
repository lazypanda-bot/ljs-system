<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';
    protected $primaryKey = 'notification_id';

    protected $fillable = ['user_id', 'type', 'audience', 'title', 'body', 'url', 'appointment_proposal_id', 'appointment_id', 'read_at'];

    /**
     * Only the viewer's own notifications. A Lead Broker's feed depends on the mode:
     * admin mode = oversight (system) events plus messages; broker mode = personal events.
     */
    public function scopeVisibleTo($query, User $user)
    {
        $query->where('app_notifications.user_id', $user->user_id);

        if ($user->normalizedRole() === 'lead_broker') {
            if ($user->actingRole() === 'admin') {
                $query->where(fn ($q) => $q->where('audience', 'system')->orWhere('type', 'message'));
            } else {
                $query->where('audience', '!=', 'system');
            }
        }

        return $query;
    }

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