<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! ($user instanceof User)) {
            abort(403);
        }
        $actingRole = $user->actingRole();

        if ($actingRole === 'admin') {
            return $this->adminIndex($request);
        }

        $search = trim($request->string('q')->toString());
        $rating = $request->integer('rating');

        $reviews = Review::with(['reviewer', 'property'])
            ->where(function ($query) use ($user) {
                $query->where('reviewed_user_id', $user->user_id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('reviewed_user_id')->where('user_id', $user->user_id));
            })
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . addcslashes($search, '%_\\') . '%';
                $query->where(fn ($q) => $q->where('review', 'like', $term)
                    ->orWhereHas('reviewer', fn ($r) => $r->where('name', 'like', $term))
                    ->orWhereHas('property', fn ($p) => $p->where('property_name', 'like', $term)));
            })
            ->when($rating >= 1 && $rating <= 5, fn ($query) => $query->where('rating', $rating))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.reviews-staff', compact('reviews', 'search', 'rating'));
    }

    public function destroy(Review $review)
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->actingRole() === 'admin', 403);

        $review->delete();

        return redirect()->back()->with('success', 'Review deleted.');
    }

    private function adminIndex(Request $request)
    {
        $search = trim($request->string('q')->toString());
        $rating = $request->integer('rating');
        $professionalFilter = $request->string('professional')->toString();
        $roleGroups = ['all-agents' => ['Agent'], 'all-brokers' => ['Broker'], 'all-lead-brokers' => ['Lead Broker']];
        $professionalId = ctype_digit($professionalFilter) ? (int) $professionalFilter : 0;

        $reviews = Review::with(['reviewer', 'reviewedUser', 'user', 'property'])
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . addcslashes($search, '%_\\') . '%';
                $query->where(function ($query) use ($term) {
                    $query->where('review', 'like', $term)
                        ->orWhereHas('reviewer', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('reviewedUser', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('property', fn ($q) => $q->where('property_name', 'like', $term));
                });
            })
            ->when($rating >= 1 && $rating <= 5, fn ($query) => $query->where('rating', $rating))
            ->when($professionalId > 0, fn ($query) => $query->where('reviewed_user_id', $professionalId))
            ->when(isset($roleGroups[$professionalFilter]), fn ($query) => $query->whereHas('reviewedUser', fn ($q) => $q->whereIn('role_name', $roleGroups[$professionalFilter])))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $professionals = User::query()
            ->whereIn('user_id', Review::query()->whereNotNull('reviewed_user_id')->select('reviewed_user_id'))
            ->orderBy('name')
            ->get(['user_id', 'name', 'role_name']);

        return view('pages.reviews-admin', compact('reviews', 'professionals', 'search', 'rating', 'professionalId', 'professionalFilter'));
    }
}
