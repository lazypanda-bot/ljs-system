<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt against the users table.
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);
        } catch (ValidationException $exception) {
            $request->session()->flash('auth_modal_error', true);
            throw $exception;
        }

        $user = User::where('email', $credentials['email'])
            ->where('account_status', 'Active')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $request->session()->flash('auth_modal_error', true);
            throw ValidationException::withMessages([
                'email' => __('Invalid credentials'),
            ]);
        }

        Auth::logout();
        $request->session()->regenerate();

        $this->sendOtp($request, $user, $request->boolean('remember'));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'A verification code was sent to your email address.',
                'otp' => true,
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->route('login.otp');
    }

    public function showOtp(Request $request)
    {
        abort_unless($request->session()->has('login_otp_user_id'), 403);

        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $expiresAt = $request->session()->get('login_otp_expires_at');
        $attempts = (int) $request->session()->get('login_otp_attempts', 0);

        if (! $expiresAt || now()->greaterThan($expiresAt) || $attempts >= 5) {
            $request->session()->forget(['login_otp_user_id', 'login_otp_hash', 'login_otp_expires_at', 'login_otp_remember', 'login_otp_attempts']);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your verification code expired. Please sign in again.'], 422);
            }

            return redirect()->route('login')->withErrors(['code' => 'Your verification code expired. Please sign in again.']);
        }

        $request->session()->put('login_otp_attempts', $attempts + 1);

        if (! Hash::check($validated['code'], (string) $request->session()->get('login_otp_hash'))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'The verification code is incorrect.'], 422);
            }

            throw ValidationException::withMessages(['code' => 'The verification code is incorrect.']);
        }

        $user = User::findOrFail($request->session()->get('login_otp_user_id'));
        Auth::login($user, (bool) $request->session()->get('login_otp_remember'));
        $request->session()->forget(['login_otp_user_id', 'login_otp_hash', 'login_otp_expires_at', 'login_otp_remember', 'login_otp_attempts']);
        $request->session()->regenerate();

        $destination = $user->normalizedRole() === 'client'
            ? route('home')
            : route('dashboard');

        return $request->expectsJson()
            ? response()->json(['redirect' => $destination])
            : redirect($destination);
    }

    public function resendOtp(Request $request)
    {
        $user = User::findOrFail($request->session()->get('login_otp_user_id'));
        $this->sendOtp($request, $user, (bool) $request->session()->get('login_otp_remember'));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'A new verification code was sent.']);
        }

        return redirect()->route('login.otp')->with('status', 'A new verification code was sent.');
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);

        $user = User::where('email', $validated['email'])->where('account_status', 'Active')->first();
        $code = (string) random_int(100000, 999999);

        // Same session state and response for unknown emails so accounts cannot be enumerated.
        $request->session()->put([
            'password_reset_user_id' => $user?->user_id,
            'password_reset_hash' => Hash::make($code),
            'password_reset_expires_at' => now()->addMinutes(10),
            'password_reset_attempts' => 0,
        ]);

        if ($user) {
            try {
                Mail::raw("Your LJS Realty password reset code is {$code}. It expires in 10 minutes. If you did not request this, you can ignore this email.", function ($message) use ($user) {
                    $message->to($user->email, $user->name)->subject('LJS Realty password reset code');
                });
            } catch (\Throwable $exception) {
                Log::error('Password reset code could not be sent.', ['user_id' => $user->user_id, 'error' => $exception->getMessage()]);
                throw ValidationException::withMessages(['email' => 'We could not send your reset code. Please try again.']);
            }
        }

        return response()->json([
            'message' => 'If that email is registered, a 6-digit code was sent. Enter it with your new password.',
            'csrf_token' => csrf_token(),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], ['password.confirmed' => 'Passwords do not match.']);
        $keys = ['password_reset_user_id', 'password_reset_hash', 'password_reset_expires_at', 'password_reset_attempts'];

        $expiresAt = $request->session()->get('password_reset_expires_at');
        $attempts = (int) $request->session()->get('password_reset_attempts', 0);
        if (! $expiresAt || now()->greaterThan($expiresAt) || $attempts >= 5) {
            $request->session()->forget($keys);

            return response()->json(['message' => 'Your reset code expired. Please request a new one.'], 422);
        }

        $request->session()->put('password_reset_attempts', $attempts + 1);
        $userId = $request->session()->get('password_reset_user_id');

        if (! $userId || ! Hash::check($validated['code'], (string) $request->session()->get('password_reset_hash'))) {
            return response()->json(['message' => 'The reset code is incorrect.'], 422);
        }

        User::whereKey($userId)->firstOrFail()->update(['password' => Hash::make($validated['password'])]);
        $request->session()->forget($keys);

        return response()->json(['message' => 'Password changed. You can now log in with your new password.']);
    }

    private function sendOtp(Request $request, User $user, bool $remember): void
    {
        $code = (string) random_int(100000, 999999);

        $request->session()->put([
            'login_otp_user_id' => $user->user_id,
            'login_otp_hash' => Hash::make($code),
            'login_otp_expires_at' => now()->addMinutes(10),
            'login_otp_remember' => $remember,
            'login_otp_attempts' => 0,
        ]);

        try {
            Mail::raw("Your LJS Realty verification code is {$code}. It expires in 10 minutes.", function ($message) use ($user) {
                $message->to($user->email, $user->name)->subject('LJS Realty login verification code');
            });
        } catch (\Throwable $exception) {
            Log::error('Login OTP could not be sent.', ['user_id' => $user->user_id, 'error' => $exception->getMessage()]);
            throw ValidationException::withMessages(['email' => 'We could not send your verification code. Please try again.']);
        }
    }

    /**
     * Register a public end-user account and start its session.
     */
    public function register(Request $request)
    {
        try {
            \App\Support\InputRules::normalize($request);
            $validated = $request->validate([
                'name' => \App\Support\InputRules::name(),
                'email' => \App\Support\InputRules::email(['unique:users,email']),
                'password' => ['required', 'confirmed', 'min:8'],
            ], \App\Support\InputRules::messages() + ['password.confirmed' => 'Passwords do not match.']);
        } catch (ValidationException $exception) {
            $request->session()->flash('auth_modal_error', true);
            throw $exception;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'account_status' => 'Active',
            'role_name' => 'Client',
        ]);

        Auth::logout();
        $request->session()->regenerate();
        $this->sendOtp($request, $user, false);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'A verification code was sent to your email address.',
                'otp' => true,
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->route('login.otp');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        \App\Support\InputRules::normalize($request, ['name'], ['contact_number']);
        $rules = [
            'name' => \App\Support\InputRules::name(),
            'email' => \App\Support\InputRules::email(['unique:users,email,' . $user->user_id . ',user_id']),
            'contact_number' => \App\Support\InputRules::phone(),
            'timezone' => ['nullable', 'timezone'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        if ($user->isProfessional()) {
            $rules['license_number'] = ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9\-\/ ]+$/'];
            $rules['professional_bio'] = ['nullable', 'string', 'max:2000'];
            $rules['credential_document'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
        }

        $credentialLabel = $user->role_name === 'Agent' ? 'accreditation number' : 'PRC license number';
        $validated = $request->validate($rules, \App\Support\InputRules::messages() + [
            'license_number.required' => 'Please input your ' . $credentialLabel . '.',
            'license_number.regex' => 'The ' . $credentialLabel . ' may only contain letters, numbers, spaces, hyphens and slashes.',
        ]);

        $profileFields = [];
        if ($user->isProfessional()) {
            $profileFields = [
                'license_number' => $validated['license_number'] ?? null,
                'professional_bio' => $validated['professional_bio'] ?? null,
            ];
            unset($validated['license_number'], $validated['professional_bio'], $validated['credential_document']);
        }

        unset($validated['profile_photo']);
        $oldPhoto = $user->profile_photo;
        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user->update($validated);

        if ($request->hasFile('profile_photo') && $oldPhoto && ! filter_var($oldPhoto, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($oldPhoto);
        }

        if ($user->isProfessional()) {
            // A credential on file (added by an admin or earlier) is kept; only a missing one can be added here.
            if ($request->hasFile('credential_document') && ! $user->professionalProfile?->document_file) {
                $profileFields['document_file'] = $request->file('credential_document')->store('professional-credentials', 'local');
            }

            $user->professionalProfile()->updateOrCreate([], $profileFields);
        }

        return redirect()->back()->with('success', 'Profile details updated successfully.');
    }

    public function downloadOwnCredentials(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isProfessional(), 403);

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

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], ['password.confirmed' => 'Passwords do not match.']);

        $user->update(['password' => Hash::make($validated['password'])]);

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    public function destroyAccount(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Your account has been deactivated.');
    }
}
