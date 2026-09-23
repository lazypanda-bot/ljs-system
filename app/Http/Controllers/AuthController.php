<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', 'min:8'],
            ]);
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

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->user_id . ',user_id'],
            'contact_number' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile details updated successfully.');
    }
}
