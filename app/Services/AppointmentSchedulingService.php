<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\PropertyAssign;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Validation\ValidationException;

class AppointmentSchedulingService
{
    /** @return array<int, array{value: string, label: string}> */
    public function availableSlots(
        PropertyAssign $assignment,
        string $date,
        string $clientTimezone,
        ?int $exceptAppointmentId = null
    ): array {
        $clientZone = new DateTimeZone($clientTimezone);
        $professionalZone = new DateTimeZone($assignment->user?->timezone ?: config('appointments.default_timezone'));
        $clientDayStart = CarbonImmutable::createFromFormat('!Y-m-d', $date, $clientZone);
        $clientDayEnd = $clientDayStart->addDay();
        $firstProfessionalDay = $clientDayStart->setTimezone($professionalZone)->startOfDay()->subDay();
        $lastProfessionalDay = $clientDayEnd->setTimezone($professionalZone)->startOfDay()->addDay();
        $slots = [];

        for ($day = $firstProfessionalDay; $day <= $lastProfessionalDay; $day = $day->addDay()) {
            foreach (config('appointments.daily_slots', []) as $time) {
                $professionalLocal = CarbonImmutable::createFromFormat('!Y-m-d H:i', $day->format('Y-m-d') . ' ' . $time, $professionalZone);
                $slotUtc = $professionalLocal->utc();

                if ($slotUtc->lessThanOrEqualTo(CarbonImmutable::now('UTC'))
                    || $slotUtc->lessThan($clientDayStart->utc())
                    || ! $slotUtc->lessThan($clientDayEnd->utc())
                    || $this->isBlocked($assignment, $slotUtc)
                    || ! $this->hasCapacity($assignment, $slotUtc, $exceptAppointmentId)) {
                    continue;
                }

                $slots[] = [
                    'value' => $slotUtc->format('Y-m-d\TH:i:s\Z'),
                    'label' => $slotUtc->setTimezone($clientZone)->format('g:i A'),
                    'local_time' => $slotUtc->setTimezone($clientZone)->format('H:i'),
                ];
            }
        }

        usort($slots, fn (array $left, array $right): int => strcmp($left['value'], $right['value']));

        return $slots;
    }

    public function assertAvailable(PropertyAssign $assignment, CarbonImmutable $slotUtc, ?int $exceptAppointmentId = null): void
    {
        if ($slotUtc->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
            throw ValidationException::withMessages(['slot_start_utc' => 'Choose a future appointment slot.']);
        }

        $professionalTimezone = $assignment->user?->timezone ?: config('appointments.default_timezone');
        $professionalLocal = $slotUtc->setTimezone($professionalTimezone);
        if ($professionalLocal->format('s') !== '00'
            || ! in_array($professionalLocal->format('H:i'), config('appointments.daily_slots', []), true)) {
            throw ValidationException::withMessages(['slot_start_utc' => 'Choose one of the available appointment times.']);
        }

        if ($this->isBlocked($assignment, $slotUtc)) {
            throw ValidationException::withMessages(['slot_start_utc' => 'The professional is unavailable at that time.']);
        }

        if (! $this->hasCapacity($assignment, $slotUtc, $exceptAppointmentId)) {
            throw ValidationException::withMessages(['slot_start_utc' => 'That appointment slot is full. Choose another time.']);
        }
    }

    private function hasCapacity(PropertyAssign $assignment, CarbonImmutable $slotUtc, ?int $exceptAppointmentId): bool
    {
        $professionalId = (int) $assignment->user_id;
        $booked = Appointment::query()
            ->where('starts_at_utc', $slotUtc->format('Y-m-d H:i:s'))
            ->whereIn('appointment_status', ['Pending', 'Confirmed'])
            ->whereHas('assignment', fn ($query) => $query->where('user_id', $professionalId))
            ->when($exceptAppointmentId, fn ($query) => $query->where('appointment_id', '!=', $exceptAppointmentId))
            ->count();

        return $booked < max(1, (int) config('appointments.slot_capacity', 1));
    }

    private function isBlocked(PropertyAssign $assignment, CarbonImmutable $slotUtc): bool
    {
        $blocks = BlockedDate::query()
            ->where('user_id', $assignment->user_id)
            ->whereBetween('date', [
                $slotUtc->subDay()->toDateString(),
                $slotUtc->addDay()->toDateString(),
            ])
            ->get();

        foreach ($blocks as $block) {
            $timezone = new DateTimeZone($block->timezone ?: config('appointments.default_timezone'));
            $date = $block->date instanceof CarbonImmutable
                ? $block->date->format('Y-m-d')
                : substr((string) $block->date, 0, 10);
            $startTime = $block->start_time ?: '00:00:00';
            $start = CarbonImmutable::parse($date . ' ' . $startTime, $timezone)->utc();
            $end = $block->start_time && $block->end_time
                ? CarbonImmutable::parse($date . ' ' . $block->end_time, $timezone)->utc()
                : $start->addDay();

            if ($slotUtc->greaterThanOrEqualTo($start) && $slotUtc->lessThan($end)) {
                return true;
            }
        }

        return false;
    }
}