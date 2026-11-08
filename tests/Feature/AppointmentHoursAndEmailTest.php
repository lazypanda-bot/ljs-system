<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\ActivityMail;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\User;
use App\Services\AppointmentSchedulingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppointmentHoursAndEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_slots_cover_both_working_periods_and_reject_lunch_and_closing_time(): void
    {
        [, , $assignment] = $this->listing();
        $date = now('Asia/Manila')->addDays(7)->toDateString();
        $service = app(AppointmentSchedulingService::class);
        $slots = $service->availableSlots($assignment, $date, 'Asia/Manila');

        $this->assertSame(['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'], array_column($slots, 'local_time'));
        $this->assertSame('8:00 AM – 9:00 AM', $slots[0]['label']);
        $this->assertSame('11:00 AM – 12:00 PM', $slots[3]['label']);
        $this->assertSame('1:00 PM – 2:00 PM', $slots[4]['label']);
        $this->assertSame('4:00 PM – 5:00 PM', $slots[7]['label']);
        foreach (['12:00', '17:00'] as $time) {
            try {
                $service->assertAvailable($assignment, CarbonImmutable::parse($date.' '.$time, 'Asia/Manila')->utc());
                $this->fail('A lunch or closing-time booking must be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('slot_start_utc', $exception->errors());
            }
        }
    }

    public function test_all_staff_modes_can_block_working_intervals_or_whole_days(): void
    {
        Mail::fake();
        foreach ([['Agent', 'agent'], ['Broker', 'broker'], ['Admin', 'admin'], ['Lead Broker', 'broker'], ['Lead Broker', 'admin']] as [$role, $mode]) {
            [$owner, , $assignment] = $this->listing($role);
            $date = now('Asia/Manila')->addDays(7)->toDateString();
            $this->actingAs($owner)->withSession(['acting_as' => $mode]);
            $this->post(route('appointments.block-date'), ['date' => $date, 'time_slot' => 'morning'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $slots = app(AppointmentSchedulingService::class)->daySlots($assignment, $date, 'Asia/Manila');
            $this->assertFalse(collect($slots)->firstWhere('local_time', '08:00')['available']);
            $this->assertFalse(collect($slots)->firstWhere('local_time', '11:00')['available']);
            $this->assertTrue(collect($slots)->firstWhere('local_time', '13:00')['available']);

            $afternoon = now('Asia/Manila')->addDays(8)->toDateString();
            $this->post(route('appointments.block-date'), ['date' => $afternoon, 'time_slot' => 'afternoon'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $afternoonSlots = app(AppointmentSchedulingService::class)->daySlots($assignment, $afternoon, 'Asia/Manila');
            $this->assertTrue(collect($afternoonSlots)->firstWhere('local_time', '11:00')['available']);
            $this->assertFalse(collect($afternoonSlots)->firstWhere('local_time', '13:00')['available']);
            $this->assertFalse(collect($afternoonSlots)->firstWhere('local_time', '16:00')['available']);

            $wholeDay = now('Asia/Manila')->addDays(9)->toDateString();
            $this->post(route('appointments.block-date'), ['date' => $wholeDay, 'time_slot' => 'whole_day'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame([], app(AppointmentSchedulingService::class)->availableSlots($assignment, $wholeDay, 'Asia/Manila'));
            $this->get(route('app.page', ['page' => 'dashboard']))->assertOk()
                ->assertSee('<select name="time_slot" required>', false)
                ->assertSee('Morning (8:00 AM–12:00 PM)')
                ->assertSee('Afternoon (1:00 PM–5:00 PM)')
                ->assertDontSee('<select name="start_time">', false)->assertDontSee('<select name="end_time">', false)
                ->assertSee('Lunch break: 12:00 PM–1:00 PM');
        }
    }

    public function test_invalid_or_incomplete_time_blocks_are_rejected_and_clients_cannot_block(): void
    {
        Mail::fake();
        [$owner, $client] = $this->listing();
        $date = now()->addDays(7)->toDateString();
        $this->actingAs($owner);
        foreach ([['12:00', '13:00'], ['07:00', '08:00'], ['17:00', '18:00'], ['08:00', null], [null, '09:00'], ['14:00', '12:00']] as [$start, $end]) {
            $this->postJson(route('appointments.block-date'), ['date' => $date, 'start_time' => $start, 'end_time' => $end])
                ->assertUnprocessable();
        }
        $this->assertDatabaseCount('blocked_dates', 0);
        $this->actingAs($client)->postJson(route('appointments.block-date'), ['date' => $date])->assertForbidden();
    }

    public function test_confirmation_email_contains_viewing_details_and_contacts_without_a_website_link(): void
    {
        Mail::fake();
        [$owner, $client, $assignment] = $this->listing();
        $appointment = $this->appointment($assignment, $client);
        $this->actingAs($owner)->patch(route('appointments.status', $appointment), ['appointment_status' => 'Confirmed'])
            ->assertRedirect()->assertSessionHasNoErrors();
        Mail::assertQueued(ActivityMail::class, fn (ActivityMail $mail) => $mail->hasTo($client->email)
            && $mail->subjectLine === 'Appointment update: Confirmed'
            && $this->hasConfirmationDetails($mail->messageBody, $owner, $appointment));
    }

    public function test_accepted_reschedule_email_also_includes_the_confirmed_details(): void
    {
        Mail::fake();
        [$owner, $client, $assignment] = $this->listing();
        $appointment = $this->appointment($assignment, $client);
        $newTime = $appointment->starts_at_utc->setTimezone('Asia/Manila')->setTime(13, 0);
        $proposal = $appointment->proposals()->create([
            'proposed_by_user_id' => $client->user_id, 'starts_at_utc' => $newTime->utc(),
            'preferred_date' => $newTime->toDateString(), 'preferred_time' => '13:00',
            'timezone' => 'Asia/Manila', 'status' => 'Pending',
        ]);
        $this->actingAs($owner)->patch(route('appointments.proposals.respond', $proposal), ['decision' => 'Accepted'])
            ->assertRedirect()->assertSessionHasNoErrors();
        Mail::assertQueued(ActivityMail::class, fn (ActivityMail $mail) => $mail->hasTo($client->email)
            && str_contains($mail->messageBody, '1:00 PM – 2:00 PM')
            && $this->hasConfirmationDetails($mail->messageBody, $owner, $appointment->fresh()));
    }

    public function test_repeated_chat_messages_keep_in_app_notifications_without_sending_emails(): void
    {
        Mail::fake();
        foreach (['Client', 'Agent', 'Broker', 'Admin', 'Lead Broker'] as $role) {
            $sender = User::factory()->create(['role_name' => $role]);
            $recipient = User::factory()->create(['role_name' => 'Agent']);
            $conversation = Conversation::create(['type' => 'direct', 'created_by' => $sender->user_id]);
            $conversation->participants()->createMany([['user_id' => $sender->user_id], ['user_id' => $recipient->user_id]]);
            $this->actingAs($sender);
            for ($index = 1; $index <= 3; $index++) {
                $this->post(route('messages.store', $conversation), ['body' => 'Viewing question '.$index])
                    ->assertRedirect()->assertSessionHasNoErrors();
            }
            $this->assertSame(3, ConversationMessage::where('conversation_id', $conversation->conversation_id)->count());
            $this->assertSame(3, AppNotification::where('user_id', $recipient->user_id)->where('type', 'message')->count());
        }
        Mail::assertNothingOutgoing();
    }

    public function test_full_dates_close_automatically_across_listings_and_reopen_after_cancellation(): void
    {
        Mail::fake();
        [$owner, $client, $assignment] = $this->listing();
        $otherProperty = $assignment->property->replicate();
        $otherProperty->property_name = 'Another home handled by the same professional';
        $otherProperty->save();
        $otherAssignment = $otherProperty->assignments()->create([
            'user_id' => $owner->user_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved',
        ]);
        $date = CarbonImmutable::now('Asia/Manila')->addDays(7)->toDateString();
        foreach (config('appointments.daily_slots') as $index => $time) {
            $booking = $this->appointment($index % 2 ? $otherAssignment : $assignment, $client);
            $booking->update(['starts_at_utc' => CarbonImmutable::parse($date.' '.$time, 'Asia/Manila')->utc(), 'preferred_time' => $time]);
        }

        $parameters = ['assign_id' => $assignment->assign_id, 'date' => $date, 'timezone' => 'Asia/Manila', 'with_dates' => 1];
        $response = $this->actingAs($client)->getJson(route('appointments.availability', ['property' => $assignment->property_id, ...$parameters]))
            ->assertOk()->assertJsonPath('slots', []);
        $this->assertContains($date, $response->json('unavailable_dates'));
        $this->assertFalse(collect($response->json('all_slots'))->contains('available', true));
        $this->assertDatabaseCount('blocked_dates', 0);

        $this->actingAs($owner)->patch(route('appointments.status', $booking), [
            'appointment_status' => 'Cancelled', 'cancellation_reason' => 'Client cannot attend.',
        ])->assertSessionHasNoErrors();
        $response = $this->actingAs($client)->getJson(route('appointments.availability', ['property' => $assignment->property_id, ...$parameters]))
            ->assertOk()->assertJsonCount(1, 'slots')->assertJsonPath('slots.0.local_time', '16:00');
        $this->assertNotContains($date, $response->json('unavailable_dates'));
        $this->assertDatabaseCount('blocked_dates', 0);
    }

    public function test_a_date_only_closes_when_all_slots_reach_the_configured_capacity(): void
    {
        config(['appointments.slot_capacity' => 2]);
        [, $client, $assignment] = $this->listing();
        $date = CarbonImmutable::now('Asia/Manila')->addDays(7)->toDateString();
        $service = app(AppointmentSchedulingService::class);
        for ($capacity = 1; $capacity <= 2; $capacity++) {
            foreach (config('appointments.daily_slots') as $time) {
                $booking = $this->appointment($assignment, $client);
                $booking->update(['starts_at_utc' => CarbonImmutable::parse($date.' '.$time, 'Asia/Manila')->utc(), 'preferred_time' => $time]);
            }
            $closed = $service->unavailableDates($assignment, 'Asia/Manila', null, 10);
            if ($capacity === 1) {
                $this->assertNotContains($date, $closed);
                $this->assertCount(8, $service->availableSlots($assignment, $date, 'Asia/Manila'));
            } else {
                $this->assertContains($date, $closed);
                $this->assertSame([], $service->availableSlots($assignment, $date, 'Asia/Manila'));
            }
        }
    }

    public function test_manual_personal_blocks_remain_closed_without_any_bookings(): void
    {
        Mail::fake();
        [$owner, , $assignment] = $this->listing();
        $date = CarbonImmutable::now('Asia/Manila')->addDays(7)->toDateString();
        $this->actingAs($owner)->post(route('appointments.block-date'), ['date' => $date, 'reason' => 'Personal leave'])
            ->assertSessionHasNoErrors();
        $this->assertContains($date, app(AppointmentSchedulingService::class)->unavailableDates($assignment, 'Asia/Manila', null, 10));
        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('blocked_dates', 1);
    }

    private function hasConfirmationDetails(string $body, User $owner, Appointment $appointment): bool
    {
        foreach (['Property: Schedule Policy Home', 'Property type: House & Lot', 'One Street, Cebu City, Cebu',
            'Status: Confirmed', 'Appointment date:', 'Appointment time:', 'Asia/Manila',
            'Client contact number: 09171234567', 'Handled by: '.$owner->name,
            'Professional contact number: 09181234567'] as $detail) {
            if (! str_contains($body, $detail)) {
                return false;
            }
        }

        return str_contains($body, $appointment->startInTimezone('Asia/Manila')->format('M j, Y'))
            && ! preg_match('~https?://|<a\b~i', $body);
    }

    private function listing(string $role = 'Agent'): array
    {
        $owner = User::factory()->create(['role_name' => $role, 'timezone' => 'Asia/Manila', 'contact_number' => '09181234567']);
        $client = User::factory()->create(['role_name' => 'Client', 'timezone' => 'Asia/Manila']);
        $property = Property::create([
            'user_id' => $owner->user_id, 'property_name' => 'Schedule Policy Home', 'property_type' => 'House & Lot',
            'property_description' => 'A property viewing.', 'street' => 'One Street', 'city' => 'Cebu City', 'province' => 'Cebu',
            'price' => 5000000, 'property_status' => 'For Sale', 'approval_status' => 'Approved',
            'listing_mode' => $role === 'Admin' ? 'admin' : 'professional',
        ]);
        $assignment = PropertyAssign::create(['user_id' => $owner->user_id, 'property_id' => $property->property_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);

        return [$owner, $client, $assignment->load('user', 'property')];
    }

    private function appointment(PropertyAssign $assignment, User $client): Appointment
    {
        $start = CarbonImmutable::now('Asia/Manila')->addDays(7)->setTime(9, 0);

        return Appointment::create([
            'assign_id' => $assignment->assign_id, 'client_id' => $client->user_id,
            'contact_name' => $client->name, 'contact_number' => '09171234567', 'contact_email' => $client->email,
            'appointment_type' => 'Property viewing', 'preferred_date' => $start->toDateString(),
            'preferred_time' => '09:00', 'starts_at_utc' => $start->utc(), 'client_timezone' => 'Asia/Manila',
        ]);
    }
}
