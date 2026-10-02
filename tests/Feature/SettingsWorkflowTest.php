<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_and_upload_a_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'contact_number' => '555-0100',
            'profile_photo' => UploadedFile::fake()->create('profile.png', 10, 'image/png'),
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('555-0100', $user->contact_number);
        $this->assertNotEmpty($user->profile_photo);
        $this->assertTrue(Storage::disk('public')->exists($user->profile_photo));
    }

    public function test_professional_can_update_credentials_and_contact_details(): void
    {
        Storage::fake('local');
        $agent = User::factory()->create(['role_name' => 'Agent']);

        $response = $this->actingAs($agent)->post(route('settings.profile.update'), [
            'name' => $agent->name,
            'email' => $agent->email,
            'contact_number' => '09171234567',
            'license_number' => 'PRC-88991',
            'years_experience' => 7,
            'professional_bio' => 'Cebu-based agent specializing in family homes.',
            'credential_document' => UploadedFile::fake()->create('license.pdf', 20, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $agent->refresh();
        $this->assertSame('09171234567', $agent->contact_number);
        $this->assertSame('PRC-88991', $agent->professionalProfile?->license_number);
        $this->assertSame(7, (int) $agent->professionalProfile?->years_experience);
        $this->assertSame('Cebu-based agent specializing in family homes.', $agent->professionalProfile?->professional_bio);
        $this->assertNotEmpty($agent->professionalProfile?->document_file);
        $this->assertTrue(Storage::disk('local')->exists($agent->professionalProfile->document_file));

        $this->actingAs($agent)->get(route('settings.credentials.download'))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_client_cannot_write_professional_profile_fields(): void
    {
        $client = User::factory()->create(['role_name' => 'Client']);

        $this->actingAs($client)->post(route('settings.profile.update'), [
            'name' => $client->name,
            'email' => $client->email,
            'contact_number' => '09170001111',
            'license_number' => 'SHOULD-NOT-SAVE',
            'years_experience' => 12,
            'professional_bio' => 'Ignored',
        ])->assertRedirect();

        $this->assertDatabaseMissing('professional_profiles', ['user_id' => $client->user_id]);
    }

    public function test_client_settings_page_lists_appointments_for_their_email(): void
    {
        $client = User::factory()->create(['role_name' => 'Client', 'email' => 'client@example.test']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $property = Property::query()->create([
            'user_id' => $agent->user_id,
            'property_name' => 'Harbor Residences',
            'property_type' => 'Condo',
            'street' => 'Osmena Blvd',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 4500000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
        ]);
        $assignment = PropertyAssign::query()->create([
            'user_id' => $agent->user_id,
            'property_id' => $property->property_id,
            'assigned_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);
        Appointment::query()->create([
            'assign_id' => $assignment->assign_id,
            'contact_name' => $client->name,
            'contact_number' => '0917',
            'contact_email' => $client->email,
            'appointment_type' => 'Site viewing',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
            'appointment_status' => 'Pending',
        ]);

        $this->actingAs($client)
            ->get(route('app.page', ['page' => 'settings']))
            ->assertOk()
            ->assertSee('landing-nav')
            ->assertSee('publicProfileButton')
            ->assertSee('js/settings.js')
            ->assertSee('profilePhotoEdit')
            ->assertSee('Harbor Residences')
            ->assertSee('Site viewing')
            ->assertSeeInOrder(['Deactivate Account', 'Appointment history'])
            ->assertDontSee('License / PRC number');
    }

    public function test_settings_uses_the_saved_avatar_and_omits_the_role_chip_for_every_role(): void
    {
        foreach (['Client', 'Admin', 'Lead Broker', 'Broker', 'Agent'] as $role) {
            $user = User::factory()->create([
                'role_name' => $role,
                'profile_photo' => 'profile-photos/avatar.png',
            ]);

            $this->actingAs($user)
                ->get(route('app.page', ['page' => 'settings']))
                ->assertOk()
                ->assertSee('profile-photos/avatar.png')
                ->assertDontSee('settings-role-chip');
        }
    }

    public function test_legacy_client_profile_url_redirects_to_settings(): void
    {
        $client = User::factory()->create(['role_name' => 'Client']);

        $this->actingAs($client)
            ->get(route('app.page', ['page' => 'client-profile']))
            ->assertRedirect(route('app.page', ['page' => 'settings']));
    }

    public function test_user_can_change_password_after_confirming_current_password(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPass1!']);

        $response = $this->actingAs($user)->post(route('settings.password.update'), [
            'current_password' => 'CurrentPass1!',
            'password' => 'UpdatedPass2!',
            'password_confirmation' => 'UpdatedPass2!',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('UpdatedPass2!', $user->fresh()->password));
    }

    public function test_user_can_deactivate_their_account_after_explicit_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPass1!']);

        $response = $this->actingAs($user)->delete(route('settings.account.destroy'), [
            'current_password' => 'CurrentPass1!',
            'confirmation' => 'DELETE',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertSoftDeleted('users', ['user_id' => $user->user_id]);
        $this->assertGuest();
    }
}
