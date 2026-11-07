<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivatePageCachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_are_not_cacheable_for_every_role(): void
    {
        foreach (['Admin', 'Lead Broker', 'Broker', 'Agent', 'Client'] as $role) {
            $user = User::factory()->create(['role_name' => $role]);
            $response = $this->actingAs($user)->get(route('messages.inbox'));
            $response->assertOk()->assertSee('js/session-history.js', false)
                ->assertHeader('Pragma', 'no-cache')->assertHeader('Expires', '0');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        }
    }

    public function test_embedded_messages_are_protected_and_logout_blocks_returning(): void
    {
        $user = User::factory()->create(['role_name' => 'Client']);
        $response = $this->actingAs($user)->get(route('messages.inbox', ['panel' => 1]));
        $response->assertOk()->assertSee('js/session-history.js', false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $logout = $this->post(route('logout'));
        $logout->assertRedirect('/');
        $this->assertStringContainsString('no-store', $logout->headers->get('Cache-Control'));
        $this->assertGuest();
        $this->get(route('messages.inbox'))->assertRedirect(route('home'));
        $this->get(route('messages.inbox', ['panel' => 1]))->assertRedirect(route('home'));
    }
}
