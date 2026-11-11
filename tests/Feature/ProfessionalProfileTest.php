<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfessionalProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_broker_can_update_professional_profile_and_appears_in_public_directory(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $leadBroker = User::factory()->create(['role_name' => 'Lead Broker']);
        $leadBroker->professionalProfile()->create(['license_number' => 'PRC-1001']);

        $this->actingAs($leadBroker)->post(route('settings.profile.update'), [
            'name' => $leadBroker->name,
            'email' => $leadBroker->email,
            'contact_number' => '09171234567',
            'license_number' => 'ATTEMPTED-CHANGE',
            'professional_bio' => 'Experienced Cebu real estate professional.',
            'profile_photo' => UploadedFile::fake()->createWithContent(
                'lead-broker.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3n8AAAAASUVORK5CYII=')
            ),
            'credential_document' => UploadedFile::fake()->create('license.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $leadBroker->refresh()->load('professionalProfile');
        $this->assertSame('09171234567', $leadBroker->contact_number);
        $this->assertSame('PRC-1001', $leadBroker->professionalProfile->license_number);
        $this->assertSame('Experienced Cebu real estate professional.', $leadBroker->professionalProfile->professional_bio);
        $this->assertTrue(Storage::disk('public')->exists($leadBroker->profile_photo));
        $this->assertTrue(Storage::disk('local')->exists($leadBroker->professionalProfile->document_file));

        $this->get(route('agents'))->assertOk()->assertSee($leadBroker->name);
        $this->get(route('agents.show', $leadBroker))
            ->assertOk()
            ->assertSee('PRC-1001')
            ->assertSee('Experienced Cebu real estate professional.');
    }

    public function test_client_can_start_a_direct_message_to_a_lead_broker(): void
    {
        $client = User::factory()->create(['role_name' => 'Client']);
        $leadBroker = User::factory()->create(['role_name' => 'Lead Broker']);

        $this->actingAs($client)
            ->get(route('messages.direct', $leadBroker))
            ->assertRedirect();

        $this->assertDatabaseHas('conversation_participants', ['user_id' => $client->user_id]);
        $this->assertDatabaseHas('conversation_participants', ['user_id' => $leadBroker->user_id]);
    }
}
