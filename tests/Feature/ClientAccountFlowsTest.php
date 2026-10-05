<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientAccountFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_reset_with_the_emailed_code(): void
    {
        $code = null;
        Mail::shouldReceive('raw')->once()->andReturnUsing(function ($body) use (&$code) {
            preg_match('/(\d{6})/', $body, $matches);
            $code = $matches[1];
        });
        $user = User::factory()->create(['role_name' => 'Agent', 'password' => Hash::make('OldPassword1!')]);

        $this->postJson(route('password.forgot'), ['email' => $user->email])->assertOk();
        $this->postJson(route('password.reset'), ['code' => '000000', 'password' => 'NewPassword1!', 'password_confirmation' => 'NewPassword1!'])
            ->assertStatus(422);
        $this->postJson(route('password.reset'), ['code' => $code, 'password' => 'NewPassword1!', 'password_confirmation' => 'NewPassword1!'])
            ->assertOk();

        $this->assertTrue(Hash::check('NewPassword1!', $user->fresh()->password));
    }

    public function test_unknown_email_gets_the_same_response_and_cannot_reset(): void
    {
        $this->postJson(route('password.forgot'), ['email' => 'nobody@example.test'])->assertOk();
        $this->postJson(route('password.reset'), ['code' => '123456', 'password' => 'NewPassword1!', 'password_confirmation' => 'NewPassword1!'])
            ->assertStatus(422);
    }

    public function test_client_appointments_use_the_client_interface_and_show_booked_history(): void
    {
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $property = Property::create([
            'user_id' => $agent->user_id, 'property_name' => 'History Home', 'property_type' => 'House & Lot',
            'property_description' => 'x', 'street' => '1 St', 'city' => 'Cebu', 'province' => 'Cebu', 'price' => 1000000,
            'property_status' => 'For Sale', 'approval_status' => 'Approved', 'listing_mode' => 'professional',
        ]);
        $assignment = PropertyAssign::create(['user_id' => $agent->user_id, 'property_id' => $property->property_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);
        Appointment::create([
            'assign_id' => $assignment->assign_id, 'client_id' => $client->user_id, 'contact_name' => $client->name,
            'contact_number' => '555', 'contact_email' => 'different@example.test', 'appointment_type' => 'Property viewing',
            'preferred_date' => now()->addDay()->toDateString(), 'preferred_time' => '10:00',
            'starts_at_utc' => now()->addDay(), 'appointment_status' => 'Pending',
        ]);

        $this->actingAs($client)->get(route('appointments.index'))
            ->assertOk()->assertSee('appointments-client-page', false)->assertSee('History Home')
            ->assertDontSee('staff-page-surface', false);
        $this->get(route('app.page', ['page' => 'settings']))->assertOk()->assertSee('History Home');
    }
}
