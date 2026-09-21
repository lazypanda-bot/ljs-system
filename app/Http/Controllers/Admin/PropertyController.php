<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyAssign;
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
        $this->authorizeLister();

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
            $authenticatedUser = Auth::user();
            $actingRole = $authenticatedUser instanceof User ? $authenticatedUser->actingRole() : 'admin';

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
                'approval_status'      => in_array($actingRole, ['broker', 'agent'], true) ? 'Pending' : 'Approved',
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

            // 7. Notify Admins about pending submissions; approved Admin listings notify everyone.
            $allUsers = User::query()
                ->where('account_status', 'Active')
                ->when($property->approval_status === 'Pending', fn ($query) => $query->where('role_name', 'Admin'))
                ->get();
            foreach ($allUsers as $user) {
                AppNotification::create([
                    'user_id'    => $user->user_id,
                    'type' => 'listing',
                    'title' => $property->approval_status === 'Pending' ? 'Listing awaiting approval' : 'New property listing',
                    'body' => $property->property_name . ($property->approval_status === 'Pending' ? ' needs review.' : ' is now available.'),
                    'url' => in_array($user->actingRole(), ['admin', 'broker', 'agent'], true)
                        ? route('staff.properties.show', $property)
                        : route('properties.show', $property),
                ]);
            }

            // 8. Redirect on Success
            $destination = $actingRole === 'admin' ? 'property' : 'listings';
            return redirect()->route('app.page', ['page' => $destination])->with('success', 'Property submitted successfully.');

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
        $this->authorizeLister();

        return view('pages.properties.create');
    }

    public function assign(Request $request, Property $property)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,user_id'],
        ]);

        $users = User::whereIn('user_id', $validated['user_ids'])
            ->where('account_status', 'Active')
            ->whereIn('role_name', ['Broker', 'Agent'])
            ->get();

        foreach ($users as $user) {
            DB::table('property_assign')->insertOrIgnore([
                'user_id' => $user->user_id,
                'property_id' => $property->property_id,
                'assigned_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Property assignments updated.');
    }

    public function reviewAssignment(Request $request, PropertyAssign $assignment)
    {
        $this->authorizeAdmin();

        $validated = $request->validate(['status' => ['required', 'in:Approved,Rejected']]);
        $assignment->update([
            'status' => $validated['status'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        AppNotification::create([
            'user_id' => $assignment->user_id,
            'type' => 'team',
            'title' => 'Selling request ' . strtolower($validated['status']),
            'body' => $assignment->property?->property_name ?? 'Property assignment',
            'url' => route('app.page', ['page' => 'listings']),
        ]);

        return redirect()->back()->with('success', 'Assignment request reviewed.');
    }

    public function reviewListing(Request $request, Property $property)
    {
        $this->authorizeAdmin();
        $validated = $request->validate(['approval_status' => ['required', 'in:Approved,Rejected']]);
        $property->update(['approval_status' => $validated['approval_status']]);

        AppNotification::create([
            'user_id' => $property->user_id,
            'type' => 'listing',
            'title' => 'Listing ' . strtolower($validated['approval_status']),
            'body' => $property->property_name,
            'url' => route('staff.properties.show', $property),
        ]);

        return redirect()->back()->with('success', 'Listing approval updated.');
    }

    public function destroy(Property $property)
    {
        $this->authorizeAdmin();
        $property->delete();

        return redirect()->back()->with('success', 'Property deleted.');
    }

    public function edit(Property $property)
    {
        $this->authorizeAdmin();

        return view('pages.properties.edit', compact('property'));
    }

    public function update(Request $request, Property $property)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'property_name' => ['required', 'string', 'max:255'],
            'property_description' => ['nullable', 'string'],
            'price' => ['required', 'numeric'],
            'property_status' => ['required', 'string', 'max:50'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
        ]);

        $property->update($validated);

        return redirect()->route('app.page', ['page' => 'property'])->with('success', 'Property updated.');
    }

    public function requestAssignment(Property $property)
    {
        $user = Auth::user();
        abort_unless($user instanceof User && in_array($user->actingRole(), ['broker', 'agent'], true), 403);
        abort_unless($property->user?->normalizedRole() === 'admin', 403);

        $assignment = PropertyAssign::firstOrCreate(
            ['user_id' => $user->user_id, 'property_id' => $property->property_id],
            ['assigned_date' => now()->toDateString(), 'status' => 'Pending', 'requested_by' => $user->user_id, 'requested_at' => now()]
        );

        if ($assignment->status === 'Rejected') {
            $assignment->update(['status' => 'Pending', 'requested_by' => $user->user_id, 'requested_at' => now(), 'reviewed_by' => null, 'reviewed_at' => null]);
        }

        $admins = User::where('role_name', 'Admin')->where('account_status', 'Active')->get();
        foreach ($admins as $admin) {
            AppNotification::create([
                'user_id' => $admin->user_id,
                'type' => 'team',
                'title' => 'New selling request',
                'body' => $user->name . ' requested ' . $property->property_name,
                'url' => route('app.page', ['page' => 'property']),
            ]);
        }

        return redirect()->back()->with('success', 'Selling request sent for Admin approval.');
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->actingRole() === 'admin', 403);
    }

    private function authorizeLister(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && in_array($user->actingRole(), ['admin', 'broker', 'agent'], true), 403);
    }

    // VIEW DETAILS
    public function show($id)
    {
        // Fetch the property with its related images, details, and user/agent
        $property = Property::with(['images', 'details', 'user'])->findOrFail($id);

        if ($property->approval_status !== 'Approved') {
            $viewer = Auth::user();
            abort_unless(
                $viewer instanceof User
                    && ($viewer->actingRole() === 'admin' || $property->user_id === $viewer->user_id),
                404
            );
        }

        return view('customer.property-details', compact('property'));
    }
}