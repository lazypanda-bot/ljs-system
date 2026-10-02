<?php

use Illuminate\Foundation\Inspiring;
use App\Models\ConversationMessage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('messages:secure-attachments', function () {
    $migrated = 0;
    ConversationMessage::query()->whereNotNull('attachment_path')->chunkById(100, function ($messages) use (&$migrated) {
        foreach ($messages as $message) {
            $path = $message->attachment_path;
            if (! str_starts_with($path, 'message-attachments/') || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            if (! Storage::disk('local')->exists($path)
                && ! Storage::disk('local')->put($path, Storage::disk('public')->get($path))) {
                continue;
            }

            Storage::disk('public')->delete($path);
            $migrated++;
        }
    }, 'message_id', 'message_id');

    $this->info("Secured {$migrated} message attachment(s).");
})->purpose('Move existing message attachments from public storage to private storage');

Schedule::command('appointments:send-reminders')->everyFifteenMinutes()->withoutOverlapping();
