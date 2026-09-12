<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teams = Team::with('user', 'members')->paginate(10);
        $users = User::where('account_status', 'Active')->get();
        
        return view('admin.admin-users', compact('teams', 'users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::where('account_status', 'Active')->get();
        return view('admin.admin-users', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'team_name' => 'nullable|string|max:255',
        ]);

        Team::create($validated);

        return redirect()->route('admin.teams.index')->with('success', 'Team created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        $team->load('user', 'members');
        return view('admin.admin-users', compact('team'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        $team->load('user', 'members');
        $users = User::where('account_status', 'Active')->get();
        return view('admin.admin-users', compact('team', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|required|exists:users,user_id',
            'team_name' => 'nullable|string|max:255',
        ]);

        $team->update($validated);

        return redirect()->route('admin.teams.index')->with('success', 'Team updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $team->delete();

        return response()->json(['success' => true, 'message' => 'Team deleted successfully.']);
    }
}
