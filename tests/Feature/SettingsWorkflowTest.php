<?php

namespace Tests\Feature;

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
