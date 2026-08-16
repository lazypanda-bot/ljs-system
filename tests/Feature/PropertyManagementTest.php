<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_property_and_it_appears_on_the_public_listing(): void
    {
        $response = $this->post('/admin/properties', [
            'property_name' => 'Skyline Villa',
            'property_type' => 'House & Lot',
            'description' => 'A modern 3-bedroom home with a city view.',
            'location' => 'Cebu City',
            'price' => 15000000,
            'address' => '123 Mango Avenue, Cebu City',
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
