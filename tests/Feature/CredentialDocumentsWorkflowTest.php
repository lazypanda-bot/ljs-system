<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
            'password' => 'InitialPass1!',
            'role_name' => 'Agent',
            'account_status' => 'Active',
            'license_number' => 'ACC-778899',
            'credential_document' => UploadedFile::fake()->create('license.pdf', 24, 'application/pdf'),
        ])->assertRedirect(route('admin.users.index'));

        $professional = User::query()->where('email', 'credential-agent@example.test')->firstOrFail();
        $documentPath = $professional->professionalProfile?->document_file;

        $this->assertTrue(Hash::check('InitialPass1!', $professional->password));
        $this->assertSame('ACC-778899', $professional->professionalProfile?->license_number);
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
}
