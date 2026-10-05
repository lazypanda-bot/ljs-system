<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Property;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagedAgentsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->actingRole() === 'broker', 403);

        $agentIds = TeamMember::query()
            ->whereIn('team_id', Team::query()->where('user_id', $user->user_id)->select('team_id'))
            ->where('status', 'Approved')
            ->where('user_id', '!=', $user->user_id)
            ->pluck('user_id');

        $managedAgents = User::query()
            ->whereIn('user_id', $agentIds)
            ->where('account_status', 'Active')
            ->with('professionalProfile')
            ->orderBy('name')
            ->get();

        $listingsByAgent = Property::query()
            ->whereIn('user_id', $managedAgents->pluck('user_id'))
            ->latest()
            ->get()
            ->groupBy('user_id');

        $appointmentsByAgent = Appointment::query()
            ->with(['assignment.property'])
            ->whereHas('assignment', fn ($query) => $query->whereIn('user_id', $managedAgents->pluck('user_id')))
            ->orderByDesc('starts_at_utc')
            ->get()
            ->groupBy(fn ($appointment) => $appointment->assignment?->user_id);

        $viewerTimezone = $user->timezone ?: config('appointments.default_timezone');

        return view('pages.managed-agents', compact('managedAgents', 'listingsByAgent', 'appointmentsByAgent', 'viewerTimezone'));
    }
}
