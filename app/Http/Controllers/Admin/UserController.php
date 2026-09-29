<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorizeAdmin();

        return view('admin.admin-users', [
            'users' => User::where('role_name', '!=', 'Client')->paginate(10),
            'allUsers' => User::where('role_name', '!=', 'Client')->get(),
            'teams' => Team::with(['user', 'members'])->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorizeAdmin();

        return view('admin.admin-users');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'role_name' => 'required|in:Lead Broker,Admin,Broker,Agent',
            'account_status' => 'required|in:Active,Inactive,Suspended',
            'password' => 'required|string|min:8',
            'credential_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
        unset($validated['credential_document']);

        $existingUser = User::withTrashed()->where('email', $validated['email'])->first();
        if ($existingUser && ! $existingUser->trashed()) {
            throw ValidationException::withMessages([
                'email' => 'The email has already been taken.',
            ]);
        }

        // Hash the password typed by the admin in the modal box
        $validated['password'] = Hash::make($validated['password']);

        if ($existingUser) {
            $existingUser->restore();
            $existingUser->update($validated);
            $user = $existingUser->fresh();
        } else {
            $user = User::create($validated);
        }

        if ($request->hasFile('credential_document')) {
            $path = $request->file('credential_document')->store('professional-credentials', 'local');
            $user->professionalProfile()->updateOrCreate([], ['document_file' => $path]);
        }

        return redirect()->route('admin.users.index')->with(
            'success', 
            'User created successfully with the provided initial password.'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $this->authorizeAdmin();

        return view('admin.admin-users', [
            'users' => User::where('role_name', '!=', 'Client')->paginate(10),
            'allUsers' => User::where('role_name', '!=', 'Client')->get(),
            'teams' => Team::with(['user', 'members'])->latest()->get(),
            'editUser' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $this->authorizeAdmin();

        return view('admin.admin-users', [
            'users' => User::where('role_name', '!=', 'Client')->paginate(10),
            'allUsers' => User::where('role_name', '!=', 'Client')->get(),
            'teams' => Team::with(['user', 'members'])->latest()->get(),
            'editUser' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->user_id . ',user_id',
            'contact_number' => 'sometimes|required|string|max:20',
            'role_name' => 'sometimes|required|in:Lead Broker,Admin,Broker,Agent,Client',
            'account_status' => 'sometimes|required|in:Active,Inactive,Suspended',
            'credential_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($request->has('password') && $request->password) {
            $request->validate(['password' => 'min:8|confirmed']);
            $validated['password'] = Hash::make($request->password);
        }

        unset($validated['credential_document']);
        if ($request->hasFile('credential_document')) {
            $role = $validated['role_name'] ?? $user->role_name;
            if (! in_array($role, ['Lead Broker', 'Admin', 'Broker', 'Agent'], true)) {
                throw ValidationException::withMessages([
                    'credential_document' => 'Credential files can only be added to professional accounts.',
                ]);
            }

            $profile = $user->professionalProfile;
            $oldPath = $profile?->document_file;
            $path = $request->file('credential_document')->store('professional-credentials', 'local');
            $user->professionalProfile()->updateOrCreate([], ['document_file' => $path]);

            if ($oldPath && str_starts_with($oldPath, 'professional-credentials/')) {
                Storage::disk('local')->delete($oldPath);
            }
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->authorizeAdmin();

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
    }

    public function downloadCredentials(User $user)
    {
        $this->authorizeAdmin();

        $path = $user->professionalProfile?->document_file;
        abort_unless($path && str_starts_with($path, 'professional-credentials/'), 404);
        $credentialRoot = realpath(Storage::disk('local')->path('professional-credentials'));
        $credentialPath = realpath(Storage::disk('local')->path($path));
        abort_unless(
            $credentialRoot
                && $credentialPath
                && str_starts_with($credentialPath, $credentialRoot . DIRECTORY_SEPARATOR)
                && is_file($credentialPath),
            404
        );

        return response()->download($credentialPath, basename($credentialPath));
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->actingRole() === 'admin', 403);
    }
}