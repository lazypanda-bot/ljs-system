<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientNotificationSplitTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_unread_notification_counts_are_split_by_message_type(): void
    {
        $client = User::factory()->create(['role_name' => 'Client']);
        AppNotification::create(['user_id' => $client->user_id, 'type' => 'message', 'title' => 'New message', 'body' => 'Hello']);
        AppNotification::create(['user_id' => $client->user_id, 'type' => 'message', 'title' => 'Another message', 'body' => 'Hi']);
        AppNotification::create(['user_id' => $client->user_id, 'type' => 'listing', 'title' => 'Listing update', 'body' => 'Approved']);

        $this->actingAs($client)->get(route('notifications.count', ['type' => 'message']))
            ->assertOk()->assertJson(['count' => 2]);
        $this->get(route('notifications.count', ['type' => 'other']))
            ->assertOk()->assertJson(['count' => 1]);
        $this->get(route('notifications.count'))
            ->assertOk()->assertJson(['count' => 3]);
    }

    public function test_client_layout_opens_messages_in_modal_and_keeps_other_notifications_on_bell(): void
    {
        $client = User::factory()->create(['role_name' => 'Client']);
        AppNotification::create(['user_id' => $client->user_id, 'type' => 'message', 'title' => 'Message notice', 'body' => 'A client message']);
        AppNotification::create(['user_id' => $client->user_id, 'type' => 'team', 'title' => 'Team notice', 'body' => 'A team update']);

        $response = $this->actingAs($client)->get(route('home'));

        $response->assertOk()
            ->assertSee('clientMessagesButton')
            ->assertSee('clientMessagesModal')
            ->assertSee('panel=1')
            ->assertSee('clientOtherNotificationMenu')
            ->assertSee('Team notice')
            ->assertDontSee('Message notice');
    }

    public function test_staff_notifications_use_standard_page_surface_without_messages_shortcut(): void
    {
        $staff = User::factory()->create(['role_name' => 'Agent']);
        AppNotification::create(['user_id' => $staff->user_id, 'type' => 'listing', 'title' => 'Listing update', 'body' => 'A listing changed.']);

        $this->actingAs($staff)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('notifications-page staff-page-surface', false)
            ->assertSee('Listing update')
            ->assertDontSee('notifications-messages-link', false);
    }

    public function test_notifications_use_the_client_shell_for_clients_and_staff_shell_for_professionals(): void
    {
        $client = User::factory()->create(['role_name' => 'Client']);
        $this->actingAs($client)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('notifications-client-page', false)
            ->assertSee('publicNavigation', false)
            ->assertDontSee('dashboard-container', false);

        $agent = User::factory()->create(['role_name' => 'Agent']);
        $this->actingAs($agent)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('notifications-page staff-page-surface', false)
            ->assertSee('dashboard-container', false)
            ->assertDontSee('publicNavigation', false);
    }

    public function test_notifications_are_paginated_nine_per_page_and_selection_stays_on_current_page(): void
    {
        $client = User::factory()->create(['role_name' => 'Client']);
        $notifications = collect();

        for ($index = 1; $index <= 10; $index++) {
            $notifications->push(AppNotification::create([
                'user_id' => $client->user_id,
                'type' => 'listing',
                'title' => 'Update ' . $index,
                'body' => 'Notification ' . $index,
                'created_at' => now()->addSeconds($index),
                'updated_at' => now()->addSeconds($index),
            ]));
        }

        $this->actingAs($client)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertViewHas('notifications', fn ($page) => $page->perPage() === 9 && $page->count() === 9);

        $secondPage = $this->get(route('notifications.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('notifications', fn ($page) => $page->count() === 1)
            ->assertViewHas('selectedNotification', fn ($notification) => $notification?->title === 'Update 1');

        $secondPage->assertSee('Update 1');
        $secondPage->assertSee('data-mobile-selected="false"', false)
            ->assertSee('data-close-notification-detail', false);

        $selected = $this->get(route('notifications.index', ['selected' => $notifications->first()->notification_id]))
            ->assertOk();
        $selected->assertSee('data-mobile-selected="true"', false)
            ->assertSee('data-close-notification-detail', false);
    }

    public function test_notification_bell_item_opens_its_detail_page_for_all_notification_types(): void
    {
        $client = User::factory()->create(['role_name' => 'Client']);
        $notification = AppNotification::create([
            'user_id' => $client->user_id,
            'type' => 'message',
            'title' => 'New message',
            'body' => 'A reply is waiting.',
            'url' => route('messages.inbox'),
        ]);

        $this->actingAs($client)->get(route('notifications.open', $notification))
            ->assertRedirect(route('notifications.index', ['selected' => $notification->notification_id]));
    }
}
