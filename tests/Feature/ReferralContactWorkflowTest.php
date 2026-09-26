<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\ConversationMessage;
use App\Models\ConversationParticipant;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralContactWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_listing_referral_can_message_another_approved_assignee(): void
    {
        [$property, $referralOwner, $otherProfessional, $code] = $this->adminListingWithReferral();
        $client = User::factory()->create(['role_name' => 'Client']);

        $response = $this->actingAs($client)->post(
            route('properties.message', ['property' => $property, 'ref' => $code->code]),
            ['recipient_id' => $otherProfessional->user_id]
        );

        $response->assertRedirect();
        $participant = ConversationParticipant::query()->where('user_id', $otherProfessional->user_id)->firstOrFail();
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $participant->conversation_id,
            'user_id' => $client->user_id,
        ]);
        $this->assertDatabaseHas('conversation_messages', [
            'conversation_id' => $participant->conversation_id,
            'sender_id' => $client->user_id,
        ]);
        $this->assertNotSame($referralOwner->user_id, $otherProfessional->user_id);
    }

    public function test_referral_appointment_can_select_another_approved_assignee(): void
    {
        [$property, , $otherProfessional, $code, $otherAssignment] = $this->adminListingWithReferral();
        $date = now()->addDays(3)->toDateString();

        $this->post(route('appointments.store', $property), [
            'ref' => $code->code,
            'assign_id' => $otherAssignment->assign_id,
            'contact_name' => 'Test Client',
            'contact_number' => '555-0100',
            'contact_email' => 'client@example.test',
            'appointment_type' => 'Property viewing',
            'preferred_date' => $date,
            'preferred_time' => '10:00',
        ])->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseHas('appointments', [
            'assign_id' => $otherAssignment->assign_id,
            'contact_email' => 'client@example.test',
        ]);
        $this->assertDatabaseHas('property_assign', [
            'property_id' => $property->property_id,
            'user_id' => $otherProfessional->user_id,
            'status' => 'Approved',
        ]);
    }

    public function test_appointments_respect_the_assigned_professionals_blocked_dates(): void
    {
        [$property, $referralOwner, , $code] = $this->adminListingWithReferral();
        $date = now()->addDays(4)->toDateString();
        BlockedDate::create([
            'user_id' => $referralOwner->user_id,
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'reason' => 'Unavailable',
        ]);

        $response = $this->post(route('appointments.store', $property), [
            'ref' => $code->code,
            'assign_id' => $property->assignments()->where('user_id', $referralOwner->user_id)->value('assign_id'),
            'contact_name' => 'Test Client',
            'contact_number' => '555-0100',
            'contact_email' => 'blocked@example.test',
            'appointment_type' => 'Property viewing',
            'preferred_date' => $date,
            'preferred_time' => '10:00',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('appointments', ['contact_email' => 'blocked@example.test']);
    }

    private function adminListingWithReferral(): array
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $referralOwner = User::factory()->create(['role_name' => 'Agent']);
        $otherProfessional = User::factory()->create(['role_name' => 'Broker']);
        $property = Property::create([
            'user_id' => $admin->user_id,
            'property_name' => 'Admin Managed Listing',
            'property_description' => 'A property assigned to multiple professionals.',
            'property_type' => 'House & Lot',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => 'admin',
        ]);

        $referralAssignment = PropertyAssign::create([
            'user_id' => $referralOwner->user_id,
            'property_id' => $property->property_id,
            'assigned_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);
        $otherAssignment = PropertyAssign::create([
            'user_id' => $otherProfessional->user_id,
            'property_id' => $property->property_id,
            'assigned_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);
        $code = ReferralCode::create([
            'user_id' => $referralOwner->user_id,
            'property_id' => $property->property_id,
            'code' => 'LJS-TEST-REF',
        ]);

        return [$property, $referralOwner, $otherProfessional, $code, $otherAssignment, $referralAssignment];
    }
}
