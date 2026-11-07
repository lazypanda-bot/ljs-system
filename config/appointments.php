<?php

return [
    'default_timezone' => env('APPOINTMENT_DEFAULT_TIMEZONE', 'Asia/Manila'),
    'slot_capacity' => max(1, (int) env('APPOINTMENT_SLOT_CAPACITY', 1)),
    'slot_duration_minutes' => max(1, (int) env('APPOINTMENT_SLOT_DURATION', 60)),
    // Previous schedule retained for reference:
    // 'daily_slots' => [
    //     '09:00', '10:00', '11:00', '12:00',
    //     '13:00', '14:00', '15:00', '16:00',
    // ],
    // One-hour viewings: 8 AM–12 PM and 1 PM–5 PM. No lunch-time start.
    'daily_slots' => [
        '08:00', '09:00', '10:00', '11:00',
        '13:00', '14:00', '15:00', '16:00',
    ],
    'reminder_offsets' => [1440, 60],
    'reminder_window_minutes' => 15,
];
