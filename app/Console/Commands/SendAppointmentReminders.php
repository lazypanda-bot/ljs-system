<?php

namespace App\Console\Commands;

use App\Services\AppointmentReminderService;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Send due appointment reminders to clients and professionals';

    public function handle(AppointmentReminderService $reminders): int
    {
        $count = $reminders->sendDue();
        $this->info("Sent {$count} appointment reminder(s).");

        return self::SUCCESS;
    }
}