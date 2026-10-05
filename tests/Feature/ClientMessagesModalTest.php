<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientMessagesModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_load_the_embedded_messages_panel(): void
    {
        $client = User::factory()->create(['role_name' => 'Client']);

        $this->actingAs($client)
            ->get(route('messages.inbox', ['panel' => 1]))
            ->assertOk()
            ->assertSee('client-messages-embed')
            ->assertSee('Select a conversation or start a new one.');
    }

    public function test_staff_cannot_use_the_client_embedded_messages_panel(): void
    {
        $agent = User::factory()->create(['role_name' => 'Agent']);

        $this->actingAs($agent)
            ->get(route('messages.inbox', ['panel' => 1]))
            ->assertForbidden();
    }

    public function test_embedded_inbox_starts_with_preview_list_and_selected_thread_has_back_control(): void
    {
        $client = User::factory()->create(['role_name' => 'Client']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $client->user_id]);
        $conversation->participants()->create(['user_id' => $client->user_id]);
        $conversation->participants()->create(['user_id' => $agent->user_id]);
        ConversationMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $agent->user_id,
            'body' => 'The latest viewing details are ready.',
        ]);

        $this->actingAs($client)->get(route('messages.inbox', ['panel' => 1]))
            ->assertOk()
            ->assertSee('client-messages-panel', false)
            ->assertDontSee('has-selected-thread', false)
            ->assertSee('The latest viewing details are ready.')
            ->assertSee($agent->name);

        $this->get(route('messages.inbox', ['panel' => 1, 'conversation' => $conversation->conversation_id]))
            ->assertOk()
            ->assertSee('has-selected-thread', false)
            ->assertSee('data-conversation-id="' . $conversation->conversation_id . '"', false)
            ->assertSee('aria-label="Back to conversations"', false);
    }
}
