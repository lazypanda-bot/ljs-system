<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppNotification;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\Team;
use App\Models\User;
use App\Services\AppointmentReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BrokerWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function property(User $owner, string $name, string $mode = 'professional'): Property
    {
        return Property::create([
            'user_id' => $owner->user_id,
            'property_name' => $name,
            'property_type' => 'House & Lot',
            'property_description' => 'Test property.',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => $mode,
        ]);
    }

    public function test_only_brokers_can_lead_teams(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $broker = User::factory()->create(['role_name' => 'Broker']);

        $this->actingAs($admin)->post(route('admin.teams.store'), ['user_id' => $agent->user_id, 'team_name' => 'Bad'])
            ->assertSessionHasErrors('user_id');
        $this->post(route('admin.teams.store'), ['user_id' => $broker->user_id, 'team_name' => 'Good', 'member_ids' => [$agent->user_id]])
            ->assertRedirect(route('admin.teams.index'));
        $this->assertDatabaseHas('teams', ['user_id' => $broker->user_id, 'team_name' => 'Good']);
    }

    public function test_broker_sees_team_agents_and_monitors_their_listings_and_appointments_read_only(): void
    {
        $broker = User::factory()->create(['role_name' => 'Broker']);
        $agent = User::factory()->create(['role_name' => 'Agent', 'name' => 'Team Agent']);
        $outsider = User::factory()->create(['role_name' => 'Agent', 'name' => 'Outside Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $team = Team::create(['user_id' => $broker->user_id, 'team_name' => 'T']);
        $team->members()->create(['user_id' => $agent->user_id, 'status' => 'Approved']);
        $agentProperty = $this->property($agent, 'Agent Listing');
        $outsiderProperty = $this->property($outsider, 'Outsider Listing');
        $brokerProperty = $this->property($broker, 'Broker Own Listing');
        $assignment = PropertyAssign::create(['user_id' => $agent->user_id, 'property_id' => $agentProperty->property_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);
        Appointment::create([
            'assign_id' => $assignment->assign_id, 'client_id' => $client->user_id, 'contact_name' => 'Visitor Vee',
            'contact_number' => '555', 'contact_email' => $client->email, 'appointment_type' => 'Property viewing',
            'preferred_date' => now()->addDay()->toDateString(), 'preferred_time' => '10:00',
            'starts_at_utc' => now()->addDay(), 'appointment_status' => 'Pending',
        ]);

        $this->actingAs($broker)->get(route('app.page', ['page' => 'agents']))->assertOk()->assertSee('Team Agent')->assertDontSee('Outside Agent');
        $this->get(route('app.page', ['page' => 'agents']))->assertSee('Agent Listing')->assertDontSee('Outsider Listing')->assertSee('Visitor Vee');
        $this->get(route('appointments.index'))->assertDontSee('Visitor Vee');
        $this->get(route('app.page', ['page' => 'listings']))->assertSee('Broker Own Listing')->assertDontSee('Agent Listing');

        $appointment = Appointment::first();
        $this->patch(route('appointments.status', $appointment), ['appointment_status' => 'Confirmed'])->assertForbidden();
        $this->assertNotNull($outsiderProperty);
    }

    public function test_new_property_types_are_accepted_and_suggested(): void
    {
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $this->property($agent, 'Existing')->update(['property_type' => 'Beach Resort']);

        $this->actingAs($agent)->get(route('admin.subfiles.addproperty'))
            ->assertOk()->assertSee('id="typeChoices"', false)->assertSee('id="addTypeChoice"', false)->assertSee('Beach Resort');
    }

    public function test_upcoming_reminders_catch_up_when_the_scheduler_missed_the_window(): void
    {
        Mail::fake();
        config(['appointments.reminder_offsets' => [1440, 60], 'appointments.reminder_window_minutes' => 15]);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $assignment = PropertyAssign::create(['user_id' => $agent->user_id, 'property_id' => $this->property($agent, 'Soon')->property_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);
        Appointment::create([
            'assign_id' => $assignment->assign_id, 'client_id' => $client->user_id, 'contact_name' => $client->name,
            'contact_number' => '555', 'contact_email' => $client->email, 'appointment_type' => 'Property viewing',
            'preferred_date' => now()->toDateString(), 'preferred_time' => '10:00',
            'starts_at_utc' => now()->addMinutes(30), 'appointment_status' => 'Confirmed',
        ]);

        $this->assertSame(2, app(AppointmentReminderService::class)->sendDue());
        $this->assertSame(1, AppNotification::where('user_id', $client->user_id)->where('title', 'Upcoming appointment')->count());
    }

    public function test_agent_profile_shows_supervising_broker_and_property_page_links_to_the_handler(): void
    {
        $broker = User::factory()->create(['role_name' => 'Broker', 'name' => 'Boss Broker']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $admin = User::factory()->create(['role_name' => 'Admin']);
        Team::create(['user_id' => $broker->user_id, 'team_name' => 'T'])->members()->create(['user_id' => $agent->user_id, 'status' => 'Approved']);

        $this->get(route('agents.show', $agent))->assertOk()->assertSee('Supervising Broker')->assertSee('Boss Broker');
        $this->get(route('agents.show', $broker))->assertOk()->assertDontSee('Supervising Broker');

        $professionalListing = $this->property($agent, 'Pro Listing');
        $this->get(route('properties.show', $professionalListing))->assertSee(route('agents.show', $agent), false);
        $adminListing = $this->property($admin, 'Admin Listing', 'admin');
        $this->get(route('properties.show', $adminListing))->assertDontSee(route('agents.show', $admin), false);
    }

    public function test_listing_appointments_and_messages_are_locked_to_the_lister(): void
    {
        $lister = User::factory()->create(['role_name' => 'Agent']);
        $other = User::factory()->create(['role_name' => 'Broker']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $property = $this->property($lister, 'Locked Listing');
        $otherAssignment = PropertyAssign::create(['user_id' => $other->user_id, 'property_id' => $property->property_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);

        $this->actingAs($client)->get(route('properties.show', $property))
            ->assertOk()->assertDontSee('referralProfessionalSelect', false)
            ->assertSee('id="appointmentBookingForm"', false)
            ->assertSee('<select name="time_period" id="appointmentTimePeriod" required disabled>', false)
            ->assertSee('Morning (8:00 AM–12:00 PM)')
            ->assertSee('Afternoon (1:00 PM–5:00 PM)')
            ->assertDontSee('Any time')
            ->assertSee('name="assign_id" value="' . $property->assignments()->where('user_id', $lister->user_id)->value('assign_id') . '"', false);
        $this->get(route('appointments.create', $property))->assertOk()
            ->assertDontSee('<select name="assign_id"', false)
            ->assertSee('<select name="time_period" id="appointmentTimePeriod" required disabled>', false)
            ->assertSee('Morning (8:00 AM–12:00 PM)')
            ->assertSee('Afternoon (1:00 PM–5:00 PM)')
            ->assertDontSee('Any time');

        $this->post(route('properties.message', $property), ['recipient_id' => $other->user_id])->assertRedirect();
        $this->assertDatabaseHas('conversation_participants', ['user_id' => $lister->user_id]);
        $this->assertDatabaseMissing('conversation_participants', ['user_id' => $other->user_id]);
        $this->assertNotNull($otherAssignment);
    }

    public function test_completed_appointment_prompts_and_allows_a_client_review_shown_to_the_professional_and_admin(): void
    {
        Mail::fake();
        $agent = User::factory()->create(['role_name' => 'Agent', 'name' => 'Rated Agent']);
        $other = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client', 'name' => 'Happy Client']);
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $assignment = PropertyAssign::create(['user_id' => $agent->user_id, 'property_id' => $this->property($agent, 'Reviewed Home')->property_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);
        $appointment = Appointment::create([
            'assign_id' => $assignment->assign_id, 'client_id' => $client->user_id, 'contact_name' => $client->name,
            'contact_number' => '555', 'contact_email' => 'other-email@example.test', 'appointment_type' => 'Property viewing',
            'preferred_date' => now()->toDateString(), 'preferred_time' => '10:00',
            'starts_at_utc' => now()->addHour(), 'appointment_status' => 'Pending',
        ]);

        $this->actingAs($client)->post(route('agents.reviews.store', $agent), ['rating' => 5, 'review' => 'Too early'])->assertForbidden();

        $this->actingAs($agent)->patch(route('appointments.status', $appointment), ['appointment_status' => 'Completed'])->assertRedirect();
        $this->assertDatabaseHas('app_notifications', ['user_id' => $client->user_id, 'title' => 'How was your experience?']);

        $this->actingAs($client)->post(route('agents.reviews.store', $agent), ['rating' => 5, 'review' => 'Wonderful service'])->assertRedirect();
        $this->post(route('agents.reviews.store', $other), ['rating' => 1, 'review' => 'Never met'])->assertForbidden();

        $this->actingAs($agent)->get(route('app.page', ['page' => 'review']))->assertSee('Wonderful service');
        $this->actingAs($admin)->get(route('app.page', ['page' => 'review']))->assertSee('Wonderful service')->assertSee('Rated Agent');
    }

    public function test_agent_rows_link_to_that_agents_listings_and_appointments_and_reports_reviews_are_own_only(): void
    {
        $broker = User::factory()->create(['role_name' => 'Broker']);
        $agent = User::factory()->create(['role_name' => 'Agent', 'name' => 'Row Agent']);
        $other = User::factory()->create(['role_name' => 'Agent', 'name' => 'Other Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $team = Team::create(['user_id' => $broker->user_id, 'team_name' => 'T']);
        $team->members()->createMany([['user_id' => $agent->user_id, 'status' => 'Approved'], ['user_id' => $other->user_id, 'status' => 'Approved']]);
        $this->property($broker, 'Broker Own');
        $this->property($agent, 'Row Agent Home');
        $this->property($other, 'Other Agent Home');

        $page = $this->actingAs($broker)->get(route('app.page', ['page' => 'agents']))->assertOk();
        $page->assertSee('PRC LICENSE')
            ->assertSee('data-agent-modal="listings-' . $agent->user_id . '"', false)
            ->assertSee('data-agent-modal="profile-' . $agent->user_id . '"', false)
            ->assertDontSee('All Agent Listings')->assertDontSee('Filter by agent');
        $page->assertSee('Row Agent Home')->assertSee('Other Agent Home');
        $this->assertStringNotContainsString('Broker Own', $page->getContent());

        $this->get(route('app.page', ['page' => 'report']))->assertOk()
            ->assertSee('data-staff-row="' . $broker->user_id . '"', false)
            ->assertDontSee('data-staff-row="' . $agent->user_id . '"', false)
            ->assertDontSee('Other Agent');

        \App\Models\Review::create(['user_id' => $broker->user_id, 'reviewer_id' => $client->user_id, 'reviewed_user_id' => $broker->user_id, 'rating' => 5, 'review' => 'About the broker']);
        \App\Models\Review::create(['user_id' => $agent->user_id, 'reviewer_id' => $client->user_id, 'reviewed_user_id' => $agent->user_id, 'rating' => 4, 'review' => 'About the agent']);
        $this->get(route('app.page', ['page' => 'review']))->assertSee('About the broker')->assertDontSee('About the agent');
    }

    public function test_sold_reserved_and_unavailable_properties_close_appointments_and_show_a_notice(): void
    {
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $property = $this->property($agent, 'Closed Home');
        $assignment = PropertyAssign::create(['user_id' => $agent->user_id, 'property_id' => $property->property_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);

        foreach (['Sold', 'Reserved', 'Unavailable'] as $status) {
            $property->update(['property_status' => $status]);
            $this->actingAs($client)->get(route('properties.show', $property))
                ->assertOk()->assertSee('Appointments are closed')->assertSee('Booking closed');
            $this->get(route('appointments.create', $property))->assertRedirect(route('properties.show', $property));
            $this->post(route('appointments.store', $property), [
                'contact_name' => 'x', 'contact_number' => '1', 'contact_email' => $client->email, 'appointment_type' => 'Property viewing',
                'preferred_date' => now()->addDay()->toDateString(), 'preferred_time' => '10:00',
            ])->assertSessionHasErrors('appointment_type');
        }

        $property->update(['property_status' => 'For Sale']);
        $this->get(route('properties.show', $property))->assertDontSee('Appointments are closed');

        $this->actingAs($admin)->get(route('app.page', ['page' => 'property']))
            ->assertOk()->assertSee('<option value="Sold">Sold</option>', false)->assertDontSee('<option value="Available">Available</option>', false);
        $this->assertNotNull($assignment);
    }

    public function test_client_cannot_rebook_an_active_property_and_proposals_show_a_popup_and_notify(): void
    {
        $agent = User::factory()->create(['role_name' => 'Agent', 'name' => 'Handler Agent']);
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $property = $this->property($agent, 'Rebook Home');
        $assignment = PropertyAssign::create(['user_id' => $agent->user_id, 'property_id' => $property->property_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);
        $appointment = Appointment::create([
            'assign_id' => $assignment->assign_id, 'client_id' => $client->user_id, 'contact_name' => $client->name,
            'contact_number' => '555', 'contact_email' => $client->email, 'appointment_type' => 'Property viewing',
            'preferred_date' => now()->addDay()->toDateString(), 'preferred_time' => '10:00',
            'starts_at_utc' => now()->addDays(2), 'appointment_status' => 'Pending',
        ]);

        $this->actingAs($client)->get(route('properties.show', $property))->assertOk()->assertSee('Already booked');
        $this->get(route('appointments.create', $property))->assertRedirect(route('properties.show', $property))->assertSessionHas('popup');
        $this->post(route('appointments.store', $property), [
            'contact_name' => 'x', 'contact_number' => '1', 'contact_email' => $client->email, 'appointment_type' => 'Property viewing',
            'preferred_date' => now()->addDays(3)->toDateString(), 'preferred_time' => '10:00',
        ])->assertSessionHasErrors('appointment_type');

        config(['appointments.daily_slots' => ['09:00', '10:00', '11:00'], 'appointments.slot_capacity' => 5]);
        $slots = app(\App\Services\AppointmentSchedulingService::class)->availableSlots($assignment->load('user'), now()->addDays(4)->toDateString(), 'Asia/Manila');
        $this->assertNotEmpty($slots);

        $this->post(route('appointments.proposals.store', $appointment), [
            'preferred_date' => now()->addDays(4)->toDateString(),
            'preferred_time' => $slots[0]['local_time'],
            'slot_start_utc' => $slots[0]['value'],
            'timezone' => 'Asia/Manila',
        ])->assertRedirect()->assertSessionHas('popup.title', 'Proposal sent');
        $this->assertStringContainsString('Handler Agent was notified', session('popup.message'));
        $this->assertDatabaseHas('app_notifications', ['user_id' => $agent->user_id, 'title' => 'New appointment time proposal']);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $admin->user_id, 'title' => 'New appointment time proposal', 'audience' => 'system']);
    }

    public function test_property_details_follow_the_property_type_and_featured_prices_keep_the_peso_sign(): void
    {
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $house = $this->property($agent, 'Typed House');
        $house->details()->create(['lot_area' => 120, 'floor_area' => 80, 'bedroom' => 3, 'bathroom' => 2, 'parking_spaces' => 1, 'number_of_floors' => 2, 'year_built' => 2015, 'property_condition' => 'Good', 'remarks' => 'Corner lot']);
        $lot = $this->property($agent, 'Typed Lot');
        $lot->update(['property_type' => 'Farm Lot', 'featured' => true]);
        $lot->details()->create(['lot_area' => 5000, 'bedroom' => 0, 'bathroom' => 0, 'year_built' => 2001, 'property_condition' => 'Rough']);

        $this->get(route('properties.show', $house))->assertSee('Year built')->assertSee('2015')->assertSee('Condition')->assertSee('3 beds');
        $this->get(route('properties.show', $lot))->assertSee('5,000.00 m² lot')->assertDontSee('Year built')->assertDontSee('beds')->assertDontSee('Rough');

        $this->get(route('home'))->assertSee('₱')->assertDontSee('â‚±', false);
    }

    public function test_agent_directory_shows_the_prc_license_number(): void
    {
        $broker = User::factory()->create(['role_name' => 'Broker']);
        $broker->professionalProfile()->updateOrCreate([], ['license_number' => 'PRC-123456']);

        $this->get(route('agents'))->assertOk()->assertSee('PRC-123456');
    }
}
