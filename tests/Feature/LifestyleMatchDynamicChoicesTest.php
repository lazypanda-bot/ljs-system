<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifestyleMatchDynamicChoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_listing_tags_and_amenities_are_added_to_quiz_choices(): void
    {
        $owner = User::factory()->create(['role_name' => 'Agent']);
        $property = $this->makeProperty($owner, 'custom_retirement_living', 'waterfront_district');
        $details = PropertyDetail::create(['property_id' => $property->property_id]);
        $details->amenities()->create(['amenity_name' => 'EV Charger']);

        $this->get(route('lifestyle.match'))->assertOk()
            ->assertSee('"value":"custom_retirement_living"', false)
            ->assertSee('"label":"Custom Retirement Living"', false)
            ->assertSee('"value":"waterfront_district"', false)
            ->assertSee('"label":"Waterfront District"', false)
            ->assertSee('"value":"EV Charger"', false);
    }

    public function test_custom_lifestyle_and_location_tags_are_used_for_matching(): void
    {
        $owner = User::factory()->create(['role_name' => 'Agent']);
        $property = $this->makeProperty($owner, 'custom_retirement_living', 'waterfront_district');

        $this->postJson(route('lifestyle.match.results'), [
            'lifestyle' => 'custom_retirement_living',
            'location' => 'waterfront_district',
        ])->assertOk()
            ->assertJsonPath('matches.0.id', $property->property_id)
            ->assertJsonPath('matchScore', 100);
    }

    public function test_multiple_lifestyles_and_locations_match_and_show_readable_preferences(): void
    {
        $owner = User::factory()->create(['role_name' => 'Agent']);
        $property = $this->makeProperty($owner, 'nature_lover', 'quiet_suburb');

        $this->postJson(route('lifestyle.match.results'), [
            'lifestyle' => ['city_explorer', 'nature_lover', 'family_oriented', 'peace_seeker'],
            'location' => ['city_center', 'quiet_suburb', 'near_coast', 'countryside'],
        ])->assertOk()
            ->assertJsonPath('matches.0.id', $property->property_id)
            ->assertJsonPath('matchScore', 100)
            ->assertJsonPath('preferences.lifestyle', 'City Explorer, Nature Lover, Family Oriented, Peace Seeker')
            ->assertJsonPath('preferences.location', 'City Center, Quiet Suburb, Near the Coast, Countryside');
    }

    public function test_quiz_rejects_choices_above_each_selection_limit(): void
    {
        foreach (['lifestyle' => 4, 'location' => 4, 'must_haves' => 7, 'amenities' => 7] as $key => $limit) {
            $choices = array_map(fn (int $index) => 'Choice ' . $index, range(1, $limit + 1));
            $this->postJson(route('lifestyle.match.results'), [$key => $choices])
                ->assertUnprocessable()
                ->assertJsonValidationErrors($key);
            array_pop($choices);
            $this->postJson(route('lifestyle.match.results'), [$key => $choices])->assertOk();
        }
    }

    private function makeProperty(User $owner, string $lifestyle, string $location): Property
    {
        return Property::create([
            'user_id' => $owner->user_id,
            'property_name' => 'Dynamic Match Listing',
            'property_description' => 'Custom preference test.',
            'property_type' => 'Condominium',
            'street' => '1 Waterfront Road',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => 'professional',
            'lifestyle_tag' => $lifestyle,
            'location_tag' => $location,
        ]);
    }
}
