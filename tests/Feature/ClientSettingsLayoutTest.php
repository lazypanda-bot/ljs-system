<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientSettingsLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(User $agent, User $client, string $status, string $name): Appointment
    {
        $property = Property::create([
            'user_id' => $agent->user_id, 'property_name' => $name, 'property_type' => 'Lot', 'property_description' => 'x',
            'street' => '1 St', 'city' => 'Cebu', 'province' => 'Cebu', 'price' => 1, 'property_status' => 'For Sale',
            'approval_status' => 'Approved', 'listing_mode' => 'professional',
        ]);
        $assignment = PropertyAssign::create(['user_id' => $agent->user_id, 'property_id' => $property->property_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);

        return Appointment::create([
            'assign_id' => $assignment->assign_id, 'client_id' => $client->user_id, 'contact_name' => $client->name,
            'contact_number' => '555', 'contact_email' => $client->email, 'appointment_type' => 'Property viewing',
            'preferred_date' => now()->addDay()->toDateString(), 'preferred_time' => '10:00',
            'starts_at_utc' => now()->addDay(), 'appointment_status' => $status,
        ]);
    }

    public function test_client_can_delete_account_but_professionals_cannot(): void
    {
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);

        $this->actingAs($client)->get(route('app.page', ['page' => 'settings']))
            ->assertOk()
            ->assertSee('Appointment History')
            ->assertSee('data-open-delete-account', false)
            ->assertSee('id="password-form"', false)
            ->assertDontSee('id="password-tab"', false);

        $this->actingAs($agent)->get(route('app.page', ['page' => 'settings']))
            ->assertOk()->assertSee('id="password-tab"', false)->assertDontSee('data-open-delete-account', false);
    }

    public function test_floating_appointment_icon_lists_only_active_appointments_before_messages_and_bell(): void
    {
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $active = $this->appointment($agent, $client, 'Pending', 'Active Home');
        $rejected = $this->appointment($agent, $client, 'Rejected', 'Rejected Home');

        $response = $this->actingAs($client)->get(route('home'));
        $response->assertOk()->assertSee('clientAppointmentMenu', false)
            ->assertSee('selected=' . $active->appointment_id, false)
            ->assertDontSee('selected=' . $rejected->appointment_id, false);
        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'id="clientMessagesButton"'), strpos($content, 'id="clientAppointmentMenu"'));
        $this->assertLessThan(strpos($content, 'clientOtherNotificationMenu'), strpos($content, 'id="clientMessagesButton"'));

        $this->get(route('appointments.active-count'))->assertOk()->assertJson(['count' => 1]);
    }
}
