<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StaffPropertyController extends Controller
{
    public function show(Property $property)
    {
        $user = Auth::user();
        if (! ($user instanceof User)) {
            abort(403);
        }
        abort_unless(in_array($user->actingRole(), ['admin', 'broker', 'agent'], true), 403);

        abort_unless(
            Property::visibleTo($user)->whereKey($property->property_id)->exists(),
            403
        );

        $property->load(['images', 'details', 'user']);
        $isAdmin = $user->actingRole() === 'admin';
        $ownsProperty = (int) $property->user_id === (int) $user->user_id;
        $hasApprovedAssignment = $property->assignments()
            ->where('user_id', $user->user_id)
            ->where('status', 'Approved')
            ->exists();
        $canAccessReferral = $isAdmin || $ownsProperty || $hasApprovedAssignment;
        $canGenerateReferral = $property->approval_status === 'Approved' && $canAccessReferral;
        $showReferralUnavailable = $property->approval_status !== 'Approved' && $canAccessReferral;
        $referralCode = $canGenerateReferral
            ? $property->referralCodes()->where('user_id', $user->user_id)->first()
            : null;

        return view('staff.property-details', compact(
            'property',
            'referralCode',
            'canGenerateReferral',
            'showReferralUnavailable'
        ));
    }
}