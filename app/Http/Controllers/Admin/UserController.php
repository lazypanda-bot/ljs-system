<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ActivityMail;
use App\Models\User;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $data = $this->adminUsersPageData($request);
        $users = $data['users'];

        if ($request->boolean('table_only')) {
            return response()->json([
                'html' => view('admin.partials.users-table', compact('users'))->render(),
            ]);
        }

        return view('admin.admin-users', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorizeAdmin();

        return view('admin.admin-users', $this->adminUsersPageData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $creator = Auth::user();
        if (! ($creator instanceof User)) {
            abort(403);
        }
        $allowedRoles = $creator->normalizedRole() === 'lead_broker'
            ? ['Admin', 'Lead Broker', 'Broker', 'Agent']
            : ['Agent', 'Broker'];

        \App\Support\InputRules::normalize($request);
        $validated = $request->validate([
            'name' => \App\Support\InputRules::name(),
            'email' => \App\Support\InputRules::email(),
            'role_name' => ['required', Rule::in($allowedRoles)],
            'account_status' => 'required|in:Active,Inactive,Suspended',
            'password' => 'required|string|min:8',
            'credential_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], \App\Support\InputRules::messages());

        unset($validated['credential_document']);

        $existingUser = User::withTrashed()->where('email', $validated['email'])->first();
        if ($existingUser && ! $existingUser->trashed()) {
            throw ValidationException::withMessages([
                'email' => 'The email has already been taken.',
            ]);
        }

        $initialPassword = $validated['password'];
        $validated['password'] = Hash::make($initialPassword);

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

        $mailed = true;
        try {
            Mail::to($user->email, $user->name)->sendNow(new ActivityMail(
                'Your LJS Realty account is ready',
                "Hello {$user->name},\n\nAn account was created for you at LJS Realty and Brokerage.\n\n"
                . "Role: {$user->role_name}\nEmail: {$user->email}\nInitial password: {$initialPassword}\n\n"
                . 'Log in with these details, then change your password in Settings.'
            ));
        } catch (\Throwable $exception) {
            $mailed = false;
            Log::warning('Account email could not be sent.', ['user_id' => $user->user_id, 'error' => $exception->getMessage()]);
        }

        return redirect()->route('admin.users.index')->with(
            'success',
            $mailed
                ? 'User created. Their role, email and initial password were emailed to ' . $user->email . '.'
                : 'User created, but the account email could not be sent. Share the initial password with them directly.'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $this->authorizeAdmin();

        return view('admin.admin-users', [
            ...$this->adminUsersPageData(),
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
            ...$this->adminUsersPageData(),
            'editUser' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        // Only the account status can be changed from the users list.
        $validated = $request->validate([
            'account_status' => 'required|in:Active,Inactive',
        ]);

        if ((int) $user->user_id === (int) Auth::id()) {
            throw ValidationException::withMessages(['account_status' => 'You cannot change your own account status.']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', $user->name . ' is now ' . strtolower($validated['account_status']) . '.');
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

        return response()->file($credentialPath, [
            'Content-Type' => match (strtolower(pathinfo($credentialPath, PATHINFO_EXTENSION))) {
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'application/octet-stream',
            },
            'Content-Disposition' => 'inline; filename="' . basename($credentialPath) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->actingRole() === 'admin', 403);
    }

    private function adminUsersPageData(?Request $request = null): array
    {
        $users = User::with('professionalProfile')
            ->where('role_name', '!=', 'Client')
            ->orderBy('user_id')
            ->paginate(7);

        if ($request) {
            $users->appends($request->except(['table_only', 'page']));
        }

        $takenUserIds = Team::pluck('user_id')
            ->merge(TeamMember::pluck('user_id'))
            ->unique()
            ->values()
            ->all();

        return [
            'users' => $users,
            'allUsers' => User::where('role_name', '!=', 'Client')->get(),
            'teams' => Team::with(['user', 'members.user'])->latest()->get(),
            'takenUserIds' => $takenUserIds,
        ];
    }
}
