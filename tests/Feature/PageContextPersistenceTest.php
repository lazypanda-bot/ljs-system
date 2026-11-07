<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Property;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PageContextPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_status_updates_and_additions_return_to_the_original_list_context(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $returnTo = '/app/users?tab=all-users&page=2';
        $this->actingAs($admin)->put(route('admin.users.update', $agent), ['account_status' => 'Inactive', '_return_to' => $returnTo])
            ->assertHeader('Location', $returnTo);
        $this->post(route('admin.users.store'), [
            'name' => 'Added Agent', 'email' => 'added@context.test', 'password' => 'InitialPass1!',
            'role_name' => 'Agent', 'account_status' => 'Active', '_return_to' => $returnTo,
        ])->assertSessionHasNoErrors()->assertHeader('Location', $returnTo);
    }

    public function test_team_add_and_edit_keep_the_teams_tab_and_user_list_page(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $broker = User::factory()->create(['role_name' => 'Broker']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $returnTo = '/app/users?tab=teams&page=2';
        $this->actingAs($admin)->post(route('admin.teams.store'), [
            'team_name' => 'Page State Team', 'user_id' => $broker->user_id,
            'member_ids' => [$agent->user_id], '_return_to' => $returnTo,
        ])->assertSessionHasNoErrors()->assertHeader('Location', $returnTo);
        $team = Team::where('team_name', 'Page State Team')->firstOrFail();
        $this->put(route('admin.teams.update', $team), [
            'team_name' => 'Updated State Team', 'user_id' => $broker->user_id,
            'member_ids' => [$agent->user_id], '_return_to' => $returnTo,
        ])->assertSessionHasNoErrors()->assertHeader('Location', $returnTo);
        $response = $this->get('/app/users?tab=teams')->assertOk()->assertSee('Updated State Team');
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $this->assertSame(1, (new \DOMXPath($document))->query('//button[@data-tab="teams" and contains(@class,"active")]')->length);
    }

    public function test_property_add_edit_status_and_delete_preserve_the_originating_property_tab(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $this->actingAs($admin);
        $payload = [
            'property_name' => 'Stateful Property', 'property_type' => 'House & Lot', 'description' => 'Property state test.',
            'street' => 'One Street', 'city' => 'Cebu City', 'province' => 'Cebu', 'price' => 5000000,
            'property_status' => 'For Sale', '_return_to' => '/app/property?tab=project-listings',
        ];
        $this->post(route('admin.properties.store'), $payload)->assertSessionHasNoErrors()
            ->assertHeader('Location', $payload['_return_to']);
        $property = Property::where('property_name', 'Stateful Property')->firstOrFail();
        $payload['_return_to'] = '/app/property?tab=history';
        $this->put(route('admin.properties.update', $property), $payload)->assertSessionHasNoErrors()
            ->assertHeader('Location', $payload['_return_to']);
        $this->patch(route('admin.properties.status', $property), ['property_status' => 'Reserved', '_return_to' => $payload['_return_to']])
            ->assertSessionHasNoErrors()->assertHeader('Location', $payload['_return_to']);
        $this->delete(route('admin.properties.destroy', $property), ['_return_to' => $payload['_return_to']])
            ->assertHeader('Location', $payload['_return_to']);
    }

    public function test_appointment_updates_preserve_the_projects_tab_page_search_and_status_filter(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $property = Property::create([
            'user_id' => $admin->user_id, 'property_name' => 'Context Appointment Home', 'property_type' => 'Lot',
            'street' => 'One Street', 'city' => 'Cebu', 'province' => 'Cebu', 'price' => 100,
            'approval_status' => 'Approved', 'property_status' => 'For Sale', 'listing_mode' => 'admin',
        ]);
        $assignment = $property->assignments()->create(['user_id' => $admin->user_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);
        $appointment = Appointment::create([
            'assign_id' => $assignment->assign_id, 'client_id' => $client->user_id, 'contact_name' => $client->name,
            'contact_number' => '09171234567', 'contact_email' => $client->email, 'appointment_type' => 'Property viewing',
            'preferred_date' => now()->addDays(7)->toDateString(), 'preferred_time' => '09:00',
        ]);
        $returnTo = '/app/appointments?tab=projects&status=Pending&page=2&q=Client';
        $this->actingAs($admin)->patch(route('appointments.status', $appointment), ['appointment_status' => 'Confirmed', '_return_to' => $returnTo])
            ->assertSessionHasNoErrors()->assertHeader('Location', $returnTo);
    }

    public function test_return_context_cannot_redirect_to_external_or_unrelated_pages(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $this->actingAs($admin);
        foreach (['https://example.com', '//example.com', '/\\example.com', '/app/property?tab=history', '/admin/users/123/edit'] as $returnTo) {
            $this->put(route('admin.users.update', $agent), ['account_status' => 'Inactive', '_return_to' => $returnTo])
                ->assertRedirect(route('admin.users.index'));
        }
    }

    public function test_deleting_the_last_user_on_a_page_returns_to_the_last_valid_page(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $agents = User::factory()->count(7)->create(['role_name' => 'Agent']);
        $this->actingAs($admin)->deleteJson(route('admin.users.destroy', $agents->last()))->assertOk();
        $response = $this->get('/admin/users?tab=all-users&page=2')->assertRedirect();
        $this->assertSame(url('/admin/users?tab=all-users&page=1'), $response->headers->get('Location'));
        $this->get($response->headers->get('Location'))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->currentPage() === 1 && $users->count() === 7);
    }
}
