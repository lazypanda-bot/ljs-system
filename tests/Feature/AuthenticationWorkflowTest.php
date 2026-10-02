<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_client_registration_requires_email_otp_before_authentication(): void
    {
        Mail::fake();

        $response = $this->post(route('register'), [
            'name' => 'New Client',
            'email' => 'new-client@example.test',
            'password' => 'ClientPass1!',
            'password_confirmation' => 'ClientPass1!',
        ]);

        $response->assertRedirect(route('login.otp'));
        $this->assertGuest();

        $user = User::query()->where('email', 'new-client@example.test')->firstOrFail();
        $this->assertSame('Client', $user->role_name);
        $this->assertSame($user->user_id, session('login_otp_user_id'));
        $this->assertNotEmpty(session('login_otp_hash'));
    }

    public function test_modal_registration_receives_an_otp_response_without_leaving_the_page(): void
    {
        Mail::fake();

        $response = $this->postJson(route('register'), [
            'name' => 'Modal Client',
            'email' => 'modal-client@example.test',
            'password' => 'ClientPass1!',
            'password_confirmation' => 'ClientPass1!',
        ]);

        $response->assertOk()
            ->assertJsonPath('otp', true)
            ->assertJsonStructure(['message', 'csrf_token']);
        $this->assertGuest();
    }
}
