<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleScopedNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function note(User $user, array $overrides = []): AppNotification
    {
        return AppNotification::create(array_merge([
            'user_id' => $user->user_id,
            'type' => 'listing',
            'title' => 'Generic',
            'body' => 'Body',
        ], $overrides));
    }

    public function test_users_only_count_and_see_their_own_notifications(): void
    {
        $clientA = User::factory()->create(['role_name' => 'Client']);
        $clientB = User::factory()->create(['role_name' => 'Client']);
        $this->note($clientA, ['title' => 'Only for A']);
        $this->note($clientB, ['title' => 'Only for B']);

        $this->actingAs($clientA)->get(route('notifications.count'))->assertJson(['count' => 1]);
        $this->get(route('notifications.index'))->assertSee('Only for A')->assertDontSee('Only for B');

        $foreign = AppNotification::where('user_id', $clientB->user_id)->first();
        $this->get(route('notifications.open', $foreign))->assertForbidden();
    }

    public function test_lead_broker_feed_is_separated_by_mode(): void
    {
        $lead = User::factory()->create(['role_name' => 'Lead Broker']);
        $this->note($lead, ['title' => 'Personal appointment']);
        $this->note($lead, ['title' => 'Oversight event', 'audience' => 'system']);
        $this->note($lead, ['title' => 'Inbox message', 'type' => 'message']);

        $this->actingAs($lead)->withSession(['acting_as' => 'broker'])
            ->get(route('notifications.index'))
            ->assertSee('Personal appointment')
            ->assertSee('Inbox message')
            ->assertDontSee('Oversight event');
        $this->get(route('notifications.count'))->assertJson(['count' => 2]);

        $this->withSession(['acting_as' => 'admin'])
            ->get(route('notifications.index'))
            ->assertSee('Oversight event')
            ->assertSee('Inbox message')
            ->assertDontSee('Personal appointment');
        $this->get(route('notifications.count'))->assertJson(['count' => 2]);
    }

    public function test_lead_broker_toggle_redirects_to_a_page_available_in_both_modes(): void
    {
        $lead = User::factory()->create(['role_name' => 'Lead Broker']);

        $response = $this->actingAs($lead)
            ->withSession(['acting_as' => 'broker'])
            ->from(route('app.page', ['page' => 'agents']))
            ->post(route('lead.toggle'));

        $response->assertRedirect(route('app.page', ['page' => 'dashboard']));
        $this->assertSame('admin', session('acting_as'));
        $this->get(route('app.page', ['page' => 'dashboard']))->assertOk();
    }

    public function test_new_live_listing_notifies_clients_staff_and_admin_oversight_but_not_the_lister(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $lead = User::factory()->create(['role_name' => 'Lead Broker']);
        $property = Property::create([
            'user_id' => $agent->user_id,
            'property_name' => 'Live Listing',
            'property_type' => 'House & Lot',
            'property_description' => 'A property.',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Pending',
            'listing_mode' => 'professional',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.properties.review', $property), ['approval_status' => 'Approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('app_notifications', ['user_id' => $client->user_id, 'title' => 'New property listing', 'audience' => 'personal']);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $lead->user_id, 'title' => 'New property listing', 'audience' => 'personal']);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $lead->user_id, 'title' => 'New property listing', 'audience' => 'system']);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $admin->user_id, 'title' => 'New property listing']);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $agent->user_id, 'title' => 'New property listing']);
    }
}
