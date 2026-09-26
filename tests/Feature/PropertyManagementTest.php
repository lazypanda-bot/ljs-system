<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PropertyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_property_and_it_appears_on_the_public_listing(): void
    {
        Http::fake();
        $admin = User::factory()->create([
            'role_name' => 'Admin',
            'account_status' => 'Active',
        ]);

        $response = $this->actingAs($admin)->post('/admin/properties', [
            'property_name' => 'Skyline Villa',
            'property_type' => 'House & Lot',
            'description' => 'A modern 3-bedroom home with a city view.',
            'street' => '123 Mango Avenue',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 15000000,
            'lot_area' => 200,
            'floor_area' => 180,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'property_status' => 'For Sale',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('property_listings', [
            'property_name' => 'Skyline Villa',
        ]);

        $publicResponse = $this->get('/properties');
        $publicResponse->assertOk();
        $publicResponse->assertSee('Skyline Villa');
    }
}
