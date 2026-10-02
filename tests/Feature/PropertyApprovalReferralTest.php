<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PropertyApprovalReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_properties_page_has_inventory_table_and_pending_submission_queue(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $professional = User::factory()->create(['role_name' => 'Agent']);
        Property::create([
            'user_id' => $professional->user_id,
            'property_name' => 'Waiting House',
            'property_description' => 'Awaiting Admin verification.',
            'property_type' => 'House & Lot',
            'street' => '1 Review Road',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 4000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Pending',
            'listing_mode' => 'professional',
        ]);

        $this->actingAs($admin)
            ->get(route('app.page', ['page' => 'property']))
            ->assertOk()
            ->assertSee('Project Listings')
            ->assertSee('Listing Requests')
            ->assertSee('Image')
            ->assertDontSee('Assigned professionals')
            ->assertSee('Waiting House')
            ->assertSee('Awaiting Admin verification.')
            ->assertSee('Reject');
    }

    public function test_professional_listing_stays_pending_until_admin_approves_and_then_gets_a_referral_code(): void
    {
        Http::fake();
        $professional = User::factory()->create(['role_name' => 'Agent']);
        $admin = User::factory()->create(['role_name' => 'Admin']);

        $submission = $this->actingAs($professional)->post('/admin/properties', $this->propertyData());
        $submission->assertRedirect();

        $property = Property::query()->where('property_name', 'Professional Listing')->firstOrFail();
        $this->assertSame('Pending', $property->approval_status);
        $this->assertSame('professional', $property->listing_mode);
        $this->assertDatabaseMissing('referral_codes', ['property_id' => $property->property_id]);

        $approval = $this->actingAs($admin)->post(route('admin.properties.review', $property), [
            'approval_status' => 'Approved',
        ]);
        $approval->assertRedirect();

        $this->assertDatabaseHas('property_listings', [
            'property_id' => $property->property_id,
            'approval_status' => 'Approved',
        ]);
        $this->assertDatabaseHas('referral_codes', [
            'property_id' => $property->property_id,
            'user_id' => $professional->user_id,
        ]);

        $code = ReferralCode::query()->where('property_id', $property->property_id)->firstOrFail();
        $this->actingAs($professional)
            ->get(route('app.page', ['page' => 'listings']))
            ->assertOk()
            ->assertSee('Add Property')
            ->assertSee('Your listing')
            ->assertSee('Referral: ' . $code->code);
        $this->get(route('referrals.open', $code->code))
            ->assertRedirect(route('properties.show', ['id' => $property->property_id, 'ref' => $code->code]));
    }

    public function test_rejection_requires_and_persists_a_reason(): void
    {
        $professional = User::factory()->create(['role_name' => 'Agent']);
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $property = Property::create([
            'user_id' => $professional->user_id,
            'property_name' => 'Professional Listing',
            'property_type' => 'House & Lot',
            'property_description' => 'A verified professional property listing.',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Pending',
            'listing_mode' => 'professional',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.properties.review', $property), ['approval_status' => 'Rejected'])
            ->assertSessionHasErrors('rejection_note');

        $this->post(route('admin.properties.review', $property), [
            'approval_status' => 'Rejected',
            'rejection_note' => 'Please provide a clearer property title document.',
        ])->assertRedirect();

        $this->assertDatabaseHas('property_listings', [
            'property_id' => $property->property_id,
            'approval_status' => 'Rejected',
            'rejection_note' => 'Please provide a clearer property title document.',
        ]);
        $this->assertDatabaseMissing('referral_codes', ['property_id' => $property->property_id]);
    }

    private function propertyData(): array
    {
        return [
            'property_name' => 'Professional Listing',
            'property_type' => 'House & Lot',
            'description' => 'A verified professional property listing.',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'lot_area' => 120,
            'floor_area' => 100,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'property_status' => 'For Sale',
        ];
    }
}
