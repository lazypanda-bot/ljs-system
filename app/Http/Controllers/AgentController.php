<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Property;
use App\Models\Review;
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
            ->whereIn('role_name', ['Broker', 'Agent']);

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
            ->orderBy('name')
            ->get();

        return view('customer.agents', compact('topProfessionals', 'allProfessionals'));
    }

    public function show(User $professional)
    {
        abort_unless(
            $professional->account_status === 'Active'
                && in_array($professional->role_name, ['Broker', 'Agent'], true),
            404
        );

        $professional->load(['professionalProfile', 'receivedReviews.reviewer']);
        $professional->loadCount('receivedReviews')->loadAvg('receivedReviews', 'rating');

        $listings = Property::with(['images', 'details'])
            ->where('user_id', $professional->user_id)
            ->whereNull('deleted_at')
            ->latest()
            ->get();

        $user = Auth::user();
        $canReview = $user instanceof User
            && $user->normalizedRole() === 'client'
            && Appointment::query()
                ->where('appointment_status', 'Completed')
                ->where('contact_email', $user->email)
                ->whereHas('assignment', fn ($query) => $query->where('user_id', $professional->user_id))
                ->exists();

        return view('customer.agent-profile', compact('professional', 'listings', 'canReview'));
    }

    public function storeReview(Request $request, User $professional)
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->normalizedRole() === 'client', 403);

        $appointment = Appointment::query()
            ->where('appointment_status', 'Completed')
            ->where('contact_email', $user->email)
            ->whereHas('assignment', fn ($query) => $query->where('user_id', $professional->user_id))
            ->firstOrFail();

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
}