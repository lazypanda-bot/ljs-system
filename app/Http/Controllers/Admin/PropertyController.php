<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\GeocodeProperty;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\AppNotification;
use App\Services\NotificationService;
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
            'property_type' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'house_number' => ['nullable', 'string'],
            'street' => ['required', 'string'],
            'city' => ['required', 'string'],
            'province' => ['required', 'string'],
            'price' => ['required', 'numeric'],
            ...Property::detailRules($request->input('property_type')),
            'parking_spaces' => ['nullable', 'integer', 'min:0'],
            'number_of_floors' => ['nullable', 'integer', 'min:1'],
            'property_condition' => ['nullable', 'string', 'max:255'],
            'year_built' => ['nullable', 'integer', 'min:1800', 'max:' . (now()->year + 1)],
            'remarks' => ['nullable', 'string', 'max:3000'],
            'property_status' => ['required', 'string'],
            'lifestyle_tags' => ['nullable', 'array', 'max:8'],
            'lifestyle_tags.*' => ['string', 'max:40', 'regex:/^[a-z0-9_-]+$/i'],
            'location_tags' => ['nullable', 'array', 'max:8'],
            'location_tags.*' => ['string', 'max:40', 'regex:/^[a-z0-9_-]+$/i'],
            'lifestyle_tag' => ['nullable', 'string', 'max:255'],
            'location_tag' => ['nullable', 'string', 'max:255'],
            'amenities' => ['nullable'],
            'amenities.*' => ['string', 'max:100'],
            'featured' => ['nullable', 'boolean'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $isAdminLister = $authenticatedUser->actingRole() === 'admin';
        $lifestyleTags = $validated['lifestyle_tags'] ?? array_filter(explode(',', $validated['lifestyle_tag'] ?? ''));
        $locationTags = $validated['location_tags'] ?? array_filter(explode(',', $validated['location_tag'] ?? ''));
        $lifestyleTagValue = collect($lifestyleTags)->map(fn ($tag) => trim($tag))->filter()->unique()->implode(',');
        $locationTagValue = collect($locationTags)->map(fn ($tag) => trim($tag))->filter()->unique()->implode(',');
        $amenityNames = is_array($validated['amenities'] ?? null)
            ? $validated['amenities']
            : explode(',', $validated['amenities'] ?? '');
        $amenityNames = collect($amenityNames)->map(fn ($name) => trim($name))->filter()->unique(fn ($name) => strtolower($name));
        $storedImagePaths = [];
        $createdPropertyId = null;
        try {
            DB::transaction(function () use ($request, $validated, $authenticatedUser, $isAdminLister, $lifestyleTagValue, $locationTagValue, $amenityNames, &$storedImagePaths, &$createdPropertyId): void {
                $property = Property::create([
                    'user_id' => $authenticatedUser->user_id,
                    'property_name' => $validated['property_name'],
                    'property_description' => $validated['description'],
                    'property_type' => $validated['property_type'],
                    'lifestyle_tag' => $lifestyleTagValue ?: null,
                    'location_tag' => $locationTagValue ?: null,
                    'house_number' => $validated['house_number'] ?? null,
                    'street' => $validated['street'],
                    'city' => $validated['city'],
                    'province' => $validated['province'],
                    'price' => (int) $validated['price'],
                    'property_status' => $this->normalizeStatus($validated['property_status']),
                    'sold_at' => $this->normalizeStatus($validated['property_status']) === 'Sold' ? now() : null,
                    'approval_status' => $isAdminLister ? 'Approved' : 'Pending',
                    'listing_mode' => $isAdminLister ? 'admin' : 'professional',
                    'latitude' => 10.3157,
                    'longitude' => 123.8854,
                    'featured' => $request->boolean('featured'),
                ]);
                $createdPropertyId = $property->property_id;

                if ($isAdminLister) {
                    $this->referralCodeFor($property, $authenticatedUser);
                    PropertyAssign::create([
                        'user_id' => $authenticatedUser->user_id,
                        'property_id' => $property->property_id,
                        'assigned_date' => now()->toDateString(),
                        'status' => 'Approved',
                    ]);
                }

                $detailsId = DB::table('property_details')->insertGetId([
                    'property_id' => $property->property_id,
                    ...Property::normalizeDetails($validated),
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'details_id');

                foreach ($amenityNames as $amenityName) {
                    DB::table('property_amenities')->insert([
                        'details_id' => $detailsId,
                        'amenity_name' => $amenityName,
                    ]);
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

                if ($property->approval_status === 'Pending') {
                    NotificationService::oversight([
                        'type' => 'listing',
                        'title' => 'Listing awaiting approval',
                        'body' => $property->property_name . ' needs review.',
                        'url' => route('staff.properties.show', $property),
                    ], $authenticatedUser);
                } else {
                    NotificationService::listingLive($property, $authenticatedUser);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedImagePaths as $path) {
                Storage::disk('public')->delete($path);
            }
            report($exception);

            return redirect()->back()->withInput()->with('error', 'The property could not be saved. Please try again.');
        }

        if ($createdPropertyId) {
            $this->geocode($createdPropertyId);
        }

        $destination = $isAdminLister ? 'property' : 'listings';
        return redirect()->route('app.page', ['page' => $destination])->with('success', 'Property submitted successfully.');
    }

    /** Pin the listing right away; retry from the queue if the lookup fails. */
    private function geocode(int $propertyId): void
    {
        try {
            GeocodeProperty::dispatchSync($propertyId);
        } catch (\Throwable $exception) {
            report($exception);
            GeocodeProperty::dispatch($propertyId);
        }
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
        abort_unless(($isAdmin && $property->isManageableByAdmin($user)) || $ownsProperty || $hasApprovedAssignment, 403);
        abort_unless($property->approval_status === 'Approved', 403, 'Referral links are available after Admin approval.');

        $referralOwner = $property->user;
        abort_unless($referralOwner instanceof User, 422);
        $referralCode = $this->referralCodeFor($property, $referralOwner);

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

        if (! $isRejected) {
            NotificationService::listingLive($property, $user);
        }

        return redirect()->back()->with('success', 'Listing approval updated.');
    }

    public function destroy(Property $property)
    {
        $this->authorizeAdmin();
        abort_unless($property->isManageableByAdmin(Auth::user()), 403);
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
        abort_unless(($isAdmin && $property->isManageableByAdmin($user)) || $isProfessionalOwner, 403);

        $property->load(['details.amenities', 'images']);

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
        abort_unless(($isAdmin && $property->isManageableByAdmin($user)) || $isProfessionalOwner, 403);

        $validated = $request->validate([
            'property_name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string', 'max:100'],
            'property_description' => ['nullable', 'string'],
            'price' => ['required', 'numeric'],
            'property_status' => ['required', 'in:For Sale,For Rent,Available,Reserved,Pending,Sold,Unavailable'],
            'house_number' => ['nullable', 'string', 'max:255'],
            'lot_number' => ['nullable', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            ...Property::detailRules($request->input('property_type')),
            'parking_spaces' => ['nullable', 'integer', 'min:0'],
            'number_of_floors' => ['nullable', 'integer', 'min:1'],
            'property_condition' => ['nullable', 'string', 'max:255'],
            'year_built' => ['nullable', 'integer', 'min:1800', 'max:' . (now()->year + 1)],
            'remarks' => ['nullable', 'string', 'max:3000'],
            'amenities' => ['nullable', 'string', 'max:3000'],
            'lifestyle_tag' => ['nullable', 'string', 'max:100'],
            'location_tag' => ['nullable', 'string', 'max:100'],
            'featured' => ['nullable', 'boolean'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $imagesToRemove = $property->images()->whereIn('image_id', $validated['remove_images'] ?? [])->get();
        $newImages = $request->file('images', []);
        if ($property->images()->count() - $imagesToRemove->count() + count($newImages) > 12) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'images' => 'A listing can have no more than 12 images.',
            ]);
        }

        $propertyFields = [
            'property_name' => $validated['property_name'],
            'property_type' => $validated['property_type'],
            'property_description' => $validated['property_description'] ?? null,
            'price' => $validated['price'],
            'property_status' => $validated['property_status'],
            'sold_at' => $validated['property_status'] === 'Sold' ? ($property->sold_at ?? now()) : null,
            'house_number' => $validated['house_number'] ?? null,
            'lot_number' => $validated['lot_number'] ?? null,
            'street' => $validated['street'],
            'city' => $validated['city'],
            'province' => $validated['province'],
            'lifestyle_tag' => $validated['lifestyle_tag'] ?? null,
            'location_tag' => $validated['location_tag'] ?? null,
            'featured' => $request->boolean('featured'),
        ];
        $detailFields = Property::normalizeDetails($validated);
        $storedImagePaths = [];

        try {
            DB::transaction(function () use ($property, $propertyFields, $detailFields, $validated, $newImages, $imagesToRemove, $isProfessionalOwner, $request, &$storedImagePaths): void {
                $property->update($isProfessionalOwner
                    ? [...$propertyFields, 'approval_status' => 'Pending', 'rejection_note' => null]
                    : $propertyFields);

                $details = $property->details()->updateOrCreate([], $detailFields);
                DB::table('property_amenities')->where('details_id', $details->details_id)->delete();
                foreach (explode(',', $validated['amenities'] ?? '') as $amenityName) {
                    $amenityName = trim($amenityName);
                    if ($amenityName !== '') {
                        DB::table('property_amenities')->insert([
                            'details_id' => $details->details_id,
                            'amenity_name' => $amenityName,
                        ]);
                    }
                }

                foreach ($newImages as $image) {
                    $path = $image->store('properties', 'public');
                    $storedImagePaths[] = $path;
                    $property->images()->create(['image_path' => $path]);
                }

                foreach ($imagesToRemove as $image) {
                    $image->delete();
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedImagePaths as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }

        foreach ($imagesToRemove as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $this->geocode($property->property_id);

        if ($property->wasChanged('property_status') && $property->property_status === 'Sold') {
            NotificationService::soldReviewPrompts($property);
        }

        if ($isProfessionalOwner) {
            NotificationService::oversight([
                'type' => 'listing',
                'title' => 'Listing resubmitted for approval',
                'body' => $property->property_name . ' was updated and needs review.',
                'url' => route('staff.properties.show', $property),
            ], $user);
        }

        return redirect()->route('app.page', ['page' => $isAdmin ? 'property' : 'listings'])
            ->with('success', $isProfessionalOwner ? 'Listing resubmitted for Admin approval.' : 'Property updated.');
    }

    public function updateStatus(Request $request, Property $property)
    {
        $this->authorizeAdmin();
        abort_unless($property->isManageableByAdmin(Auth::user()), 403);

        $validated = $request->validate([
            'property_status' => ['required', 'in:Available,For Sale,For Rent,Reserved,Pending,Sold,Unavailable'],
        ]);

        $property->update([
            'property_status' => $validated['property_status'],
            'sold_at' => $validated['property_status'] === 'Sold' ? ($property->sold_at ?? now()) : null,
        ]);

        if ($property->wasChanged('property_status') && $property->property_status === 'Sold') {
            NotificationService::soldReviewPrompts($property);
        }

        return redirect()->back()->with('success', 'Property status updated.');
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
    $property = Property::with(['images', 'details', 'user'])->findOrFail($id);
    $referralCode = null;
    $isAdminListing = $property->listing_mode === 'admin';

    // The lister is the only contact for a listing; no other professional can be selected.
    $contactProfessionals = collect();
    $selectedContactAssignment = null;
    $contactUser = $property->user;
    $selectedAssignmentId = null;
    $viewer = Auth::user();
    $hasActiveAppointment = $viewer instanceof User
        && \App\Models\Appointment::clientHasActiveForProperty($viewer, $property->property_id);
    if (Auth::check() && $property->approval_status === 'Approved' && $property->user_id) {
        $ownerAssignment = PropertyAssign::firstOrCreate(
            ['property_id' => $property->property_id, 'user_id' => $property->user_id],
            ['assigned_date' => now()->toDateString(), 'status' => 'Approved']
        );
        if ($ownerAssignment->status !== 'Approved') {
            $ownerAssignment->update(['status' => 'Approved']);
        }
        $selectedAssignmentId = $ownerAssignment->assign_id;
    }

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
        'hasActiveAppointment',
        'referralCode',
        'isAdminListing',
        'contactProfessionals',
        'selectedContactAssignment',
        'contactUser',
        'selectedAssignmentId'
    ));
}
}