<?php

namespace App\Http\Controllers\Admin;

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
        $validated = $request->validate([
            'property_name' => 'required|string|max:255',
            'property_type' => 'required|string',
            'description' => 'required|string',
            'location' => 'required|string',
            'price' => 'required|numeric',
            'address' => 'required|string',
            'lot_area' => 'required|numeric',
            'floor_area' => 'required|numeric',
            'bedrooms' => 'required|integer',
            'bathrooms' => 'required|integer',
            'property_status' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'images.*' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $userId = Auth::id() ?? $this->ensureFallbackUser();
        $latitude = $validated['latitude'] ?? 10.3157;
        $longitude = $validated['longitude'] ?? 123.8854;

        $property = Property::create([
            'user_id' => $userId,
            'referral_code' => 'LJS-' . strtoupper(Str::random(6)),
            'property_name' => $validated['property_name'],
            'property_description' => $validated['description'],
            'property_type' => $validated['property_type'],
            'property_location' => $validated['location'],
            'price' => (int) $validated['price'],
            'property_status' => $this->normalizeStatus($validated['property_status']),
            'approval_status' => 'Approved',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        DB::table('property_details')->insert([
            'property_id' => $property->property_id,
            'lot_area' => $validated['lot_area'],
            'floor_area' => $validated['floor_area'],
            'bedroom' => $validated['bedrooms'],
            'bathroom' => $validated['bathrooms'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('properties', 'public');

                DB::table('property_images')->insert([
                    'property_id' => $property->property_id,
                    'image_path' => $path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('app.page', ['page' => 'property'])->with('success', 'Property added successfully!');
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
}
