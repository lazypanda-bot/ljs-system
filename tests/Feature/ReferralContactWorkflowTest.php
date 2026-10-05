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
use App\Mail\ActivityMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReferralContactWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_listing_messages_the_listing_owner(): void
    {
        Mail::fake();
        [$property, $referralOwner, $otherProfessional, $code, , , $admin] = $this->adminListingWithReferral();
        $client = User::factory()->create(['role_name' => 'Client']);

        $response = $this->actingAs($client)->post(
            route('properties.message', ['property' => $property, 'ref' => $code->code]),
            ['recipient_id' => $otherProfessional->user_id]
        );

        $response->assertRedirect();
        $participant = ConversationParticipant::query()->where('user_id', $admin->user_id)->firstOrFail();
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $participant->conversation_id,
            'user_id' => $client->user_id,
        ]);
        $this->assertDatabaseHas('conversation_messages', [
            'conversation_id' => $participant->conversation_id,
            'sender_id' => $client->user_id,
        ]);
        $this->assertDatabaseMissing('conversation_participants', ['user_id' => $referralOwner->user_id]);
        $this->assertDatabaseMissing('conversation_participants', ['user_id' => $otherProfessional->user_id]);
        Mail::assertQueued(ActivityMail::class, fn (ActivityMail $mail) => $mail->subjectLine === 'New message from ' . $client->name);
    }

    public function test_admin_listing_ignores_selected_message_recipient(): void
    {
        [$property, , $professional, , , , $admin] = $this->adminListingWithReferral();
        $client = User::factory()->create(['role_name' => 'Client']);

        $this->actingAs($client)->post(route('properties.message', $property), [
            'recipient_id' => $professional->user_id,
        ])->assertRedirect();

        $conversationId = ConversationParticipant::query()
            ->where('user_id', $admin->user_id)
            ->value('conversation_id');
        $this->assertNotNull($conversationId);
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversationId,
            'user_id' => $client->user_id,
        ]);
    }

    public function test_admin_listing_appointment_is_assigned_to_the_listing_owner(): void
    {
        Mail::fake();
        [$property, , $otherProfessional, $code, $otherAssignment, , $admin] = $this->adminListingWithReferral();
        $date = now()->addDays(3)->toDateString();

        $this->actingAs(User::factory()->create(['role_name' => 'Client', 'email' => 'client@example.test']))
            ->post(route('appointments.store', $property), [
            'ref' => $code->code,
            'assign_id' => $otherAssignment->assign_id,
            'contact_name' => 'Test Client',
            'contact_number' => '555-0100',
            'contact_email' => 'client@example.test',
            'appointment_type' => 'Property viewing',
            'preferred_date' => $date,
            'preferred_time' => '10:00',
        ])->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseHas('appointments', ['contact_email' => 'client@example.test']);
        $this->assertDatabaseHas('property_assign', [
            'property_id' => $property->property_id,
            'user_id' => $admin->user_id,
            'status' => 'Approved',
        ]);
        $this->assertDatabaseMissing('appointments', ['assign_id' => $otherAssignment->assign_id]);
        Mail::assertQueued(ActivityMail::class, fn (ActivityMail $mail) => $mail->subjectLine === 'New appointment request: ' . $property->property_name);
    }

    public function test_admin_listing_appointments_respect_the_listing_owners_blocked_dates(): void
    {
        [$property, , , $code, , , $admin] = $this->adminListingWithReferral();
        $date = now()->addDays(4)->toDateString();
        BlockedDate::create([
            'user_id' => $admin->user_id,
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'reason' => 'Unavailable',
        ]);

        $response = $this->actingAs(User::factory()->create(['role_name' => 'Client', 'email' => 'blocked@example.test']))
            ->post(route('appointments.store', $property), [
            'ref' => $code->code,
            'assign_id' => $property->assignments()->where('user_id', $admin->user_id)->value('assign_id'),
            'contact_name' => 'Test Client',
            'contact_number' => '555-0100',
            'contact_email' => 'blocked@example.test',
            'appointment_type' => 'Property viewing',
            'preferred_date' => $date,
            'preferred_time' => '10:00',
        ]);

        $response->assertSessionHasErrors('slot_start_utc');
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

        return [$property, $referralOwner, $otherProfessional, $code, $otherAssignment, $referralAssignment, $admin];
    }
}
