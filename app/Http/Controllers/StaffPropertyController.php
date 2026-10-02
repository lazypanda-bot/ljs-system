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

        return view('staff.property-details', compact('property'));
    }
}