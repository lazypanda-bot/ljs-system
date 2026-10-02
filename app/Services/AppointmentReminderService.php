<?php

namespace App\Services;

use App\Mail\ActivityMail;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppointmentReminderService
{
    public function sendDue(): int
    {
        $sent = 0;
        $now = CarbonImmutable::now('UTC');
        $window = max(1, (int) config('appointments.reminder_window_minutes', 15));

        foreach (config('appointments.reminder_offsets', [1440, 60]) as $minutesBefore) {
            $windowStart = $now->addMinutes((int) $minutesBefore);
            $windowEnd = $windowStart->addMinutes($window);

            Appointment::query()
                ->with(['assignment.user', 'client'])
                ->where('appointment_status', 'Confirmed')
                ->whereBetween('starts_at_utc', [$windowStart->format('Y-m-d H:i:s'), $windowEnd->format('Y-m-d H:i:s')])
                ->chunkById(100, function ($appointments) use (&$sent, $minutesBefore, $now): void {
                    foreach ($appointments as $appointment) {
                        $recipients = collect([$appointment->assignment?->user, $this->clientFor($appointment)])
                            ->filter(fn ($recipient) => $recipient instanceof User)
                            ->unique('user_id');

                        foreach ($recipients as $recipient) {
                            $inserted = DB::table('appointment_reminders')->insertOrIgnore([
                                'appointment_id' => $appointment->appointment_id,
                                'recipient_user_id' => $recipient->user_id,
                                'minutes_before' => $minutesBefore,
                                'sent_at' => $now->format('Y-m-d H:i:s'),
                            ]);

                            if ($inserted === 0) {
                                continue;
                            }

                            $localStart = $appointment->startInTimezone($recipient->timezone ?: config('appointments.default_timezone'));
                            $propertyName = $appointment->assignment?->property?->property_name ?? 'your property appointment';
                            $body = "Reminder: your appointment for {$propertyName} is scheduled for {$localStart->format('M j, Y g:i A')}.";

                            AppNotification::create([
                                'user_id' => $recipient->user_id,
                                'type' => 'listing',
                                'title' => 'Upcoming appointment',
                                'body' => $body,
                                'url' => route('notifications.index'),
                                'appointment_id' => $appointment->appointment_id,
                            ]);

                            try {
                                Mail::to($recipient->email, $recipient->name)->queue(new ActivityMail('Upcoming appointment reminder', $body));
                            } catch (\Throwable $exception) {
                                Log::warning('Appointment reminder email could not be queued.', [
                                    'appointment_id' => $appointment->appointment_id,
                                    'recipient_id' => $recipient->user_id,
                                    'error' => $exception->getMessage(),
                                ]);
                            }

                            $sent++;
                        }
                    }
                }, 'appointment_id');
        }

        return $sent;
    }

    private function clientFor(Appointment $appointment): ?User
    {
        if ($appointment->client instanceof User) {
            return $appointment->client;
        }

        return User::query()
            ->where('email', $appointment->contact_email)
            ->where('role_name', 'Client')
            ->where('account_status', 'Active')
            ->first();
    }
}