<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validate the form inputs to match what is coming from Blade
        $validated = $request->validate([
            'property_name' => 'required|string|max:255',
            'property_type' => 'required|string',
            'description'   => 'required|string',
            'location'      => 'required|string',
            'price'         => 'required|numeric',
            'address'       => 'required|string',
            'lot_area'      => 'required|numeric',
            'floor_area'    => 'required|numeric',
            'bedrooms'      => 'required|integer',
            'bathrooms'     => 'required|integer',
            'property_status'=> 'required|string',
            'images.*'      => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $userId = Auth::id() ?? $this->ensureFallbackUser();

        // 2. Set Default coordinates (Centered on Cebu)
        $latitude = 10.3157;
        $longitude = 123.8854;

        // 3. Try fetching coordinates safely via OpenStreetMap API with a 3-second timeout
        try {
            $queryAddress = $validated['address'];

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
            'property_location'    => $validated['address'],
            'price'                => (int) $validated['price'],
            'property_status'      => $this->normalizeStatus($validated['property_status']),
            'approval_status'      => 'Approved',
            'latitude'             => $latitude,
            'longitude'            => $longitude,
        ]);

        // 5. Insert Property Details
        DB::table('property_details')->insert([
            'property_id' => $property->property_id,
            'lot_area'    => $validated['lot_area'],
            'floor_area'  => $validated['floor_area'],
            'bedroom'     => $validated['bedrooms'],
            'bathroom'    => $validated['bathrooms'],
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

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

        // 7. Send notification to all users about the new listing and random code
        $allUsers = DB::table('users')->get();
        foreach ($allUsers as $user) {
            DB::table('email_notifications')->insert([
                'user_id' => $user->user_id,
                'message' => "New listing added by Admin: {$validated['property_name']} located at {$validated['address']}. Use code: {$referralCode}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 8. Redirect on Success
        return redirect()->route('app.page', ['page' => 'property'])->with('success', 'Property added and notifications sent successfully!');
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
        return match ($status) {
            'For Sale' => 'Available',
            'For Rent' => 'Rented',
            'Sold' => 'Sold',
            'Pending' => 'Unavailable',
            default => 'Available',
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
}