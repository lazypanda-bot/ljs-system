<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('layouts.client');
});

// TEMPORARY FOR TESTING
Route::post('/temp-login', function (Request $request) {
    // Temporary hardcoded accounts for development/testing only.
    $credentials = [
        'admin@example.com' => ['password' => 'admin', 'role' => 'admin', 'name' => 'Temporary Admin'],
        'agent@example.com' => ['password' => 'agent', 'role' => 'agent', 'name' => 'Temporary Agent'],
        'broker@example.com' => ['password' => 'broker', 'role' => 'broker', 'name' => 'Temporary Broker']
    ];

    $email = $request->input('email');
    $password = $request->input('password');

    if (! isset($credentials[$email]) || $credentials[$email]['password'] !== $password) {
        return redirect('/')->with('login_error', 'Invalid temporary credentials.');
    }

    $entry = $credentials[$email];
    $roleId = DB::table('roles')->where('role_name', $entry['role'])->value('role_id');
    if (! $roleId) {
        $roleId = DB::table('roles')->insertGetId([
            'role_name' => $entry['role'],
        ]);
    }

    $user = DB::table('users')->where('email', $email)->first();
    if (! $user) {
        $userId = DB::table('users')->insertGetId([
            'name' => $entry['name'],
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = DB::table('users')->where('user_id', $userId)->first();
    } else {
        DB::table('users')->where('user_id', $user->user_id)->update([
            'password' => Hash::make($password),
            'role_id' => $roleId,
            'updated_at' => now(),
        ]);
    }

    Auth::loginUsingId($user->user_id);

    return redirect('/');
})->name('temp.login');

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', fn () => view('admin.admin-dashboard'))->name('dashboard');
    Route::get('/users', fn () => view('admin.admin-users'))->name('users');
    Route::get('/messages', fn () => view('admin.admin-messages'))->name('messages');
    Route::get('/property', fn () => view('admin.admin-properties'))->name('property');
    Route::get('/appointments', fn () => view('admin.admin-appointments'))->name('appointments');
    Route::get('/review', fn () => view('admin.admin-review'))->name('review');
    Route::get('/reports', fn () => view('admin.admin-reports'))->name('reports');
});

Route::middleware('auth')->prefix('agent')->name('agent.')->group(function () {
    Route::get('/dashboard', fn () => view('agent.agent-dashboard'))->name('dashboard');
    Route::get('/users', fn () => view('agent.agent-users'))->name('users');
    Route::get('/messages', fn () => view('agent.agent-messages'))->name('messages');
    Route::get('/listings', fn () => view('agent.agent-listings'))->name('listings');
    Route::get('/appointment', fn () => view('agent.agent-appointment'))->name('appointment');
    Route::get('/review', fn () => view('agent.agent-review'))->name('review');
    Route::get('/reports', fn () => view('agent.agent-report'))->name('reports');
});

Route::post('/lead/toggle', function () {
    if (! Auth::check()) {
        return redirect('/');
    }

    $current = session('acting_as', 'broker');
    $next = $current === 'admin' ? 'broker' : 'admin';
    session(['acting_as' => $next]);

    return redirect()->back();
})->name('lead.toggle')->middleware('auth');
