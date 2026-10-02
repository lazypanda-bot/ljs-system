<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('appointment_time_proposals')
            ->orderBy('proposal_id')
            ->chunkById(100, function (Collection $proposals): void {
                foreach ($proposals as $proposal) {
                    $appointment = DB::table('appointments')
                        ->where('appointment_id', $proposal->appointment_id)
                        ->first();
                    $proposer = DB::table('users')
                        ->where('user_id', $proposal->proposed_by_user_id)
                        ->first();

                    if (! $appointment || ! $proposer) {
                        continue;
                    }

                    $assignment = DB::table('property_assign')
                        ->where('assign_id', $appointment->assign_id)
                        ->first();
                    if (! $assignment) {
                        continue;
                    }

                    $clientId = $appointment->client_id ?: DB::table('users')
                        ->where('email', $appointment->contact_email)
                        ->where('role_name', 'Client')
                        ->value('user_id');
                    $recipientIds = match ($proposer->role_name) {
                        'Client' => [(int) $assignment->user_id],
                        'Admin' => array_filter([(int) $assignment->user_id, $clientId ? (int) $clientId : null]),
                        default => $clientId ? [(int) $clientId] : [],
                    };

                    if ($recipientIds === []) {
                        continue;
                    }

                    $timezone = $proposal->timezone ?: 'Asia/Manila';
                    $localStart = CarbonImmutable::parse($proposal->starts_at_utc, 'UTC')->setTimezone($timezone);
                    $expectedBody = $proposer->name . ' proposed ' . $localStart->format('M j, Y g:i A') . ' (' . $timezone . ').';
                    $createdAt = CarbonImmutable::parse($proposal->created_at);

                    DB::table('app_notifications')
                        ->whereIn('user_id', $recipientIds)
                        ->where('title', 'New appointment time proposal')
                        ->where('body', $expectedBody)
                        ->whereNull('appointment_proposal_id')
                        ->whereBetween('created_at', [
                            $createdAt->subMinute()->format('Y-m-d H:i:s'),
                            $createdAt->addMinute()->format('Y-m-d H:i:s'),
                        ])
                        ->update(['appointment_proposal_id' => $proposal->proposal_id]);
                }
            }, 'proposal_id');
    }

    public function down(): void
    {
        // Keep the recovered notification links; they are safe references to the original proposals.
    }
};