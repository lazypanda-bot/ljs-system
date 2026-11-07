<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitViewNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function roles(): array
    {
        return [['Client', 'client'], ['Agent', 'agent'], ['Broker', 'broker'], ['Admin', 'admin'], ['Lead Broker', 'broker'], ['Lead Broker', 'admin']];
    }

    public function test_notification_selection_preserves_page_filter_and_highlight_for_all_roles(): void
    {
        foreach ($this->roles() as [$role, $mode]) {
            AppNotification::query()->delete();
            $viewer = User::factory()->create(['role_name' => $role]);
            $notifications = collect();
            for ($index = 0; $index < 12; $index++) {
                $notifications->push(AppNotification::create([
                    'user_id' => $viewer->user_id,
                    'type' => 'message',
                    'audience' => $role === 'Lead Broker' && $mode === 'admin' ? 'system' : 'personal',
                    'title' => 'Split notification '.$index,
                    'body' => 'Notification details '.$index,
                ]));
            }
            $this->actingAs($viewer)->withSession(['acting_as' => $mode]);
            $target = $notifications[1];
            $url = route('notifications.index', ['filter' => 'messages', 'page' => 2, 'selected' => $target->notification_id], false);
            $response = $this->get($url)->assertOk()
                ->assertViewHas('notifications', fn ($items) => $items->currentPage() === 2 && $items->count() === 3)
                ->assertViewHas('selectedNotification', fn ($item) => $item?->is($target))
                ->assertSee('href="'.e($url).'"', false)
                ->assertSee('data-split-view="notifications"', false)
                ->assertSee('js/split-view.js', false);
            $document = new \DOMDocument;
            $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $active = (new \DOMXPath($document))->query('//a[contains(@class,"notification-list-item") and @aria-current="true"]');
            $this->assertSame(1, $active->length);
            $this->assertSame($url, $active->item(0)->getAttribute('href'));

            $this->get(route('notifications.index', ['selected' => $target->notification_id]))
                ->assertOk()->assertViewHas('notifications', fn ($items) => $items->currentPage() === 2
                    && $items->contains('notification_id', $target->notification_id));

            $unreadTarget = $notifications[0];
            $partial = $this->getJson(route('notifications.index', ['page' => 2, 'selected' => $unreadTarget->notification_id, 'list_only' => 1]))
                ->assertOk();
            $this->assertStringContainsString('aria-current="true"', $partial->json('html'));
            $this->assertNull($unreadTarget->fresh()->read_at);

            $unreadUrl = route('notifications.index', ['filter' => 'unread', 'selected' => $target->notification_id]);
            $this->get($unreadUrl)->assertOk()
                ->assertViewHas('notifications', fn ($items) => $items->contains('notification_id', $target->notification_id));

            $outsider = User::factory()->create(['role_name' => 'Client']);
            $private = AppNotification::create(['user_id' => $outsider->user_id, 'type' => 'listing', 'audience' => 'personal', 'title' => 'Private split notification', 'body' => 'Hidden details']);
            $this->get(route('notifications.index', ['selected' => $private->notification_id]))
                ->assertOk()->assertDontSee('Private split notification')->assertDontSee('Hidden details');
        }
    }

    public function test_messages_keep_search_filters_selected_thread_and_privacy_for_all_roles(): void
    {
        foreach ($this->roles() as [$role, $mode]) {
            $viewer = User::factory()->create(['role_name' => $role]);
            $target = $this->thread($viewer, 'Split search target');
            $other = $this->thread($viewer, 'Split search other');
            $outsider = User::factory()->create(['role_name' => 'Client']);
            $private = $this->thread($outsider, 'Private split thread');
            $this->actingAs($viewer)->withSession(['acting_as' => $mode]);
            foreach ($role === 'Client' ? [0, 1] : [0] as $panel) {
                $url = route('messages.inbox', ['q' => 'Split search', 'type' => 'direct', 'conversation' => $target->conversation_id, 'panel' => $panel ?: null], false);
                $this->get($url)->assertOk()
                    ->assertViewHas('conversations', fn ($items) => $items->count() === 2 && $items->contains('conversation_id', $other->conversation_id))
                    ->assertViewHas('conversation', fn ($item) => $item?->is($target))
                    ->assertSee('href="'.e($url).'"', false)
                    ->assertSee('data-split-view="messages"', false)
                    ->assertSee('js/split-view.js', false)
                    ->assertDontSee('Private split thread');
                $this->get(route('messages.inbox', ['status' => 'unread', 'conversation' => $target->conversation_id, 'panel' => $panel ?: null]))
                    ->assertOk()->assertViewHas('conversation', fn ($item) => $item?->is($target));
                $this->get(route('messages.inbox', ['conversation' => $private->conversation_id, 'panel' => $panel ?: null]))
                    ->assertOk()->assertViewHas('conversation', null)->assertDontSee('Private split thread');
            }
        }
    }

    public function test_staff_appointment_table_does_not_enable_split_navigation(): void
    {
        foreach ([['Agent', 'agent'], ['Broker', 'broker'], ['Admin', 'admin'], ['Lead Broker', 'broker'], ['Lead Broker', 'admin']] as [$role, $mode]) {
            $viewer = User::factory()->create(['role_name' => $role]);
            $this->actingAs($viewer)->withSession(['acting_as' => $mode])->get(route('appointments.index'))
                ->assertOk()->assertSee('staff-property-table')->assertDontSee('data-split-view="appointments"', false)
                ->assertDontSee('js/split-view.js', false);
        }
    }

    private function thread(User $viewer, string $body): Conversation
    {
        $sender = User::factory()->create(['role_name' => 'Agent']);
        $thread = Conversation::create(['type' => 'direct', 'created_by' => $viewer->user_id]);
        $thread->participants()->createMany([['user_id' => $viewer->user_id], ['user_id' => $sender->user_id]]);
        ConversationMessage::create(['conversation_id' => $thread->conversation_id, 'sender_id' => $sender->user_id, 'body' => $body]);

        return $thread;
    }
}
