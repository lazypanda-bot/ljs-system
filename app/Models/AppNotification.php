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
        $role = $user->normalizedRole();

        if ($role === 'broker') {
            $teamUserIds = TeamMember::query()
                ->whereHas('team', fn ($teamQuery) => $teamQuery->where('user_id', $user->user_id))
                ->pluck('user_id')
                ->all();

            $candidateUserIds = array_values(array_unique(array_merge([$user->user_id], $teamUserIds)));

            return $query
                ->whereIn('app_notifications.user_id', $candidateUserIds)
                ->where('audience', '!=', 'system');
        }

        if ($role === 'lead_broker') {
            if ($user->actingRole() === 'admin') {
                return $query
                    ->where(function ($q) {
                        $q->where('audience', 'system')
                            ->orWhere('type', 'message');
                    });
            }

            $teamUserIds = TeamMember::query()
                ->whereHas('team', fn ($teamQuery) => $teamQuery->where('user_id', $user->user_id))
                ->pluck('user_id')
                ->all();

            $candidateUserIds = array_values(array_unique(array_merge([$user->user_id], $teamUserIds)));

            return $query
                ->whereIn('app_notifications.user_id', $candidateUserIds)
                ->where('audience', '!=', 'system');
        }

        return $query->where('app_notifications.user_id', $user->user_id);
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