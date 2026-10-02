<?php

namespace Tests\Feature;

use App\Mail\ActivityMail;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_change_records_completion_and_queues_client_notification(): void
    {
        Mail::fake();
        $owner = User::factory()->create(['role_name' => 'Agent']);
        $client = User::factory()->create(['role_name' => 'Client', 'email' => 'client@example.test']);
        $property = Property::create([
            'user_id' => $owner->user_id,
            'property_name' => 'Completed Viewing Home',
            'property_description' => 'Notification test property.',
            'property_type' => 'House & Lot',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => 'professional',
        ]);
        $assignment = PropertyAssign::create([
            'user_id' => $owner->user_id,
            'property_id' => $property->property_id,
            'assigned_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);
        $appointment = Appointment::create([
            'assign_id' => $assignment->assign_id,
            'contact_name' => $client->name,
            'contact_number' => '555-0200',
            'contact_email' => $client->email,
            'appointment_type' => 'Property viewing',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '11:00',
        ]);

        $this->actingAs($owner)->patch(route('appointments.status', $appointment), [
            'appointment_status' => 'Completed',
        ])->assertRedirect();

        $this->assertNotNull($appointment->fresh()->completed_at);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $client->user_id,
            'title' => 'Appointment completed',
        ]);
        Mail::assertQueued(ActivityMail::class, fn (ActivityMail $mail) => $mail->subjectLine === 'Appointment update: Completed');
    }
}