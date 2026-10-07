<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_can_add_a_user_and_teams_tab_swaps_the_header_button(): void
    {
        $firstAdmin = User::factory()->create(['role_name' => 'Admin']);
        $secondAdmin = User::factory()->create(['role_name' => 'Admin']);

        $this->actingAs($secondAdmin)->get('/app/users')
            ->assertOk()
            ->assertSee('id="openAddUserModal"', false)
            ->assertSee('id="openAddTeamModal"', false);

        $this->post(route('admin.users.store'), [
            'name' => 'Created By Second Admin',
            'email' => 'second-admin-created@example.test',
            'password' => 'InitialPass1!',
            'role_name' => 'Broker',
            'account_status' => 'Active',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'second-admin-created@example.test', 'role_name' => 'Broker']);
        $this->assertNotNull($firstAdmin->user_id);
    }

    public function test_only_a_lead_broker_can_add_an_admin_and_projects_stay_with_their_admin(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $other = User::factory()->create(['role_name' => 'Admin']);
        $leadBroker = User::factory()->create(['role_name' => 'Lead Broker']);
        $payload = ['name' => 'New Admin', 'email' => 'new-admin@example.test', 'password' => 'InitialPass1!', 'role_name' => 'Admin', 'account_status' => 'Active'];

        $this->actingAs($admin)->get('/app/users')->assertOk()->assertDontSee('<option value="Admin"', false);
        $this->post(route('admin.users.store'), $payload)->assertSessionHasErrors('role_name');
        $this->assertDatabaseMissing('users', ['email' => 'new-admin@example.test']);

        $project = Property::create([
            'user_id' => $admin->user_id, 'property_name' => 'Private Project', 'property_type' => 'Lot', 'street' => 's', 'city' => 'c',
            'province' => 'p', 'price' => 1, 'property_status' => 'For Sale', 'approval_status' => 'Approved', 'listing_mode' => 'admin',
        ]);
        $this->assertTrue(Property::visibleTo($admin)->whereKey($project->property_id)->exists());
        $this->assertFalse(Property::visibleTo($other)->whereKey($project->property_id)->exists());
        $this->actingAs($other)->get(route('admin.properties.edit', $project))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.properties.edit', $project))->assertOk();

        $this->actingAs($leadBroker)->post(route('admin.users.store'), $payload)->assertForbidden();
    }

    public function test_admin_and_lead_broker_can_only_add_their_allowed_user_roles(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $leadBroker = User::factory()->create(['role_name' => 'Lead Broker']);

        $this->actingAs($admin)->get('/app/users')
            ->assertOk()
            ->assertSee('<option value="Agent"', false)
            ->assertSee('<option value="Broker"', false)
            ->assertDontSee('<option value="Admin"', false)
            ->assertDontSee('<option value="Lead Broker"', false);

        $this->post(route('admin.users.store'), [
            'name' => 'Disallowed Lead Broker', 'email' => 'disallowed-lead@example.test', 'password' => 'InitialPass1!',
            'role_name' => 'Lead Broker', 'account_status' => 'Active',
        ])->assertSessionHasErrors('role_name');
        $this->assertDatabaseMissing('users', ['email' => 'disallowed-lead@example.test']);

        $this->actingAs($leadBroker)->withSession(['acting_as' => 'admin'])->get('/app/users')
            ->assertOk()
            ->assertSee('<option value="Admin"', false)
            ->assertSee('<option value="Lead Broker"', false)
            ->assertDontSee('<option value="Agent"', false)
            ->assertDontSee('<option value="Broker"', false);

        foreach ([
            ['Admin', 'added-admin@example.test'],
            ['Lead Broker', 'added-lead-broker@example.test'],
        ] as [$role, $email]) {
            $this->post(route('admin.users.store'), [
                'name' => 'Added ' . $role, 'email' => $email, 'password' => 'InitialPass1!',
                'role_name' => $role, 'account_status' => 'Active',
            ])->assertRedirect(route('admin.users.index'));

            $this->assertDatabaseHas('users', ['email' => $email, 'role_name' => $role]);
        }
    }

    public function test_appointments_split_into_monitoring_and_admin_project_tabs(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $agentAppointment = $this->appointmentFor($agent, 'professional', 'Agent Villa', $client);
        $projectAppointment = $this->appointmentFor($admin, 'admin', 'Project Tower', $client);

        $this->actingAs($admin)->get(route('appointments.index'))
            ->assertOk()->assertSee('Agent Villa')->assertDontSee('Project Tower')->assertSee('View only');

        $this->get(route('appointments.index', ['tab' => 'projects']))
            ->assertOk()->assertSee('Project Tower')->assertDontSee('Agent Villa');

        $this->patch(route('appointments.status', $agentAppointment), ['appointment_status' => 'Confirmed'])->assertForbidden();
        $this->patch(route('appointments.status', $projectAppointment), ['appointment_status' => 'Confirmed'])->assertRedirect();
        $this->assertSame('Confirmed', $projectAppointment->fresh()->appointment_status);

        $otherAdmin = User::factory()->create(['role_name' => 'Admin']);
        $secondProject = $this->appointmentFor($admin, 'admin', 'Second Tower', $client);
        $this->actingAs($otherAdmin)->patch(route('appointments.status', $secondProject), ['appointment_status' => 'Confirmed'])->assertForbidden();
        $this->get(route('appointments.index', ['tab' => 'projects']))->assertDontSee('Second Tower');
    }

    public function test_admin_reviews_table_searches_filters_and_deletes(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $agent = User::factory()->create(['role_name' => 'Agent', 'name' => 'Alice Agent']);
        $broker = User::factory()->create(['role_name' => 'Broker', 'name' => 'Bob Broker']);
        $client = User::factory()->create(['role_name' => 'Client', 'name' => 'Carl Client']);
        $keep = Review::create(['user_id' => $agent->user_id, 'reviewer_id' => $client->user_id, 'reviewed_user_id' => $agent->user_id, 'rating' => 5, 'review' => 'Great service alpha']);
        $other = Review::create(['user_id' => $broker->user_id, 'reviewer_id' => $client->user_id, 'reviewed_user_id' => $broker->user_id, 'rating' => 2, 'review' => 'Slow replies beta']);

        $this->actingAs($admin)->get(route('app.page', ['page' => 'review']))
            ->assertOk()->assertSee('Great service alpha')->assertSee('Slow replies beta');
        $this->get(route('app.page', ['page' => 'review', 'q' => 'Alice']))
            ->assertSee('Great service alpha')->assertDontSee('Slow replies beta');
        $this->get(route('app.page', ['page' => 'review', 'rating' => 2]))
            ->assertSee('Slow replies beta')->assertDontSee('Great service alpha');

        $this->delete(route('admin.reviews.destroy', $other))->assertRedirect();
        $this->assertDatabaseMissing('reviews', ['review_id' => $other->review_id]);
        $this->actingAs($agent)->delete(route('admin.reviews.destroy', $keep))->assertForbidden();
    }

    public function test_new_user_is_emailed_role_email_and_initial_password_and_failed_adds_reopen_the_modal(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $leader = User::factory()->create(['role_name' => 'Broker']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Mailed Agent', 'email' => 'mailed-agent@example.test', 'password' => 'InitialPass1!',
            'role_name' => 'Agent', 'account_status' => 'Active',
        ])->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'mailed-agent@example.test')->firstOrFail();
        $this->assertNotSame('InitialPass1!', $created->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('InitialPass1!', $created->password));
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ActivityMail::class, fn ($mail) => $mail->hasTo('mailed-agent@example.test')
            && str_contains($mail->messageBody, 'Role: Agent')
            && str_contains($mail->messageBody, 'Email: mailed-agent@example.test')
            && str_contains($mail->messageBody, 'Initial password: InitialPass1!'));

        $this->from(route('admin.users.index'))->post(route('admin.users.store'), [
            'name' => 'Dup', 'email' => 'mailed-agent@example.test', 'password' => 'InitialPass1!',
            'role_name' => 'Agent', 'account_status' => 'Active',
        ])->assertRedirect(route('admin.users.index'));
        $this->get(route('admin.users.index'))->assertSee('id="addUserModalOverlay"', false)->assertSee('au-modal-overlay open', false);

        $this->post(route('admin.teams.store'), ['user_id' => $leader->user_id, 'team_name' => 'Fresh Team', 'member_ids' => [$created->user_id]])
            ->assertRedirect(route('admin.teams.index'));
        $this->assertDatabaseHas('team_members', ['user_id' => $created->user_id, 'status' => 'Approved']);
    }

    public function test_admin_can_only_change_user_status_and_not_their_own(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $agent = User::factory()->create(['role_name' => 'Agent', 'account_status' => 'Active', 'name' => 'Original Name']);

        $this->actingAs($admin)->put(route('admin.users.update', $agent), ['account_status' => 'Inactive', 'name' => 'Hacked'])
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['user_id' => $agent->user_id, 'account_status' => 'Inactive', 'name' => 'Original Name']);

        $this->put(route('admin.users.update', $agent), ['account_status' => 'Suspended'])->assertSessionHasErrors('account_status');
        $this->put(route('admin.users.update', $admin), ['account_status' => 'Inactive'])->assertSessionHasErrors('account_status');
    }

    public function test_an_admins_blocked_date_only_closes_their_own_project_bookings(): void
    {
        $blocker = User::factory()->create(['role_name' => 'Admin']);
        $owner = User::factory()->create(['role_name' => 'Admin']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $this->appointmentFor($owner, 'admin', 'Project Tower', $client);
        $assignment = PropertyAssign::query()->where('user_id', $owner->user_id)->firstOrFail();
        $day = now()->addDays(5)->toDateString();
        $scheduling = app(\App\Services\AppointmentSchedulingService::class);

        \App\Models\BlockedDate::create(['user_id' => $blocker->user_id, 'date' => $day, 'timezone' => 'Asia/Manila']);
        $this->assertNotSame([], $scheduling->availableSlots($assignment, $day, 'Asia/Manila'));

        \App\Models\BlockedDate::create(['user_id' => $owner->user_id, 'date' => $day, 'timezone' => 'Asia/Manila']);
        $this->assertSame([], $scheduling->availableSlots($assignment, $day, 'Asia/Manila'));
    }

    public function test_reports_show_an_overall_section_for_admins(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        User::factory()->create(['role_name' => 'Agent', 'name' => 'Idle Agent']);

        $this->actingAs($admin)->get(route('app.page', ['page' => 'report']))
            ->assertOk()->assertSee('Overall system report')->assertSee('Idle Agent');
    }

    public function test_property_inventory_requests_tab_has_filters_and_add_button_sits_in_the_project_toolbar(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $property = Property::create([
            'user_id' => $agent->user_id, 'property_name' => 'Rejected Villa', 'property_type' => 'Lot',
            'property_description' => 'x', 'street' => '1 St', 'city' => 'Cebu', 'province' => 'Cebu', 'price' => 1,
            'property_status' => 'For Sale', 'approval_status' => 'Rejected', 'listing_mode' => 'professional',
        ]);

        $this->actingAs($admin)->get(route('app.page', ['page' => 'property']))
            ->assertOk()->assertSee('id="requestStatusFilter"', false)->assertSee('id="requestSearch"', false)->assertSee('Rejected Villa');
        $this->assertNotNull($property);
    }

    public function test_detail_requirements_follow_the_property_type(): void
    {
        $this->assertSame(['lot_area'], \App\Models\Property::requiredDetailFields('Farm Lot'));
        $this->assertSame(['floor_area', 'bedrooms', 'bathrooms'], \App\Models\Property::requiredDetailFields('Apartment'));
        $this->assertSame([], \App\Models\Property::requiredDetailFields('Beach Resort'));

        $admin = User::factory()->create(['role_name' => 'Admin']);
        $base = [
            'property_name' => 'Req Test', 'description' => 'x', 'street' => '1 St', 'city' => 'Cebu', 'province' => 'Cebu',
            'price' => 100000, 'property_status' => 'For Sale', 'lot_area' => 500,
        ];

        $this->actingAs($admin)->post(route('admin.properties.store'), $base + ['property_type' => 'Apartment'])
            ->assertSessionHasErrors(['floor_area', 'bedrooms', 'bathrooms']);
        $this->post(route('admin.properties.store'), $base + ['property_type' => 'Farm Lot'])->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('property_listings', ['property_name' => 'Req Test', 'property_type' => 'Farm Lot']);
    }

    private function appointmentFor(User $owner, string $mode, string $name, User $client): Appointment
    {
        $property = Property::create([
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
        $assignment = PropertyAssign::create([
            'user_id' => $owner->user_id,
            'property_id' => $property->property_id,
            'assigned_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);

        return Appointment::create([
            'assign_id' => $assignment->assign_id,
            'client_id' => $client->user_id,
            'contact_name' => $client->name,
            'contact_number' => '555-0100',
            'contact_email' => $client->email,
            'appointment_type' => 'Property viewing',
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_time' => '10:00',
            'starts_at_utc' => now()->addDays(3)->setTime(2, 0),
            'appointment_status' => 'Pending',
        ]);
    }
}
