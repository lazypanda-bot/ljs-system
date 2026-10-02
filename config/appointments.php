<?php

return [
    'default_timezone' => env('APPOINTMENT_DEFAULT_TIMEZONE', 'Asia/Manila'),
    'slot_capacity' => max(1, (int) env('APPOINTMENT_SLOT_CAPACITY', 1)),
    'daily_slots' => [
        '09:00', '10:00', '11:00', '12:00',
        '13:00', '14:00', '15:00', '16:00',
    ],
    'reminder_offsets' => [1440, 60],
    'reminder_window_minutes' => 15,
];