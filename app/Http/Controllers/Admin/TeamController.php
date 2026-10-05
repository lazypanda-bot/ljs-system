<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorizeAdmin();

        return view('admin.admin-users', $this->usersPageData());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorizeAdmin();

        return view('admin.admin-users', $this->usersPageData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'user_id')->whereIn('role_name', ['Broker', 'Lead Broker'])],
            'team_name' => 'nullable|string|max:255',
            'member_ids' => 'array',
            'member_ids.*' => 'exists:users,user_id',
        ]);

        DB::transaction(function () use ($validated) {
            $memberIds = collect($validated['member_ids'] ?? [])
                ->reject(fn ($userId) => (int) $userId === (int) $validated['user_id'])
                ->unique();

            $team = Team::create(collect($validated)->except('member_ids')->all());

            foreach ($memberIds as $userId) {
                $team->members()->create([
                    'user_id' => $userId,
                    'status' => 'Approved',
                    'requested_at' => now(),
                    'approved_at' => now(),
                ]);
                AppNotification::create([
                    'user_id' => $userId,
                    'type' => 'team',
                    'title' => 'You were added to ' . ($team->team_name ?: 'a team'),
                    'body' => 'Open Messages to access the team conversation.',
                    'url' => route('messages.inbox'),
                ]);
            }
        });

        return redirect()->route('admin.teams.index')->with('success', 'Team created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        $this->authorizeAdmin();

        $team->load('user', 'members');
        return view('admin.admin-users', array_merge($this->usersPageData(), compact('team')));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        $this->authorizeAdmin();

        $team->load('user', 'members');
        return view('admin.admin-users', array_merge($this->usersPageData(), compact('team')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'user_id' => ['sometimes', 'required', Rule::exists('users', 'user_id')->whereIn('role_name', ['Broker', 'Lead Broker'])],
            'team_name' => 'nullable|string|max:255',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:users,user_id',
        ]);

        DB::transaction(function () use ($team, $validated): void {
            $team->update(collect($validated)->except('member_ids')->all());

            if (! array_key_exists('member_ids', $validated)) {
                return;
            }

            $selectedIds = collect($validated['member_ids'] ?? [])
                ->map(fn ($userId) => (int) $userId)
                ->reject(fn ($userId) => $userId === (int) $team->user_id)
                ->unique()
                ->values();
            $currentIds = $team->members()->pluck('user_id')->map(fn ($userId) => (int) $userId);
            $removedIds = $currentIds->diff($selectedIds);
            if ($removedIds->isNotEmpty()) {
                $team->members()->whereIn('user_id', $removedIds)->delete();
            }

            foreach ($selectedIds as $userId) {
                $member = $team->members()->firstOrCreate(
                    ['user_id' => $userId],
                    ['status' => 'Approved', 'requested_at' => now(), 'approved_at' => now()]
                );
                if ($member->wasRecentlyCreated) {
                    AppNotification::create([
                        'user_id' => $userId,
                        'type' => 'team',
                        'title' => 'You were added to ' . ($team->team_name ?: 'a team'),
                        'body' => 'Open Messages to access the team conversation.',
                        'url' => route('messages.inbox'),
                    ]);
                } elseif ($member->status !== 'Approved') {
                    $member->update(['status' => 'Approved', 'approved_at' => now()]);
                }
            }
        });

        return redirect()->route('admin.teams.index')->with('success', 'Team updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $this->authorizeAdmin();

        $team->delete();

        return response()->json(['success' => true, 'message' => 'Team deleted successfully.']);
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->actingRole() === 'admin', 403);
    }

    private function usersPageData(): array
    {
        return [
            'users' => User::where('role_name', '!=', 'Client')->paginate(10),
            'allUsers' => User::where('role_name', '!=', 'Client')->get(),
            'teams' => Team::with(['user', 'members.user'])->latest()->get(),
        ];
    }
}
