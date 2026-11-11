<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
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
    // public function store(Request $request)
    // {
    //     $this->authorizeAdmin();

    //     $validated = $request->validate([
    //         'user_id' => ['required', Rule::exists('users', 'user_id')->whereIn('role_name', ['Broker', 'Lead Broker'])],
    //         'team_name' => 'nullable|string|max:255',
    //         'member_ids' => 'array',
    //         'member_ids.*' => 'exists:users,user_id',
    //     ]);

        // DB::transaction(function () use ($validated) {
        //     $memberIds = collect($validated['member_ids'] ?? [])
        //         ->reject(fn ($userId) => (int) $userId === (int) $validated['user_id'])
        //         ->unique();

        //     $team = Team::create(collect($validated)->except('member_ids')->all());

        //     foreach ($memberIds as $userId) {
        //         $team->members()->create([
        //             'user_id' => $userId,
        //             'status' => 'Approved',
        //             'requested_at' => now(),
        //             'approved_at' => now(),
        //         ]);
        //         AppNotification::create([
        //             'user_id' => $userId,
        //             'type' => 'team',
        //             'title' => 'You were added to ' . ($team->team_name ?: 'a team'),
        //             'body' => 'Open Messages to access the team conversation.',
        //             'url' => route('messages.inbox'),
        //         ]);
        //     }
        // });
    //     DB::transaction(function () use ($validated) {
    //         $team = Team::create(collect($validated)->except('member_ids')->all());

    //         // 1. Automatically add/ensure the team leader is a member
    //         $team->members()->firstOrCreate(
    //             ['user_id' => $validated['user_id']],
    //             [
    //                 'status' => 'Approved',
    //                 'requested_at' => now(),
    //                 'approved_at' => now(),
    //             ]
    //         );

    //         // 2. Process other members
    //         $memberIds = collect($validated['member_ids'] ?? [])
    //             ->reject(fn ($userId) => (int) $userId === (int) $validated['user_id'])
    //             ->unique();

    //         foreach ($memberIds as $userId) {
    //             $team->members()->firstOrCreate(
    //                 ['user_id' => $userId],
    //                 [
    //                     'status' => 'Approved',
    //                     'requested_at' => now(),
    //                     'approved_at' => now(),
    //                 ]
    //             );
                
    //             AppNotification::create([
    //                 'user_id' => $userId,
    //                 'type' => 'team',
    //                 'title' => 'You were added to ' . ($team->team_name ?: 'a team'),
    //                 'body' => 'Open Messages to access the team conversation.',
    //                 'url' => route('messages.inbox'),
    //             ]);
    //         }
    //     });

    //     return redirect()->route('admin.teams.index')->with('success', 'Team created successfully.');
    // }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        // $validated = $request->validate([
        //     'user_id' => ['required', Rule::exists('users', 'user_id')->whereIn('role_name', ['Broker', 'Lead Broker'])],
        //     'team_name' => 'nullable|string|max:255',
        //     'member_ids' => 'array',
        //     'member_ids.*' => 'exists:users,user_id',
        // ]);
        $validated = $request->validate([
            'user_id' => [
                'required', 
                Rule::exists('users', 'user_id')->whereIn('role_name', ['Broker', 'Lead Broker']),
                // Ensure the leader is not already assigned as a member or leader in another team
                function ($attribute, $value, $fail) {
                    if ($this->isAssignedToActiveTeam($value)) {
                        $fail('The selected team leader already belongs to another team.');
                    }
                }
            ],
            'team_name' => 'nullable|string|max:255|unique:teams,team_name',
            'member_ids' => 'array',
            'member_ids.*' => [
                'exists:users,user_id',
                // Ensure each member does not already belong to any team
                function ($attribute, $value, $fail) {
                    if ($this->isAssignedToActiveTeam($value)) {
                        $fail('One or more selected users already belong to a team.');
                    }
                }
            ],
        ]);

        DB::transaction(function () use ($validated) {
            $memberIds = collect($validated['member_ids'] ?? [])
                ->map(fn ($userId) => (int) $userId)
                ->reject(fn ($userId) => $userId === (int) $validated['user_id'])
                ->unique()
                ->values();
            $assignedIds = $memberIds->push((int) $validated['user_id'])->unique();

            TeamMember::query()
                ->whereIn('user_id', $assignedIds)
                ->whereHas('team', fn ($query) => $query->where('team_status', 'Inactive'))
                ->delete();

            $team = Team::create(collect($validated)->except('member_ids')->all());

            // 1. Ensure team leader is an approved member
            $team->members()->firstOrCreate(
                ['user_id' => $validated['user_id']],
                ['status' => 'Approved', 'requested_at' => now(), 'approved_at' => now()]
            );

            // 2. Process other members
            foreach ($memberIds as $userId) {
                $team->members()->firstOrCreate(
                    ['user_id' => $userId],
                    ['status' => 'Approved', 'requested_at' => now(), 'approved_at' => now()]
                );
                
                AppNotification::create([
                    'user_id' => $userId,
                    'type' => 'team',
                    'title' => 'You were added to ' . ($team->team_name ?: 'a team'),
                    'body' => 'Open Messages to access the team conversation.',
                    'url' => route('messages.inbox'),
                ]);
            }

            // 3. Automatically create the team Conversation and add participants
            $conversation = \App\Models\Conversation::create([
                'type' => 'team', // or 'group' depending on your chat implementation
                'team_id' => $team->team_id,
                'created_by' => Auth::id(),
            ]);

            foreach ($team->members()->pluck('user_id') as $memberUserId) {
                \App\Models\ConversationParticipant::updateOrCreate(
                    [
                        'conversation_id' => $conversation->conversation_id,
                        'user_id' => $memberUserId,
                    ],
                    ['hidden_at' => null]
                );
            }
        });

        return redirect()->route('admin.teams.index')->with('success', 'Team created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        $user = Auth::user();

        $team->load('user', 'members');
        return view('admin.teams.show', compact('team'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        $this->authorizeAdmin();
        abort_if($team->team_status !== 'Active', 403, 'Reactivate this team before editing its membership.');

        $team->load('user', 'members');
        $currentTeamUserIds = $team->members->pluck('user_id')
            ->push($team->user_id)
            ->map(fn ($userId) => (int) $userId)
            ->unique()
            ->values()
            ->all();
        $selectedMemberIds = $team->members->pluck('user_id')
            ->map(fn ($userId) => (int) $userId)
            ->all();

        return view('admin.admin-users', array_merge(
            $this->usersPageData(),
            compact('team', 'currentTeamUserIds', 'selectedMemberIds')
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, Team $team)
    // {
    //     $this->authorizeAdmin();

    //     $validated = $request->validate([
    //         'user_id' => ['sometimes', 'required', Rule::exists('users', 'user_id')->whereIn('role_name', ['Broker', 'Lead Broker'])],
    //         'team_name' => 'nullable|string|max:255',
    //         'member_ids' => 'nullable|array',
    //         'member_ids.*' => 'integer|exists:users,user_id',
    //     ]);

        // DB::transaction(function () use ($team, $validated): void {
        //     $team->update(collect($validated)->except('member_ids')->all());

        //     if (! array_key_exists('member_ids', $validated)) {
        //         return;
        //     }

        //     $selectedIds = collect($validated['member_ids'] ?? [])
        //         ->map(fn ($userId) => (int) $userId)
        //         ->reject(fn ($userId) => $userId === (int) $team->user_id)
        //         ->unique()
        //         ->values();
        //     $currentIds = $team->members()->pluck('user_id')->map(fn ($userId) => (int) $userId);
        //     $removedIds = $currentIds->diff($selectedIds);
        //     if ($removedIds->isNotEmpty()) {
        //         $team->members()->whereIn('user_id', $removedIds)->delete();
        //     }

        //     foreach ($selectedIds as $userId) {
        //         $member = $team->members()->firstOrCreate(
        //             ['user_id' => $userId],
        //             ['status' => 'Approved', 'requested_at' => now(), 'approved_at' => now()]
        //         );
        //         if ($member->wasRecentlyCreated) {
        //             AppNotification::create([
        //                 'user_id' => $userId,
        //                 'type' => 'team',
        //                 'title' => 'You were added to ' . ($team->team_name ?: 'a team'),
        //                 'body' => 'Open Messages to access the team conversation.',
        //                 'url' => route('messages.inbox'),
        //             ]);
        //         } elseif ($member->status !== 'Approved') {
        //             $member->update(['status' => 'Approved', 'approved_at' => now()]);
        //         }
        //     }
        // });

    //     DB::transaction(function () use ($team, $validated): void {
    //         $team->update(collect($validated)->except('member_ids')->all());

    //         // 1. Ensure the current team leader is always an approved member
    //         $team->members()->firstOrCreate(
    //             ['user_id' => $team->user_id],
    //             ['status' => 'Approved', 'requested_at' => now(), 'approved_at' => now()]
    //         );

    //         if (! array_key_exists('member_ids', $validated)) {
    //             return;
    //         }

    //         $selectedIds = collect($validated['member_ids'] ?? [])
    //             ->map(fn ($userId) => (int) $userId)
    //             ->reject(fn ($userId) => $userId === (int) $team->user_id)
    //             ->unique()
    //             ->values();

    //         $currentIds = $team->members()->pluck('user_id')->map(fn ($userId) => (int) $userId);
    //         // Don't accidentally remove the leader if they were in currentIds
    //         $removedIds = $currentIds->diff($selectedIds)->reject(fn ($userId) => $userId === (int) $team->user_id);
            
    //         if ($removedIds->isNotEmpty()) {
    //             $team->members()->whereIn('user_id', $removedIds)->delete();
    //         }

    //         foreach ($selectedIds as $userId) {
    //             $member = $team->members()->firstOrCreate(
    //                 ['user_id' => $userId],
    //                 ['status' => 'Approved', 'requested_at' => now(), 'approved_at' => now()]
    //             );
    //             if ($member->wasRecentlyCreated) {
    //                 AppNotification::create([
    //                     'user_id' => $userId,
    //                     'type' => 'team',
    //                     'title' => 'You were added to ' . ($team->team_name ?: 'a team'),
    //                     'body' => 'Open Messages to access the team conversation.',
    //                     'url' => route('messages.inbox'),
    //                 ]);
    //             } elseif ($member->status !== 'Approved') {
    //                 $member->update(['status' => 'Approved', 'approved_at' => now()]);
    //             }
    //         }
    //     });

    //     return redirect()->route('admin.teams.index')->with('success', 'Team updated successfully.');
    // }
    public function update(Request $request, Team $team)
    {
        $this->authorizeAdmin();

        // $validated = $request->validate([
        //     'user_id' => ['sometimes', 'required', Rule::exists('users', 'user_id')->whereIn('role_name', ['Broker', 'Lead Broker'])],
        //     'team_name' => 'nullable|string|max:255',
        //     'member_ids' => 'nullable|array',
        //     'member_ids.*' => 'integer|exists:users,user_id',
        // ]);

        $validated = $request->validate([
            'user_id' => [
                'sometimes', 
                'required', 
                Rule::exists('users', 'user_id')->whereIn('role_name', ['Broker', 'Lead Broker']),
                function ($attribute, $value, $fail) use ($team) {
                    if ($this->isAssignedToActiveTeam($value, $team->team_id)) {
                        $fail('The selected team leader already belongs to another team.');
                    }
                }
            ],
            'team_name' => ['nullable', 'string', 'max:255', Rule::unique('teams', 'team_name')->ignore($team->team_id, 'team_id')],
            'member_ids' => 'nullable|array',
            'member_ids.*' => [
                'integer',
                'exists:users,user_id',
                function ($attribute, $value, $fail) use ($team) {
                    if ($this->isAssignedToActiveTeam($value, $team->team_id)) {
                        $fail('One or more selected users already belong to another team.');
                    }
                }
            ],
            'team_status' => ['sometimes', 'required', Rule::in(['Active', 'Inactive'])],
        ]);

        if (count($validated) === 1 && array_key_exists('team_status', $validated)) {
            $team->update(['team_status' => $validated['team_status']]);

            return redirect()->route('admin.teams.index')->with(
                'success',
                $team->team_name . ' is now ' . strtolower($validated['team_status']) . '.'
            );
        }

        abort_if($team->team_status !== 'Active', 403, 'Reactivate this team before editing its membership.');
        abort_if(array_key_exists('team_status', $validated), 422, 'Update team status separately from team details.');

        DB::transaction(function () use ($team, $validated): void {
            $team->update(collect($validated)->except('member_ids')->all());

            $selectedIds = collect($validated['member_ids'] ?? [])
                ->map(fn ($userId) => (int) $userId)
                ->reject(fn ($userId) => $userId === (int) $team->user_id)
                ->unique()
                ->values();
            $assignedIds = $selectedIds->push((int) $team->user_id)->unique();

            TeamMember::query()
                ->whereIn('user_id', $assignedIds)
                ->whereHas('team', fn ($query) => $query->where('team_status', 'Inactive'))
                ->delete();

            // 1. Ensure leader is an approved member
            $team->members()->firstOrCreate(
                ['user_id' => $team->user_id],
                ['status' => 'Approved', 'requested_at' => now(), 'approved_at' => now()]
            );

            if (array_key_exists('member_ids', $validated)) {
                $currentIds = $team->members()->pluck('user_id')->map(fn ($userId) => (int) $userId);
                $removedIds = $currentIds->diff($selectedIds)->reject(fn ($userId) => $userId === (int) $team->user_id);
                
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
            }

            // 2. Automatically sync Conversation Participants
            $conversation = \App\Models\Conversation::firstOrCreate(
                ['type' => 'team', 'team_id' => $team->team_id],
                ['created_by' => Auth::id()]
            );

            if ($conversation) {
                $activeMemberIds = $team->members()->pluck('user_id')->toArray();

                // Remove participants who were kicked/removed from the team
                \App\Models\ConversationParticipant::where('conversation_id', $conversation->conversation_id)
                    ->whereNotIn('user_id', $activeMemberIds)
                    ->delete();

                // Add newly added team members to the conversation chat
                foreach ($activeMemberIds as $memberUserId) {
                    \App\Models\ConversationParticipant::updateOrCreate(
                        [
                            'conversation_id' => $conversation->conversation_id,
                            'user_id' => $memberUserId,
                        ],
                        ['hidden_at' => null]
                    );
                }

                $conversation->touch();
            }
        });

        return redirect()->route('admin.teams.index')->with('success', 'Team updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Team $team)
    // {
    //     $this->authorizeAdmin();

    //     $team->delete();

    //     return response()->json(['success' => true, 'message' => 'Team deleted successfully.']);
    // }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->actingRole() === 'admin', 403);
    }

    private function usersPageData(): array
    {
        // return [
        //     'users' => User::where('role_name', '!=', 'Client')->paginate(10),
        //     'allUsers' => User::where('role_name', '!=', 'Client')->get(),
        //     'teams' => Team::with(['user', 'members.user'])->latest()->get(),
        // ];
        $activeTeamIds = Team::query()->where('team_status', 'Active')->select('team_id');
        $takenUserIds = Team::query()->where('team_status', 'Active')->pluck('user_id')
            ->merge(TeamMember::query()->whereIn('team_id', $activeTeamIds)->pluck('user_id'))
            ->map(fn ($userId) => (int) $userId)
            ->unique()
            ->toArray();

        return [
            'users' => User::where('role_name', '!=', 'Client')->paginate(10),
            'allUsers' => User::where('role_name', '!=', 'Client')->get(),
            'teams' => Team::with(['user', 'members.user'])->latest()->get(),
            'takenUserIds' => $takenUserIds, // Pass this to your view
        ];
    }

    private function isAssignedToActiveTeam(int|string $userId, ?int $exceptTeamId = null): bool
    {
        return Team::query()
            ->where('team_status', 'Active')
            ->where('user_id', $userId)
            ->when($exceptTeamId !== null, fn ($query) => $query->where('team_id', '!=', $exceptTeamId))
            ->exists()
            || TeamMember::query()
                ->where('user_id', $userId)
                ->whereIn('team_id', Team::query()
                    ->where('team_status', 'Active')
                    ->when($exceptTeamId !== null, fn ($query) => $query->where('team_id', '!=', $exceptTeamId))
                    ->select('team_id'))
                ->exists();
    }

    /*
     * **FOR DELETE ACTIONS**
     * Temporarily disabled. Restore the destroy route and this method together
     * if team deletion is re-enabled.
     *
    public function destroy(Team $team)
    {
        $this->authorizeAdmin();

        DB::transaction(function () use ($team) {
            // Find and delete the team conversation (which automatically deletes messages via model events)
            $conversation = \App\Models\Conversation::where('team_id', $team->team_id)->first();
            if ($conversation) {
                $conversation->participants()->delete();
                $conversation->delete();
            }

            // Delete team members and the team itself
            $team->members()->delete();
            $team->delete();
        });

        return response()->json(['success' => true, 'message' => 'Team and its chat group deleted successfully.']);
    }
    */
}
