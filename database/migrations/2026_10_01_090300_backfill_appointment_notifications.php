<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('app_notifications')
            ->whereNull('appointment_id')
            ->whereIn('title', [
                'New appointment request',
                'Appointment confirmed',
                'Appointment completed',
                'Appointment cancelled',
                'Appointment rejected',
            ])
            ->orderBy('notification_id')
            ->chunkById(100, function ($notifications): void {
                foreach ($notifications as $notification) {
                    $user = DB::table('users')->where('user_id', $notification->user_id)->first();
                    if (! $user) {
                        continue;
                    }

                    $createdAt = CarbonImmutable::parse($notification->created_at);
                    $appointments = DB::table('appointments')
                        ->join('property_assign as assignment', 'assignment.assign_id', '=', 'appointments.assign_id')
                        ->join('property_listings as property', 'property.property_id', '=', 'assignment.property_id')
                        ->select([
                            'appointments.appointment_id',
                            'appointments.client_id',
                            'appointments.contact_email',
                            'appointments.appointment_status',
                            'appointments.updated_at',
                            'assignment.user_id as professional_id',
                            'property.property_name',
                        ])
                        ->whereBetween('appointments.updated_at', [
                            $createdAt->subMinutes(3)->format('Y-m-d H:i:s'),
                            $createdAt->addMinutes(3)->format('Y-m-d H:i:s'),
                        ])
                        ->get()
                        ->filter(function ($appointment) use ($notification, $user): bool {
                            if (! str_contains((string) $notification->body, (string) $appointment->property_name)) {
                                return false;
                            }

                            $isParticipant = (int) $appointment->professional_id === (int) $notification->user_id
                                || (int) $appointment->client_id === (int) $notification->user_id
                                || ($appointment->client_id === null && strcasecmp((string) $appointment->contact_email, (string) $user->email) === 0);
                            if (! $isParticipant) {
                                return false;
                            }

                            if ($notification->title === 'New appointment request') {
                                return (int) $appointment->professional_id === (int) $notification->user_id;
                            }

                            $status = ucfirst(substr($notification->title, strlen('Appointment ')));

                            return $appointment->appointment_status === $status;
                        })
                        ->values();

                    if ($appointments->count() === 1) {
                        DB::table('app_notifications')
                            ->where('notification_id', $notification->notification_id)
                            ->update(['appointment_id' => $appointments->first()->appointment_id]);
                    }
                }
            }, 'notification_id');
    }

    public function down(): void
    {
        // Keep recovered links so rolling this migration back never loses notification context.
    }
};