<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('app_notifications')
            ->where('title', 'New appointment time proposal')
            ->whereNotNull('body')
            ->orderBy('notification_id')
            ->chunkById(100, function ($notifications): void {
                foreach ($notifications as $notification) {
                    $body = preg_replace(
                        '/\s+\([A-Za-z_+-]+(?:\/[A-Za-z0-9_+-]+)+\)(?=[.!?]?\s*$)/u',
                        '',
                        $notification->body
                    );

                    if ($body !== null && $body !== $notification->body) {
                        DB::table('app_notifications')
                            ->where('notification_id', $notification->notification_id)
                            ->update(['body' => $body]);
                    }
                }
            }, 'notification_id');
    }

    public function down(): void
    {
        // Removed display suffixes cannot be safely reconstructed without the viewer's timezone.
    }
};