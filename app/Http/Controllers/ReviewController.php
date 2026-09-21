<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (! ($user instanceof User)) {
            abort(403);
        }
        $actingRole = $user->actingRole();

        $reviews = Review::with(['reviewer', 'property'])
            ->when($actingRole !== 'admin', fn ($query) => $query->where(function ($query) use ($user) {
                $query->where('reviewed_user_id', $user->user_id)->orWhere('user_id', $user->user_id);
            }))
            ->latest()
            ->get();

        return view('pages.reviews-live', compact('reviews'));
    }
}