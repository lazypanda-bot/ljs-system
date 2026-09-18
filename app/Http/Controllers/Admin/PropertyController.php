<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        try {
            // 1. Validate the form inputs to match the new granular location fields
            $validated = $request->validate([
                'property_name'  => 'required|string|max:255',
                'property_type'  => 'required|string',
                'description'    => 'required|string',
                'house_number'   => 'nullable|string',
                'street'         => 'required|string',
                'city'           => 'required|string',
                'province'       => 'required|string',
                'price'          => 'required|numeric',
                'lot_area'       => 'required|numeric',
                'floor_area'     => 'required|numeric',
                'bedrooms'       => 'required|integer',
                'bathrooms'      => 'required|integer',
                'property_status'=> 'required|string',
                'lifestyle_tag'  => 'nullable|string',
                'location_tag'   => 'nullable|string',
                'amenities'      => 'nullable|string',
                'featured'       => 'nullable|boolean',
                'images.*'       => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            ]);

            $userId = Auth::id() ?? $this->ensureFallbackUser();

            // 2. Set Default coordinates (Centered on Cebu)
            $latitude = 10.3157;
            $longitude = 123.8854;

            // 3. Try fetching coordinates safely via OpenStreetMap API with a 3-second timeout
            try {
                // Dynamically build the full address string for the map API
                $queryAddress = collect([
                    $validated['house_number'] ?? '',
                    $validated['street'],
                    $validated['city'],
                    $validated['province']
                ])->filter()->implode(', ');

                $response = Http::timeout(3)->withHeaders([
                    'User-Agent' => 'LJSRealtyApp/1.0 (ljsrealty@gmail.com)' 
                ])->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json',
                    'q' => $queryAddress,
                    'limit' => 1
                ]);

                if ($response->successful() && count($response->json()) > 0) {
                    $latitude = $response->json()[0]['lat'];
                    $longitude = $response->json()[0]['lon'];
                }
            } catch (\Exception $e) {
                // If the internet is down or API fails, it just logs it and continues using defaults
                Log::error('Geocoding failed, using defaults: ' . $e->getMessage());
            }

            // 4. Automatically generate a random referral code here upon creation
            $referralCode = 'LJS-' . strtoupper(Str::random(6));

            // Create Property
            $property = Property::create([
                'user_id'              => $userId,
                'referral_code'        => $referralCode,
                'property_name'        => $validated['property_name'],
                'property_description' => $validated['description'],
                'property_type'        => $validated['property_type'],
                'lifestyle_tag'        => $validated['lifestyle_tag'] ?? null,
                'location_tag'         => $validated['location_tag'] ?? null,
                // Map the new location fields to the database columns
                'house_number'         => $validated['house_number'] ?? null,
                'street'               => $validated['street'],
                'city'                 => $validated['city'],
                'province'             => $validated['province'],
                'price'                => (int) $validated['price'],
                'property_status'      => $this->normalizeStatus($validated['property_status']),
                'approval_status'      => 'Approved',
                'latitude'             => $latitude,
                'longitude'            => $longitude,
                'featured'             => $request->boolean('featured'),
            ]);

            // 5. Insert Property Details
            $detailsId = DB::table('property_details')->insertGetId([
                'property_id' => $property->property_id, // ensure your DB primary key matches
                'lot_area'    => $validated['lot_area'],
                'floor_area'  => $validated['floor_area'],
                'bedroom'     => $validated['bedrooms'],
                'bathroom'    => $validated['bathrooms'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ], 'details_id');

            // 5b. Save Amenities / Other Features
            if (! empty($validated['amenities'])) {
                foreach (explode(',', $validated['amenities']) as $amenityName) {
                    $amenityName = trim($amenityName);

                    if ($amenityName === '') {
                        continue;
                    }

                    DB::table('property_amenities')->insert([
                        'details_id'   => $detailsId,
                        'amenity_name' => $amenityName,
                    ]);
                }
            }

            // 6. Save Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('properties', 'public');

                    DB::table('property_images')->insert([
                        'property_id' => $property->property_id,
                        'image_path'  => $path,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            // 7. Notify users about the new listing.
            $allUsers = User::query()->where('account_status', 'Active')->get();
            foreach ($allUsers as $user) {
                AppNotification::create([
                    'user_id'    => $user->user_id,
                    'type' => 'listing',
                    'title' => 'New property listing',
                    'body' => $property->property_name . ' is now available.',
                    'url' => in_array($user->actingRole(), ['admin', 'broker', 'agent'], true)
                        ? route('staff.properties.show', $property)
                        : route('properties.show', $property),
                ]);
            }

            // 8. Redirect on Success
            return redirect()->route('app.page', ['page' => 'property'])->with('success', 'Property added and notifications sent successfully!');

        } catch (\Exception $e) {
            // Displays the exact database or code error directly on the screen for troubleshooting
            dd($e->getMessage());
        }
    }

    private function ensureFallbackUser(): int
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
            return 1;
        }

        $roleId = DB::table('roles')->value('role_id');

        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'role_name' => 'admin',
            ]);
        }

        $userId = DB::table('users')->value('user_id');

        if (! $userId) {
            $userId = DB::table('users')->insertGetId([
                'role_id' => $roleId,
                'name' => 'System User',
                'email' => 'system@example.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return (int) $userId;
    }

    private function normalizeStatus(string $status): string
    {
        return match (trim($status)) {
            'Available', 'For Sale' => 'For Sale',
            'For Rent', 'Rent', 'for-rent' => 'For Rent',
            'Sold' => 'Sold',
            'Pending' => 'Pending',
            'Reserved' => 'Reserved',
            'Unavailable' => 'Unavailable',
            default => 'For Sale', // fallback
        };
    }

    // GENERATING REF CODE
    public function generateReferral($id)
    {
        $property = Property::findOrFail($id);

        // Check if it already has a referral code
        if (!empty($property->referral_code)) {
            return redirect()->back()->with('info', 'Referral code already exists: ' . $property->referral_code);
        }

        // Generate unique code and save directly to the property
        $referralCode = 'LJS-' . strtoupper(Str::random(6));
        
        $property->update([
            'referral_code' => $referralCode
        ]);

        return redirect()->back()->with('success', 'Referral code generated: ' . $referralCode);
    }

    public function create()
    {
        $this->authorizeAdmin();

        return view('pages.properties.create');
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->actingRole() === 'admin', 403);
    }

    // VIEW DETAILS
    public function show($id)
    {
        // Fetch the property with its related images, details, and user/agent
        $property = Property::with(['images', 'details', 'user'])->findOrFail($id);

        return view('customer.property-details', compact('property'));
    }
}