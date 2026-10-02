<?php

namespace Tests\Feature;

use App\Mail\ActivityMail;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\User;
use App\Services\AppointmentReminderService;
use App\Services\AppointmentSchedulingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_saves_the_utc_instant_and_rejects_a_full_slot(): void
    {
        Mail::fake();
        config(['appointments.daily_slots' => ['09:00'], 'appointments.slot_capacity' => 1]);

        $professional = User::factory()->create(['role_name' => 'Agent', 'timezone' => 'Asia/Manila']);
        $client = User::factory()->create(['role_name' => 'Client', 'timezone' => 'America/Los_Angeles']);
        $secondClient = User::factory()->create(['role_name' => 'Client', 'timezone' => 'America/Los_Angeles']);
        [$property, $assignment] = $this->createAssignment($professional, 'UTC booking test');
        $providerStart = CarbonImmutable::parse('2030-01-10 09:00', 'Asia/Manila')->utc();
        $clientStart = $providerStart->setTimezone($client->timezone);
        $payload = [
            'assign_id' => $assignment->assign_id,
            'contact_name' => $client->name,
            'contact_number' => '555-0101',
            'contact_email' => $client->email,
            'appointment_type' => 'Property viewing',
            'preferred_date' => $clientStart->toDateString(),
            'preferred_time' => $clientStart->format('H:i'),
            'slot_start_utc' => $providerStart->format('Y-m-d\TH:i:s\Z'),
            'client_timezone' => $client->timezone,
        ];

        $this->actingAs($client)
            ->post(route('appointments.store', $property), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'assign_id' => $assignment->assign_id,
            'client_id' => $client->user_id,
            'starts_at_utc' => $providerStart->format('Y-m-d H:i:s'),
        ]);

        $this->actingAs($secondClient)
            ->from(route('appointments.create', $property))
            ->post(route('appointments.store', $property), array_merge($payload, [
                'contact_name' => $secondClient->name,
                'contact_email' => $secondClient->email,
            ]))
            ->assertSessionHasErrors('slot_start_utc');
    }

    public function test_availability_converts_timezones_and_closes_blocked_or_full_slots(): void
    {
        config(['appointments.daily_slots' => ['09:00'], 'appointments.slot_capacity' => 2]);
        $professional = User::factory()->create(['role_name' => 'Agent', 'timezone' => 'Asia/Manila']);
        $firstClient = User::factory()->create(['role_name' => 'Client']);
        $secondClient = User::factory()->create(['role_name' => 'Client']);
        [$firstProperty, $firstAssignment] = $this->createAssignment($professional, 'First assigned home');
        [, $secondAssignment] = $this->createAssignment($professional, 'Second assigned home');
        $providerStart = CarbonImmutable::parse('2030-01-10 09:00', 'Asia/Manila')->utc();
        $clientStart = $providerStart->setTimezone('America/Los_Angeles');
        $service = app(AppointmentSchedulingService::class);

        $slots = $service->availableSlots($firstAssignment, $clientStart->toDateString(), 'America/Los_Angeles');
        $this->assertSame($providerStart->format('Y-m-d\TH:i:s\Z'), $slots[0]['value']);
        $this->assertSame('5:00 PM', $slots[0]['label']);

        BlockedDate::create([
            'user_id' => $professional->user_id,
            'date' => '2030-01-10',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'timezone' => 'Asia/Manila',
        ]);
        $this->assertSame([], $service->availableSlots($firstAssignment, $clientStart->toDateString(), 'America/Los_Angeles'));
        BlockedDate::query()->delete();

        $this->createAppointment($firstAssignment, $firstClient, $providerStart);
        $this->createAppointment($secondAssignment, $secondClient, $providerStart);
        $this->assertSame([], $service->availableSlots($firstAssignment, $clientStart->toDateString(), 'America/Los_Angeles'));
    }

    public function test_public_availability_endpoint_returns_client_local_labels_and_utc_values(): void
    {
        config(['appointments.daily_slots' => ['09:00'], 'appointments.slot_capacity' => 2]);
        $professional = User::factory()->create(['role_name' => 'Agent', 'timezone' => 'Asia/Manila']);
        [$property, $assignment] = $this->createAssignment($professional, 'Availability endpoint home');
        $providerStart = CarbonImmutable::parse('2030-01-10 09:00', 'Asia/Manila')->utc();
        $clientStart = $providerStart->setTimezone('America/Los_Angeles');

        $this->getJson(route('appointments.availability', $property) . '?' . http_build_query([
            'assign_id' => $assignment->assign_id,
            'date' => $clientStart->toDateString(),
            'timezone' => 'America/Los_Angeles',
        ]))
            ->assertOk()
            ->assertJsonPath('slots.0.value', $providerStart->format('Y-m-d\TH:i:s\Z'))
            ->assertJsonPath('slots.0.local_time', $clientStart->format('H:i'))
            ->assertJsonPath('slots.0.label', '5:00 PM');
    }

    public function test_client_can_propose_a_new_time_and_professional_can_accept_it(): void
    {
        Mail::fake();
        config(['appointments.daily_slots' => ['10:00', '11:00'], 'appointments.slot_capacity' => 1]);
        $professional = User::factory()->create(['role_name' => 'Agent', 'timezone' => 'Asia/Manila']);
        $client = User::factory()->create(['role_name' => 'Client', 'timezone' => 'America/Los_Angeles']);
        [$property, $assignment] = $this->createAssignment($professional, 'Reschedule test home');
        $originalStart = CarbonImmutable::parse('2030-01-10 10:00', 'Asia/Manila')->utc();
        $newStart = CarbonImmutable::parse('2030-01-10 11:00', 'Asia/Manila')->utc();
        $appointment = $this->createAppointment($assignment, $client, $originalStart, $client->timezone);
        $clientLocalProposal = $newStart->setTimezone($client->timezone);

        $this->actingAs($client)
            ->post(route('appointments.proposals.store', $appointment), [
                'preferred_date' => $clientLocalProposal->toDateString(),
                'preferred_time' => $clientLocalProposal->format('H:i'),
                'slot_start_utc' => $newStart->format('Y-m-d\TH:i:s\Z'),
                'timezone' => $client->timezone,
                'message' => 'Please move this to the later slot.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $professional->user_id,
            'title' => 'New appointment time proposal',
        ]);
        $proposalNotification = AppNotification::query()
            ->where('user_id', $professional->user_id)
            ->where('appointment_proposal_id', $appointment->proposals()->value('proposal_id'))
            ->firstOrFail();
        $proposalNotification->update(['url' => route('appointments.index')]);
        $this->actingAs($professional)
            ->get(route('notifications.open', $proposalNotification))
            ->assertRedirect(route('notifications.index', ['selected' => $proposalNotification->notification_id]));

        $this->get(route('notifications.index', ['selected' => $proposalNotification->notification_id]))
            ->assertOk()
            ->assertSee('Appointment details')
            ->assertSee('Reschedule test home')
            ->assertSee('Proposed time')
            ->assertDontSee('Open related page')
            ->assertDontSee('View appointment');

        $this->actingAs($professional)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('New appointment time proposal')
            ->assertSee('Accept new time')
            ->assertSee('Decline');
        $this->actingAs($professional)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertDontSee('Accept new time');

        $proposal = $appointment->proposals()->firstOrFail();
        $this->actingAs($client)
            ->patch(route('appointments.proposals.respond', $proposal), ['decision' => 'Accepted'])
            ->assertForbidden();

        $this->actingAs($professional)
            ->patch(route('appointments.proposals.respond', $proposal), ['decision' => 'Accepted'])
            ->assertRedirect();

        $this->assertTrue($appointment->fresh()->starts_at_utc->equalTo($newStart));
        $this->assertSame('Accepted', $proposal->fresh()->status);
        $this->assertSame($clientLocalProposal->toDateString(), $appointment->fresh()->preferred_date->format('Y-m-d'));
        $this->assertSame($clientLocalProposal->format('H:i'), substr($appointment->fresh()->preferred_time, 0, 5));
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $client->user_id,
            'title' => 'Appointment time proposal accepted',
        ]);
    }

    public function test_professional_time_proposal_is_accepted_by_the_client(): void
    {
        Mail::fake();
        config(['appointments.daily_slots' => ['10:00', '11:00'], 'appointments.slot_capacity' => 1]);
        $professional = User::factory()->create(['role_name' => 'Agent', 'timezone' => 'Asia/Manila']);
        $client = User::factory()->create(['role_name' => 'Client', 'timezone' => 'America/Los_Angeles']);
        [$property, $assignment] = $this->createAssignment($professional, 'Professional proposal home');
        $originalStart = CarbonImmutable::parse('2030-01-10 10:00', 'Asia/Manila')->utc();
        $newStart = CarbonImmutable::parse('2030-01-10 11:00', 'Asia/Manila')->utc();
        $appointment = $this->createAppointment($assignment, $client, $originalStart, $client->timezone);
        $professionalLocalProposal = $newStart->setTimezone($professional->timezone);

        $this->actingAs($professional)
            ->post(route('appointments.proposals.store', $appointment), [
                'preferred_date' => $professionalLocalProposal->toDateString(),
                'preferred_time' => $professionalLocalProposal->format('H:i'),
                'slot_start_utc' => $newStart->format('Y-m-d\TH:i:s\Z'),
                'timezone' => $professional->timezone,
            ])
            ->assertRedirect();

        $proposal = $appointment->proposals()->firstOrFail();
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $client->user_id,
            'title' => 'New appointment time proposal',
        ]);

        $this->actingAs($professional)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('Accept new time');
        $this->actingAs($client)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Accept new time')
            ->assertSee('Decline');

        $this->actingAs($professional)
            ->patch(route('appointments.proposals.respond', $proposal), ['decision' => 'Accepted'])
            ->assertForbidden();

        $this->actingAs($client)
            ->patch(route('appointments.proposals.respond', $proposal), ['decision' => 'Accepted'])
            ->assertRedirect();

        $this->assertTrue($appointment->fresh()->starts_at_utc->equalTo($newStart));
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $professional->user_id,
            'title' => 'Appointment time proposal accepted',
        ]);
    }

    public function test_client_cancellation_notifies_the_professional_not_the_client_actor(): void
    {
        Mail::fake();
        $professional = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        [, $assignment] = $this->createAssignment($professional, 'Cancelled appointment home');
        $appointment = $this->createAppointment($assignment, $client, CarbonImmutable::now('UTC')->addDays(3));

        $this->actingAs($client)
            ->patch(route('appointments.status', $appointment), ['appointment_status' => 'Cancelled'])
            ->assertRedirect();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $professional->user_id,
            'title' => 'Appointment cancelled',
        ]);
        $this->assertDatabaseMissing('app_notifications', [
            'user_id' => $client->user_id,
            'title' => 'Appointment cancelled',
        ]);
    }

    public function test_status_update_is_one_time_and_the_updated_status_stays_locked(): void
    {
        Mail::fake();
        $professional = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        [, $assignment] = $this->createAssignment($professional, 'Locked status home');
        $appointment = $this->createAppointment($assignment, $client, CarbonImmutable::now('UTC')->addDays(3));

        $this->actingAs($professional)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Update status');

        $this->patch(route('appointments.status', $appointment), ['appointment_status' => 'Confirmed'])
            ->assertRedirect();

        $this->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Confirmed')
            ->assertDontSee('Update status')
            ->assertDontSee('(Asia/Manila)');

        $this->patch(route('appointments.status', $appointment), ['appointment_status' => 'Completed'])
            ->assertStatus(409);

        $this->assertSame('Confirmed', $appointment->fresh()->appointment_status);
    }

    public function test_clients_only_see_their_appointments_but_admins_see_all(): void
    {
        $professional = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $otherClient = User::factory()->create(['role_name' => 'Client']);
        [$firstProperty, $firstAssignment] = $this->createAssignment($professional, 'Private client home');
        [$secondProperty, $secondAssignment] = $this->createAssignment($professional, 'Other client home');
        $start = CarbonImmutable::now('UTC')->addDays(4)->setTime(9, 0);
        $this->createAppointment($firstAssignment, $client, $start);
        $this->createAppointment($secondAssignment, $otherClient, $start->addHour());

        $this->actingAs($client)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Private client home')
            ->assertDontSee('Other client home');

        $admin = User::factory()->create(['role_name' => 'Admin']);
        $this->actingAs($admin)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Private client home')
            ->assertSee('Other client home');
    }

    public function test_appointment_master_list_paginates_five_per_page(): void
    {
        $professional = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $start = CarbonImmutable::now('UTC')->addDays(10)->setTime(9, 0);
        $appointments = collect();

        for ($index = 1; $index <= 6; $index++) {
            [, $assignment] = $this->createAssignment($professional, 'Paged appointment ' . $index);
            $appointments->push($this->createAppointment($assignment, $client, $start->addDays($index)));
        }

        $firstPage = $this->actingAs($client)->get(route('appointments.index'))
            ->assertOk()
            ->assertViewHas('appointments', fn ($page) => $page->count() === 5)
            ->assertViewHas('selectedAppointment', fn ($selected) => $selected instanceof Appointment);

        $firstPage->assertSee('Paged appointment 1');

        $secondPage = $this->get(route('appointments.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('appointments', fn ($page) => $page->count() === 1)
            ->assertViewHas('selectedAppointment', fn ($selected) => $selected?->assignment?->property?->property_name === 'Paged appointment 6');

        $secondPage->assertSee('Paged appointment 6');
    }

    public function test_dashboard_calendar_is_personal_for_clients_and_global_for_admins(): void
    {
        $professional = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $otherClient = User::factory()->create(['role_name' => 'Client']);
        [, $firstAssignment] = $this->createAssignment($professional, 'Client calendar home');
        [, $secondAssignment] = $this->createAssignment($professional, 'Global calendar home');
        $start = CarbonImmutable::now('UTC')->addDays(4)->setTime(9, 0);
        $this->createAppointment($firstAssignment, $client, $start);
        $this->createAppointment($secondAssignment, $otherClient, $start->addHour());

        $this->actingAs($client)
            ->get(route('app.page', ['page' => 'dashboard']))
            ->assertOk()
            ->assertViewHas('calendarAppointments', function ($appointments) use ($client, $otherClient): bool {
                $names = collect($appointments)->pluck('contact_name');

                return $names->contains($client->name) && ! $names->contains($otherClient->name);
            });

        $admin = User::factory()->create(['role_name' => 'Admin']);
        $this->actingAs($admin)
            ->get(route('app.page', ['page' => 'dashboard']))
            ->assertOk()
            ->assertViewHas('calendarAppointments', function ($appointments) use ($client, $otherClient): bool {
                $names = collect($appointments)->pluck('contact_name');

                return $names->contains($client->name) && $names->contains($otherClient->name);
            });
    }

    public function test_reminders_are_sent_once_to_the_client_and_professional(): void
    {
        Mail::fake();
        config(['appointments.reminder_offsets' => [60], 'appointments.reminder_window_minutes' => 15]);
        $professional = User::factory()->create(['role_name' => 'Agent', 'timezone' => 'Asia/Manila']);
        $client = User::factory()->create(['role_name' => 'Client', 'timezone' => 'America/Los_Angeles']);
        [, $assignment] = $this->createAssignment($professional, 'Reminder home');
        $appointment = $this->createAppointment($assignment, $client, CarbonImmutable::now('UTC')->addMinutes(65), $client->timezone);
        $appointment->update(['appointment_status' => 'Confirmed']);

        $service = app(AppointmentReminderService::class);
        $this->assertSame(2, $service->sendDue());
        $this->assertSame(0, $service->sendDue());
        $this->assertSame(2, AppNotification::query()->where('title', 'Upcoming appointment')->count());
        $this->assertDatabaseCount('appointment_reminders', 2);
        Mail::assertQueued(ActivityMail::class, 2);
    }

    /** @return array{0: Property, 1: PropertyAssign} */
    private function createAssignment(User $professional, string $name): array
    {
        $property = Property::create([
            'user_id' => $professional->user_id,
            'property_name' => $name,
            'property_description' => 'Appointment scheduling test listing.',
            'property_type' => 'House & Lot',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => 'professional',
        ]);
        $assignment = PropertyAssign::create([
            'user_id' => $professional->user_id,
            'property_id' => $property->property_id,
            'assigned_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);

        return [$property, $assignment];
    }

    private function createAppointment(
        PropertyAssign $assignment,
        User $client,
        CarbonImmutable $startsAtUtc,
        string $clientTimezone = 'Asia/Manila'
    ): Appointment {
        $localStart = $startsAtUtc->setTimezone($clientTimezone);

        return Appointment::create([
            'assign_id' => $assignment->assign_id,
            'client_id' => $client->user_id,
            'contact_name' => $client->name,
            'contact_number' => '555-0100',
            'contact_email' => $client->email,
            'appointment_type' => 'Property viewing',
            'preferred_date' => $localStart->toDateString(),
            'preferred_time' => $localStart->format('H:i:s'),
            'starts_at_utc' => $startsAtUtc->utc()->format('Y-m-d H:i:s'),
            'client_timezone' => $clientTimezone,
        ]);
    }
}