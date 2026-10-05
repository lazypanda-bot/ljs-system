<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Property;
use App\Models\ReferralCode;
use App\Models\PropertyDetail;
use App\Jobs\GeocodeProperty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_property_management_routes_require_authentication(): void
    {
        $this->post('/admin/properties', [])->assertRedirect(route('home'));
        $this->get('/admin/properties/create')->assertRedirect(route('home'));
    }

    public function test_add_property_form_has_checkbox_choices_and_custom_add_controls(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);

        $this->actingAs($admin)->get(route('admin.subfiles.addproperty'))
            ->assertOk()
            ->assertSee('Choose or drop photos here')
            ->assertSee('name="amenities[]"', false)
            ->assertSee('name="lifestyle_tags[]"', false)
            ->assertSee('name="location_tags[]"', false)
            ->assertSee('Add a feature not listed')
            ->assertSee('Add a lifestyle tag')
            ->assertSee('Add a location preference')
            ->assertDontSee('Reference Code');
    }

    public function test_public_filter_options_only_include_values_from_approved_properties(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        Property::create([
            'user_id' => $admin->user_id,
            'property_name' => 'Available Condo',
            'property_type' => 'Condominium',
            'street' => '1 Main Road',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
        ]);
        Property::create([
            'user_id' => $admin->user_id,
            'property_name' => 'Unapproved House',
            'property_type' => 'Townhouse',
            'street' => '2 Main Road',
            'city' => 'Mandaue City',
            'province' => 'Cebu',
            'price' => 6000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Pending',
        ]);

        $this->get('/properties')
            ->assertOk()
            ->assertSee('Condominium')
            ->assertSee('Cebu City')
            ->assertSee('Cebu')
            ->assertDontSee('Townhouse')
            ->assertDontSee('Mandaue City');
    }

    public function test_admin_can_create_a_property_and_it_appears_on_the_public_listing(): void
    {
        Http::fake();
        Queue::fake();
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
            'amenities' => ['Pool', 'Custom EV charging'],
            'lifestyle_tags' => ['city_explorer', 'custom_retirement_living'],
            'location_tags' => ['city_center', 'near_university'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('property_listings', [
            'property_name' => 'Skyline Villa',
            'approval_status' => 'Approved',
            'listing_mode' => 'admin',
        ]);
        $property = Property::query()->where('property_name', 'Skyline Villa')->firstOrFail();
        $this->assertDatabaseHas('property_amenities', ['details_id' => $property->details->details_id, 'amenity_name' => 'Pool']);
        $this->assertDatabaseHas('property_amenities', ['details_id' => $property->details->details_id, 'amenity_name' => 'Custom EV charging']);
        $this->assertSame('city_explorer,custom_retirement_living', $property->lifestyle_tag);
        $this->assertSame('city_center,near_university', $property->location_tag);
        $referralCode = ReferralCode::query()->where('property_id', $property->property_id)->where('user_id', $admin->user_id)->firstOrFail();
        $this->assertSame(1, $property->referralCodes()->count());
        Queue::assertPushed(GeocodeProperty::class);

        $publicResponse = $this->get('/properties');
        $publicResponse->assertOk();
        $publicResponse->assertSee('Skyline Villa');
        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('Pool')
            ->assertSee('Custom EV charging')
            ->assertSee('City Explorer')
            ->assertSee('Retirement Living')
            ->assertSee('City Center')
            ->assertSee('Near University');

        $this->actingAs($admin)->get(route('app.page', ['page' => 'property']))
            ->assertOk()
            ->assertSee('Referral link')
            ->assertSee($referralCode->code)
            ->assertSee(route('referrals.open', $referralCode->code));
        $this->get(route('referrals.open', $referralCode->code))
            ->assertRedirect(route('properties.show', ['id' => $property->property_id, 'ref' => $referralCode->code]));
    }

    public function test_admin_property_edits_track_sold_event_date(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $property = Property::create([
            'user_id' => $admin->user_id,
            'property_name' => 'Sale Timestamp Home',
            'property_description' => 'Sale event test.',
            'property_type' => 'House & Lot',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => 'admin',
        ]);

        $this->actingAs($admin)->put(route('admin.properties.update', $property), [
            'property_name' => $property->property_name,
            'property_type' => $property->property_type,
            'property_description' => $property->property_description,
            'price' => $property->price,
            'property_status' => 'Sold',
            'lot_area' => 100,
            'floor_area' => 80,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'street' => $property->street,
            'city' => $property->city,
            'province' => $property->province,
        ])->assertRedirect();

        $this->assertNotNull($property->fresh()->sold_at);
    }

    public function test_edit_page_exposes_all_listing_fields_and_photo_controls(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $property = Property::create([
            'user_id' => $admin->user_id,
            'property_name' => 'Editable Home',
            'property_description' => 'Complete edit form test.',
            'property_type' => 'House & Lot',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => 'admin',
        ]);

        $this->actingAs($admin)->get(route('admin.properties.edit', $property))
            ->assertOk()
            ->assertSee('Property details')
            ->assertSee('Amenities (comma separated)')
            ->assertSee('Property photos')
            ->assertSee('name="property_type"', false)
            ->assertSee('name="lot_area"', false)
            ->assertSee('name="images[]"', false)
            ->assertSee('name="featured"', false);
    }

    public function test_admin_edit_updates_complete_listing_details_and_images(): void
    {
        Storage::fake('public');
        Queue::fake();
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $property = Property::create([
            'user_id' => $admin->user_id,
            'property_name' => 'Editable Home',
            'property_description' => 'Before edit.',
            'property_type' => 'House & Lot',
            'house_number' => 'Unit 2',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => 'admin',
        ]);
        $details = PropertyDetail::create([
            'property_id' => $property->property_id,
            'lot_area' => 100,
            'floor_area' => 80,
            'bedroom' => 2,
            'bathroom' => 1,
        ]);
        $details->amenities()->create(['amenity_name' => 'Old amenity']);
        $oldImagePath = UploadedFile::fake()->create('old.jpg', 10, 'image/jpeg')->store('properties', 'public');
        $oldImage = $property->images()->create(['image_path' => $oldImagePath]);

        $this->actingAs($admin)->put(route('admin.properties.update', $property), [
            'property_name' => 'Updated Home',
            'property_type' => 'Condominium',
            'property_description' => 'Updated details.',
            'price' => 6200000,
            'property_status' => 'For Sale',
            'house_number' => 'Unit 9',
            'street' => '9 Harbor Road',
            'city' => 'Lapu-Lapu City',
            'province' => 'Cebu',
            'lot_area' => 120,
            'floor_area' => 95,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'parking_spaces' => 1,
            'number_of_floors' => 2,
            'property_condition' => 'New',
            'year_built' => now()->year,
            'amenities' => 'Pool, Security',
            'lifestyle_tag' => 'city_explorer',
            'location_tag' => 'city_center',
            'featured' => '1',
            'remove_images' => [$oldImage->image_id],
            'images' => [UploadedFile::fake()->create('new.jpg', 10, 'image/jpeg')],
        ])->assertRedirect();

        $this->assertDatabaseHas('property_listings', [
            'property_id' => $property->property_id,
            'property_name' => 'Updated Home',
            'property_type' => 'Condominium',
            'house_number' => 'Unit 9',
            'featured' => true,
        ]);
        $this->assertDatabaseHas('property_details', [
            'property_id' => $property->property_id,
            'lot_area' => 120,
            'floor_area' => 95,
            'bedroom' => 3,
            'bathroom' => 2,
            'parking_spaces' => 1,
            'number_of_floors' => 2,
        ]);
        $this->assertDatabaseHas('property_amenities', ['details_id' => $details->details_id, 'amenity_name' => 'Pool']);
        $this->assertDatabaseMissing('property_amenities', ['details_id' => $details->details_id, 'amenity_name' => 'Old amenity']);
        $this->assertDatabaseMissing('property_images', ['image_id' => $oldImage->image_id]);
        $this->assertFalse(Storage::disk('public')->exists($oldImagePath));
        $this->assertSame(1, $property->fresh()->images()->count());
    }

    public function test_other_admin_sees_and_uses_listing_owners_referral_code(): void
    {
        $listingAdmin = User::factory()->create(['role_name' => 'Admin']);
        $otherAdmin = User::factory()->create(['role_name' => 'Admin']);
        $property = Property::create([
            'user_id' => $listingAdmin->user_id,
            'property_name' => 'Owner Referral Home',
            'property_description' => 'Admin listing.',
            'property_type' => 'House & Lot',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => 'admin',
        ]);
        $property->assignments()->create(['user_id' => $listingAdmin->user_id, 'assigned_date' => today(), 'status' => 'Approved']);
        $referral = ReferralCode::create(['user_id' => $listingAdmin->user_id, 'property_id' => $property->property_id, 'code' => 'LJS-OWNER-123']);

        $this->actingAs($otherAdmin)->get(route('staff.properties.show', $property))->assertForbidden();
        $this->get(route('app.page', ['page' => 'property']))
            ->assertOk()
            ->assertDontSee($referral->code);
        $this->post(route('agent.properties.generate-code', $property->property_id))->assertForbidden();

        $this->actingAs($listingAdmin)->get(route('staff.properties.show', $property))
            ->assertOk()
            ->assertSee('staff-page-surface', false)
            ->assertDontSee('Seller referral link')
            ->assertDontSee(route('referrals.open', $referral->code));
        $this->get(route('app.page', ['page' => 'property']))
            ->assertOk()
            ->assertSee($referral->code)
            ->assertSee(route('referrals.open', $referral->code));
        $this->post(route('agent.properties.generate-code', $property->property_id))->assertRedirect();

        $this->assertDatabaseCount('referral_codes', 1);
        $this->assertDatabaseHas('referral_codes', [
            'property_id' => $property->property_id,
            'user_id' => $listingAdmin->user_id,
            'code' => $referral->code,
        ]);
    }

    public function test_referral_backfill_creates_codes_for_existing_admin_listings(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $property = Property::create([
            'user_id' => $admin->user_id,
            'property_name' => 'Legacy Admin Listing',
            'property_description' => 'Created before referral links were automatic.',
            'property_type' => 'Condominium',
            'street' => '1 Old Port Road',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 4000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => 'admin',
        ]);

        $migration = require database_path('migrations/2026_09_30_130000_backfill_admin_listing_referrals.php');
        $migration->up();

        $this->assertDatabaseHas('referral_codes', [
            'property_id' => $property->property_id,
            'user_id' => $admin->user_id,
        ]);
    }

    public function test_referral_timestamp_repair_adds_missing_updated_at_column(): void
    {
        \Illuminate\Support\Facades\Schema::table('referral_codes', fn ($table) => $table->dropColumn('updated_at'));
        $migration = require database_path('migrations/2026_10_01_000100_repair_referral_codes_updated_at.php');
        $migration->up();

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('referral_codes', 'updated_at'));
    }
}
