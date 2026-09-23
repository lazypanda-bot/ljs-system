<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            'password' => 'required|string',
            'role_name' => 'required|in:Lead Broker,Admin,Broker,Agent',
            'account_status' => 'required|in:Active,Inactive,Suspended',
        ]);

        $existingUser = User::withTrashed()->where('email', $validated['email'])->first();
        if ($existingUser && ! $existingUser->trashed()) {
            throw ValidationException::withMessages([
                'email' => 'The email has already been taken.',
            ]);
        }

        $plainPassword = $validated['password'];
        $validated['password'] = Hash::make($plainPassword);

        if ($existingUser) {
            $existingUser->restore();
            $existingUser->update($validated);
            $user = $existingUser->fresh();
        } else {
            $user = User::create($validated);
        }

        $accountEmailSent = true;

        try {
            Mail::raw(
                "Hello {$user->name},\n\nYour LJS Realty account has been created.\n\nLogin email: {$user->email}\nInitial password: {$plainPassword}\nRole: {$user->role_name}\n\nPlease change your password after signing in.",
                function ($message) use ($user) {
                    $message->to($user->email, $user->name)->subject('Your LJS Realty account');
                }
            );
        } catch (\Throwable $exception) {
            $accountEmailSent = false;
            Log::warning('New-user email could not be sent.', [
                'user_id' => $user->user_id,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('admin.users.index')->with(
            $accountEmailSent ? 'success' : 'warning',
            $accountEmailSent
                ? 'User created and account details emailed successfully.'
                : 'User created, but the account email could not be sent. Check the mail configuration and resend the credentials securely.'
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
        ]);

        if ($request->has('password') && $request->password) {
            $request->validate(['password' => 'min:8|confirmed']);
            $validated['password'] = Hash::make($request->password);
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

    private function authorizeAdmin(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->actingRole() === 'admin', 403);
    }
}
