<?php

namespace Tests\Feature;

use App\Models\User;
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
}
