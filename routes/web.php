<?php

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PropertyController;

Route::get('/', function () {
    $properties = Property::with('images')->latest()->get();

    return view('layouts.app', compact('properties'));
})->name('home');

Route::get('/properties', function () {
    $properties = Property::with('images')->latest()->get();

    return view('layouts.app', compact('properties'));
})->name('properties');

Route::get('/agents', function () {
    return view('layouts.app');
})->name('agents');

Route::get('/about', function () {
    return view('layouts.app');
})->name('about');

Route::post('/admin/properties', [PropertyController::class, 'store'])->name('admin.properties.store');

Route::post('/admin/properties/{id}/generate-referral', [PropertyController::class, 'generateReferral'])
    ->name('agent.properties.generate-code');

// TEMPORARY FOR TESTING
Route::get('/temp-login', function () {
    return redirect('/')->with('login_error', 'Please sign in using the form below.');
});

Route::post('/temp-login', function (Request $request) {
    // Temporary hardcoded accounts for development/testing only.
    $credentials = [
        'admin@example.com' => ['password' => 'admin', 'role' => 'admin', 'name' => 'Temporary Admin'],
        'agent@example.com' => ['password' => 'agent', 'role' => 'agent', 'name' => 'Temporary Agent'],
        'broker@example.com' => ['password' => 'broker', 'role' => 'broker', 'name' => 'Temporary Broker'],
        'leadbroker@example.com' => ['password' => 'lead', 'role' => 'lead_broker', 'name' => 'Temporary Lead Broker'],
        'customer@example.com' => ['password' => 'customer', 'role' => 'customer', 'name' => 'Temporary Customer'],
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

    $role = $entry['role'];

    if ($role === 'customer') {
        return redirect()->route('properties');
    }

    if ($role === 'lead_broker') {
        session(['acting_as' => 'broker']);
        return redirect()->route('app.page', ['page' => 'dashboard']);
    }

    if (in_array($role, ['admin', 'broker', 'agent'], true)) {
        return redirect()->route('app.page', ['page' => 'dashboard']);
    }

    return redirect()->route('home');
})->name('temp.login');

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');

// Legacy role-specific routes were replaced with a single dynamic shared page route.
// Keep this commented-out reference while we consolidate the UI.
// Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
//     Route::get('/dashboard', fn () => view('admin.admin-dashboard'))->name('dashboard');
//     Route::get('/users', fn () => view('admin.admin-users'))->name('users');
//     Route::get('/messages', fn () => view('admin.admin-messages'))->name('messages');
//     Route::get('/property', fn () => view('admin.admin-properties'))->name('property');
//     Route::get('/appointments', fn () => view('admin.admin-appointments'))->name('appointments');
//     Route::get('/review', fn () => view('admin.admin-review'))->name('review');
//     Route::get('/reports', fn () => view('admin.admin-reports'))->name('reports');
// });
// 
// Route::middleware('auth')->prefix('agent')->name('agent.')->group(function () {
//     Route::get('/dashboard', fn () => view('agent.agent-dashboard'))->name('dashboard');
//     Route::get('/users', fn () => view('agent.agent-users'))->name('users');
//     Route::get('/messages', fn () => view('agent.agent-messages'))->name('messages');
//     Route::get('/listings', fn () => view('agent.agent-listings'))->name('listings');
//     Route::get('/appointment', fn () => view('agent.agent-appointment'))->name('appointment');
//     Route::get('/review', fn () => view('agent.agent-review'))->name('review');
//     Route::get('/reports', fn () => view('agent.agent-report'))->name('reports');
// });

Route::middleware('auth')->get('/app/{page}', function ($page) {
    $user = Auth::user();
    $role = 'guest';
    if (! empty($user?->role_id)) {
        $role = DB::table('roles')->where('role_id', $user->role_id)->value('role_name') ?? 'guest';
    }
    $acting = $role;
    if ($role === 'lead_broker') {
        $acting = session('acting_as', 'broker');
    }

    $rolePages = [
        'admin' => ['dashboard', 'users', 'messages', 'property', 'appointments', 'review', 'report', 'settings'],
        'broker' => ['dashboard', 'agents', 'messages', 'listings', 'appointments', 'review', 'report', 'settings'],
        'agent' => ['dashboard', 'messages', 'listings', 'appointment', 'review', 'report', 'settings'],
    ];

    if (! isset($rolePages[$acting]) || ! in_array($page, $rolePages[$acting], true)) {
        abort(403);
    }

    $pageTitle = ucfirst(str_replace(['-', '_'], ' ', $page));
    $properties = Property::with('images')->latest()->get();

    return view('shared.page', compact('acting', 'page', 'pageTitle', 'properties'));
})->name('app.page');

Route::post('/lead/toggle', function () {
    if (! Auth::check()) {
        return redirect('/');
    }

    $current = session('acting_as', 'broker');
    $next = $current === 'admin' ? 'broker' : 'admin';
    session(['acting_as' => $next]);

    return redirect()->back();
})->name('lead.toggle')->middleware('auth');

Route::get('/', function () {
    $collection = Property::with('images')->latest()->get();
    return view('layouts.app', compact('collection'));
})->name('home');

// please work
// Route::get('/', function () {
//     $properties = Property::with('images')
//         ->whereNotIn('property_status', ['Pending', 'Unavailable']) 
//         ->latest()
//         ->get();

//     $collection = $properties; 

//     return view('layouts.app', compact('properties', 'collection'));
// })->name('home');

// MAP
Route::get('/', function () {
    $properties = Property::with('images')->latest()->get();
    $collection = $properties; // Keep both if other parts of your page use $collection

    return view('layouts.app', compact('properties', 'collection'));
})->name('home');

// pending properties do noy show
Route::get('/', function () {
    // Only fetch properties that are approved for public viewing
    $properties = Property::with('images')
        ->whereNotIn('property_status', ['Pending', 'Unavailable']) 
        ->latest()
        ->get();

    return view('layouts.app', compact('properties'));
})->name('home');