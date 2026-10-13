<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Property;
use App\Models\Review;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller 
{
    public function agents(Request $request)
    {
        $professionals = User::query()
            ->with('professionalProfile')
            ->withCount('receivedReviews')
            ->withAvg('receivedReviews', 'rating')
            ->where('account_status', 'Active')
            ->whereIn('role_name', ['Broker', 'Agent', 'Lead Broker']);

        $topProfessionals = (clone $professionals)
            ->orderByDesc('received_reviews_count')
            ->orderByDesc('received_reviews_avg_rating')
            ->take(5)
            ->get();

        $allProfessionals = $professionals
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role') && $request->role !== 'all', fn ($query) => $query->where('role_name', $request->role))
            ->orderBy('name')
            ->get();

        return view('customer.agents', compact('topProfessionals', 'allProfessionals'));
    }

    public function show(User $professional)
    {
        abort_unless(
            $professional->account_status === 'Active'
                && in_array($professional->role_name, ['Broker', 'Agent', 'Lead Broker'], true),
            404
        );

        $professional->load(['professionalProfile', 'receivedReviews.reviewer']);
        $professional->loadCount('receivedReviews')->loadAvg('receivedReviews', 'rating');

        $listings = Property::with(['images', 'details'])
            ->where('user_id', $professional->user_id)
            ->publiclyVisible()
            ->latest()
            ->get();

        $user = Auth::user();
        $canReview = $user instanceof User && $this->completedAppointmentFor($user, $professional) !== null;
        $myReview = $canReview
            ? Review::query()->where('reviewer_id', $user->user_id)->where('reviewed_user_id', $professional->user_id)->first()
            : null;

        $supervisors = $professional->role_name === 'Lead Broker' ? collect() : User::query()
            ->where('account_status', 'Active')
            ->whereIn('role_name', ['Broker', 'Lead Broker'])
            ->whereKeyNot($professional->user_id)
            ->whereIn('user_id', Team::query()
                ->whereIn('team_id', TeamMember::query()->where('user_id', $professional->user_id)->where('status', 'Approved')->select('team_id'))
                ->select('user_id'))
            ->orderBy('name')
            ->get(['user_id', 'name', 'role_name']);

        return view('customer.agent-profile', compact('professional', 'listings', 'canReview', 'myReview', 'supervisors'));
    }

    public function storeReview(Request $request, User $professional)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        abort_unless(
            $professional->account_status === 'Active'
                && in_array($professional->role_name, ['Broker', 'Agent', 'Lead Broker'], true),
            404
        );

        $appointment = $this->completedAppointmentFor($user, $professional);
        abort_unless($appointment, 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['required', 'string', 'max:2000'],
        ]);

        Review::updateOrCreate(
            ['reviewer_id' => $user->user_id, 'reviewed_user_id' => $professional->user_id],
            [
                'user_id' => $professional->user_id,
                'property_id' => $appointment->assignment?->property_id,
                'rating' => $validated['rating'],
                'review' => $validated['review'],
            ]
        );

        return redirect()->route('agents.show', $professional)->with('success', 'Your review has been saved.');
    }

    public function destroyReview(User $professional)
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->normalizedRole() === 'client', 403);

        Review::query()
            ->where('reviewer_id', $user->user_id)
            ->where('reviewed_user_id', $professional->user_id)
            ->delete();

        return redirect()->route('agents.show', $professional)->with('success', 'Your review has been deleted.');
    }

    /** A client may review only a professional they completed an appointment with. */
    private function completedAppointmentFor(User $client, User $professional): ?Appointment
    {
        if ($client->normalizedRole() !== 'client') {
            return null;
        }

        return Appointment::query()
            ->with('assignment')
            ->where('appointment_status', 'Completed')
            ->where(fn ($query) => $query->where('client_id', $client->user_id)
                ->orWhere(fn ($legacy) => $legacy->whereNull('client_id')->where('contact_email', $client->email)))
            ->whereHas('assignment', fn ($query) => $query->where('user_id', $professional->user_id))
            ->latest('appointment_id')
            ->first();
    }
}
