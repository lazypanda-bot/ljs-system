<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Property;
use App\Models\Team;
use App\Models\TeamMember;
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

    public function test_notifications_pagination_omits_the_result_summary(): void
    {
        $user = User::factory()->create(['role_name' => 'Agent']);
        for ($index = 1; $index <= 100; $index++) {
            $this->note($user, ['title' => 'Notification ' . $index]);
        }

        $this->actingAs($user)->get(route('notifications.index', ['page' => 10]))
            ->assertOk()
            ->assertSee('class="ljs-pagination notification-pagination"', false)
            ->assertSee('aria-label="Next page"', false)
            ->assertSee('class="is-current pagination-desktop-only" aria-current="page">10</span>', false)
            ->assertSee('class="is-current pagination-mobile-only" aria-current="page">10</span>', false)
            ->assertSee('aria-label="Go to page 9"', false)
            ->assertDontSee('>15</a>', false)
            ->assertDontSee('Showing');
    }

    public function test_notification_list_only_pagination_does_not_mark_items_read(): void
    {
        $user = User::factory()->create(['role_name' => 'Agent']);
        for ($index = 1; $index <= 70; $index++) {
            $this->note($user, ['title' => 'Ajax notification ' . $index]);
        }

        $pageTwoNotification = AppNotification::query()
            ->where('user_id', $user->user_id)
            ->where('title', 'Ajax notification 2')
            ->firstOrFail();
        $selectedNotification = AppNotification::query()
            ->where('user_id', $user->user_id)
            ->where('title', 'Ajax notification 70')
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->getJson(route('notifications.index', [
                'selected' => $selectedNotification->notification_id,
                'page' => 8,
                'list_only' => 1,
            ]))
            ->assertOk()
            ->assertJsonStructure(['html']);

        $this->assertStringContainsString('Ajax notification 2', $response->json('html'));
        $this->assertNull($pageTwoNotification->fresh()->read_at);
    }

    public function test_notification_detail_does_not_render_open_related_item_action(): void
    {
        $user = User::factory()->create(['role_name' => 'Agent']);
        $notification = $this->note($user, [
            'title' => 'Property listing notice',
            'url' => route('properties'),
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index', ['selected' => $notification->notification_id]))
            ->assertOk()
            ->assertSee('Property listing notice')
            ->assertDontSee('Open related item');
    }

    public function test_broker_sees_team_and_own_notifications_but_not_system_events(): void
    {
        $broker = User::factory()->create(['role_name' => 'Broker']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $team = Team::create(['user_id' => $broker->user_id, 'team_name' => 'Alpha Team']);
        TeamMember::create(['team_id' => $team->team_id, 'user_id' => $agent->user_id, 'status' => 'Approved']);

        $this->note($broker, ['title' => 'Broker own listing']);
        $this->note($agent, ['title' => 'Team agent listing']);
        $this->note($broker, ['title' => 'System oversight', 'audience' => 'system']);

        $this->actingAs($broker)
            ->get(route('notifications.index'))
            ->assertSee('Broker own listing')
            ->assertSee('Team agent listing')
            ->assertDontSee('System oversight');

        $this->get(route('notifications.count'))->assertJson(['count' => 2]);
    }

    public function test_agent_only_sees_own_notifications(): void
    {
        $agentA = User::factory()->create(['role_name' => 'Agent']);
        $agentB = User::factory()->create(['role_name' => 'Agent']);

        $this->note($agentA, ['title' => 'Agent own item']);
        $this->note($agentB, ['title' => 'Other agent item']);

        $this->actingAs($agentA)
            ->get(route('notifications.index'))
            ->assertSee('Agent own item')
            ->assertDontSee('Other agent item');

        $this->get(route('notifications.count'))->assertJson(['count' => 1]);
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
