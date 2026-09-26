<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class CredentialDocumentsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_and_download_credentials_from_private_storage(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();

        $admin = User::factory()->create(['role_name' => 'Admin']);
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Credential Agent',
            'email' => 'credential-agent@example.test',
            'role_name' => 'Agent',
            'account_status' => 'Active',
            'credential_document' => UploadedFile::fake()->create('license.pdf', 24, 'application/pdf'),
        ])->assertRedirect(route('admin.users.index'));

        $professional = User::query()->where('email', 'credential-agent@example.test')->firstOrFail();
        $documentPath = $professional->professionalProfile?->document_file;

        $this->assertDatabaseHas('account_password_setup_tokens', ['user_id' => $professional->user_id]);
        $this->assertNotEmpty($documentPath);
        $this->assertStringStartsWith('professional-credentials/', $documentPath);
        $this->assertTrue(Storage::disk('local')->exists($documentPath));
        $this->assertFalse(Storage::disk('public')->exists($documentPath));

        $this->get(route('admin.users.credentials', $professional))
            ->assertOk()
            ->assertHeader('content-disposition');

        $agent = User::factory()->create(['role_name' => 'Agent']);
        $this->actingAs($agent)->get(route('admin.users.credentials', $professional))->assertForbidden();
    }

    public function test_account_password_setup_link_is_one_time_and_sets_password(): void
    {
        $user = User::factory()->create(['role_name' => 'Agent']);
        $token = Str::random(64);
        DB::table('account_password_setup_tokens')->insert([
            'user_id' => $user->user_id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
        ]);
        $url = URL::temporarySignedRoute(
            'account.password-setup.show',
            now()->addDay(),
            ['user' => $user->user_id, 'token' => $token]
        );

        $this->get($url)->assertOk();
        $this->post($url, [
            'password' => 'NewSecurePass1!',
            'password_confirmation' => 'NewSecurePass1!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewSecurePass1!', $user->fresh()->password));
        $this->assertDatabaseMissing('account_password_setup_tokens', ['user_id' => $user->user_id]);
        $this->get($url)->assertNotFound();
    }
}
