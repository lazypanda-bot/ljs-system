<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\ReferralCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function store(Request $request)
    {
        $this->authorizeLister();
        $authenticatedUser = Auth::user();
        if (! ($authenticatedUser instanceof User)) {
            abort(403);
        }
        $validated = $request->validate([
            'property_name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string'],
            'description' => ['required', 'string'],
            'house_number' => ['nullable', 'string'],
            'street' => ['required', 'string'],
            'city' => ['required', 'string'],
            'province' => ['required', 'string'],
            'price' => ['required', 'numeric'],
            'lot_area' => ['required', 'numeric'],
            'floor_area' => ['required', 'numeric'],
            'bedrooms' => ['required', 'integer'],
            'bathrooms' => ['required', 'integer'],
            'property_status' => ['required', 'string'],
            'lifestyle_tag' => ['nullable', 'string'],
            'location_tag' => ['nullable', 'string'],
            'amenities' => ['nullable', 'string'],
            'featured' => ['nullable', 'boolean'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $isAdminLister = $authenticatedUser->actingRole() === 'admin';
        $latitude = 10.3157;
        $longitude = 123.8854;

        try {
            $address = collect([
                $validated['house_number'] ?? '',
                $validated['street'],
                $validated['city'],
                $validated['province'],
            ])->filter()->implode(', ');

            $response = Http::timeout(3)->withHeaders([
                'User-Agent' => 'LJSRealtyApp/1.0 (ljsrealty@gmail.com)',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json',
                'q' => $address,
                'limit' => 1,
            ]);

            $results = $response->json();
            if ($response->successful() && is_array($results) && isset($results[0]['lat'], $results[0]['lon'])) {
                $latitude = $results[0]['lat'];
                $longitude = $results[0]['lon'];
            }
        } catch (\Throwable $exception) {
            Log::warning('Geocoding failed; using default coordinates.', ['error' => $exception->getMessage()]);
        }

        $storedImagePaths = [];
        try {
            DB::transaction(function () use ($request, $validated, $authenticatedUser, $isAdminLister, $latitude, $longitude, &$storedImagePaths): void {
                $property = Property::create([
                    'user_id' => $authenticatedUser->user_id,
                    'property_name' => $validated['property_name'],
                    'property_description' => $validated['description'],
                    'property_type' => $validated['property_type'],
                    'lifestyle_tag' => $validated['lifestyle_tag'] ?? null,
                    'location_tag' => $validated['location_tag'] ?? null,
                    'house_number' => $validated['house_number'] ?? null,
                    'street' => $validated['street'],
                    'city' => $validated['city'],
                    'province' => $validated['province'],
                    'price' => (int) $validated['price'],
                    'property_status' => $this->normalizeStatus($validated['property_status']),
                    'approval_status' => $isAdminLister ? 'Approved' : 'Pending',
                    'listing_mode' => $isAdminLister ? 'admin' : 'professional',
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'featured' => $request->boolean('featured'),
                ]);

                $detailsId = DB::table('property_details')->insertGetId([
                    'property_id' => $property->property_id,
                    'lot_area' => $validated['lot_area'],
                    'floor_area' => $validated['floor_area'],
                    'bedroom' => $validated['bedrooms'],
                    'bathroom' => $validated['bathrooms'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'details_id');

                foreach (explode(',', $validated['amenities'] ?? '') as $amenityName) {
                    $amenityName = trim($amenityName);
                    if ($amenityName !== '') {
                        DB::table('property_amenities')->insert([
                            'details_id' => $detailsId,
                            'amenity_name' => $amenityName,
                        ]);
                    }
                }

                foreach ($request->file('images', []) as $image) {
                    $path = $image->store('properties', 'public');
                    $storedImagePaths[] = $path;
                    DB::table('property_images')->insert([
                        'property_id' => $property->property_id,
                        'image_path' => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $recipients = User::query()
                    ->where('account_status', 'Active')
                    ->when($property->approval_status === 'Pending', fn ($query) => $query->where('role_name', 'Admin'))
                    ->get();

                foreach ($recipients as $recipient) {
                    AppNotification::create([
                        'user_id' => $recipient->user_id,
                        'type' => 'listing',
                        'title' => $property->approval_status === 'Pending' ? 'Listing awaiting approval' : 'New property listing',
                        'body' => $property->property_name . ($property->approval_status === 'Pending' ? ' needs review.' : ' is now available.'),
                        'url' => in_array($recipient->actingRole(), ['admin', 'broker', 'agent'], true)
                            ? route('staff.properties.show', $property)
                            : route('properties.show', $property),
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedImagePaths as $path) {
                Storage::disk('public')->delete($path);
            }
            report($exception);

            return redirect()->back()->withInput()->with('error', 'The property could not be saved. Please try again.');
        }

        $destination = $isAdminLister ? 'property' : 'listings';
        return redirect()->route('app.page', ['page' => $destination])->with('success', 'Property submitted successfully.');
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

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $ownsProperty = (int) $property->user_id === (int) $user->user_id;
        $hasApprovedAssignment = $property->assignments()
            ->where('user_id', $user->user_id)
            ->where('status', 'Approved')
            ->exists();

        $isAdmin = $user instanceof User && $user->actingRole() === 'admin';
        abort_unless($isAdmin || $ownsProperty || $hasApprovedAssignment, 403);
        abort_unless($property->approval_status === 'Approved', 403, 'Referral links are available after Admin approval.');

        $referralCode = $this->referralCodeFor($property, $user);

        return redirect()->back()->with('success', 'Referral code generated: ' . $referralCode->code);
    }

    private function referralCodeFor(Property $property, User $user): ReferralCode
    {
        $referralCode = ReferralCode::query()
            ->where('property_id', $property->property_id)
            ->where('user_id', $user->user_id)
            ->first();

        if ($referralCode) {
            return $referralCode;
        }

        do {
            $code = 'LJS-' . strtoupper(Str::random(10));
        } while (ReferralCode::query()->where('code', $code)->exists());

        return ReferralCode::create([
            'property_id' => $property->property_id,
            'user_id' => $user->user_id,
            'code' => $code,
        ]);
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
            ->whereIn('role_name', ['Broker', 'Agent', 'Lead Broker'])
            ->get();

        foreach ($users as $user) {
            DB::table('property_assign')->insertOrIgnore([
                'user_id' => $user->user_id,
                'property_id' => $property->property_id,
                'assigned_date' => now()->toDateString(),
                'status' => 'Approved',
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
        $user = Auth::user();
        abort_unless($user instanceof User && $user->actingRole() === 'admin', 403);

        $validated = $request->validate([
            'approval_status' => ['required', 'in:Approved,Rejected'],
            'rejection_note' => ['required_if:approval_status,Rejected', 'nullable', 'string', 'max:2000'],
        ]);
        $isRejected = $validated['approval_status'] === 'Rejected';
        $property->update([
            'approval_status' => $validated['approval_status'],
            'rejection_note' => $isRejected ? $validated['rejection_note'] : null,
        ]);

        $referralCode = null;
        if (! $isRejected && $property->listing_mode !== 'admin') {
            $lister = $property->user;
            if ($lister instanceof User && in_array($lister->normalizedRole(), ['agent', 'broker', 'lead_broker'], true)) {
                $referralCode = $this->referralCodeFor($property, $lister);
            }
        }

        AppNotification::create([
            'user_id' => $property->user_id,
            'type' => 'listing',
            'title' => $isRejected
                ? 'Listing rejected'
                : ($referralCode ? 'Listing approved; referral link ready' : 'Listing approved'),
            'body' => $isRejected
                ? $property->property_name . ': ' . $validated['rejection_note']
                : ($referralCode
                    ? $property->property_name . ' is live. Referral link: ' . route('referrals.open', $referralCode->code)
                    : $property->property_name . ' is now live.'),
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
        $user = Auth::user();
        $isAdmin = $user instanceof User && $user->actingRole() === 'admin';
        $isProfessionalOwner = $user instanceof User
            && (int) $property->user_id === (int) $user->user_id
            && in_array($user->normalizedRole(), ['agent', 'broker', 'lead_broker'], true)
            && $user->actingRole() !== 'admin';
        abort_unless($isAdmin || $isProfessionalOwner, 403);

        return view('pages.properties.edit', compact('property'));
    }

    public function update(Request $request, Property $property)
    {
        $user = Auth::user();
        $isAdmin = $user instanceof User && $user->actingRole() === 'admin';
        $isProfessionalOwner = $user instanceof User
            && (int) $property->user_id === (int) $user->user_id
            && in_array($user->normalizedRole(), ['agent', 'broker', 'lead_broker'], true)
            && $user->actingRole() !== 'admin';
        abort_unless($isAdmin || $isProfessionalOwner, 403);

        $validated = $request->validate([
            'property_name' => ['required', 'string', 'max:255'],
            'property_description' => ['nullable', 'string'],
            'price' => ['required', 'numeric'],
            'property_status' => ['required', 'string', 'max:50'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
        ]);

        $property->update($isProfessionalOwner
            ? [...$validated, 'approval_status' => 'Pending', 'rejection_note' => null]
            : $validated);

        if ($isProfessionalOwner) {
            $admins = User::query()->where('role_name', 'Admin')->where('account_status', 'Active')->get();
            foreach ($admins as $admin) {
                AppNotification::create([
                    'user_id' => $admin->user_id,
                    'type' => 'listing',
                    'title' => 'Listing resubmitted for approval',
                    'body' => $property->property_name . ' was updated and needs review.',
                    'url' => route('staff.properties.show', $property),
                ]);
            }
        }

        return redirect()->route('app.page', ['page' => $isAdmin ? 'property' : 'listings'])
            ->with('success', $isProfessionalOwner ? 'Listing resubmitted for Admin approval.' : 'Property updated.');
    }

    public function updateStatus(Request $request, Property $property)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'property_status' => ['required', 'in:Available,For Sale,For Rent,Reserved,Pending,Sold,Unavailable'],
        ]);

        $property->update(['property_status' => $validated['property_status']]);

        return redirect()->back()->with('success', 'Property status updated.');
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
    public function show($id, Request $request)
    {
        // Fetch the property with its related images, details, and user/agent
        $property = Property::with(['images', 'details', 'user'])->findOrFail($id);
        $referralCode = null;

        if ($request->filled('ref')) {
            $referralCode = ReferralCode::query()
                ->with('user')
                ->where('code', $request->query('ref'))
                ->where('property_id', $property->property_id)
                ->firstOrFail();
            abort_unless($referralCode->user?->account_status === 'Active', 404);
        }

        $isAdminListing = $property->listing_mode === 'admin';
        $contactProfessionals = collect();
        $selectedContactAssignment = null;

        if ($referralCode && $isAdminListing) {
            $contactProfessionals = $property->assignments()
                ->where('status', 'Approved')
                ->whereHas('user', fn ($query) => $query->where('account_status', 'Active'))
                ->with('user')
                ->get();
            $selectedContactAssignment = $contactProfessionals->firstWhere('user_id', $referralCode->user_id)
                ?? $contactProfessionals->first();
        }

        $contactUser = $selectedContactAssignment?->user ?? $referralCode?->user ?? $property->user;
        $selectedAssignmentId = $selectedContactAssignment?->assign_id;

        if ($property->approval_status !== 'Approved') {
            $viewer = Auth::user();
            abort_unless(
                $viewer instanceof User
                    && ($viewer->actingRole() === 'admin' || $property->user_id === $viewer->user_id),
                404
            );
        }

        return view('customer.property-details', compact(
            'property',
            'referralCode',
            'isAdminListing',
            'contactProfessionals',
            'selectedContactAssignment',
            'contactUser',
            'selectedAssignmentId'
        ));
    }
}