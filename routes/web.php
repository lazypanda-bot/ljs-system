<?php

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\LifestyleController;

// HOME / LANDING PAGE
Route::get('/', function () {
    $properties = Property::with(['images', 'details'])
        ->whereNotIn('property_status', ['Pending']) // Removed 'Unavailable' here
        ->latest()
        ->get();

    $collection = $properties; 

    $rushProperties = Property::with('images')
        ->where('rush', true)
        ->latest()
        ->get();

    return view('customer.home', compact('properties', 'collection', 'rushProperties'));
})->name('home');

// PROPERTIES CATALOG
Route::get('/properties', function () {
    $properties = Property::with('images')->get();
    return view('customer.properties', compact('properties'));
})->name('properties');

// ABOUT
Route::get('/about', function () {
    return view('customer.about');
})->name('about');

// ADMIN PROPERTY MANAGEMENT ACTIONS
Route::post('/admin/properties', [PropertyController::class, 'store'])->name('admin.properties.store');

Route::post('/admin/properties/{id}/generate-referral', [PropertyController::class, 'generateReferral'])
    ->name('agent.properties.generate-code');

// LIFESTYLE
Route::get('/lifestyle-match', [LifestyleController::class, 'index'])->name('lifestyle.match');
Route::post('/lifestyle-match/results', [LifestyleController::class, 'results'])->name('lifestyle.match.results');

// ADD PROPERTY
Route::get('/admin/properties/create', [PropertyController::class, 'create'])->name('admin.subfiles.addproperty');

// VIEW DETAILS
Route::get('/properties/{id}', [PropertyController::class, 'show'])->name('properties.show');

// ALL AGENTS
Route::get('/agents', [AgentController::class, 'agents'])->name('agents');

// USERS
Route::get('/app/users', [UserController::class, 'index']);

// AUTHENTICATION ROUTES
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// AUTHENTICATED ROUTES
Route::middleware('auth')->group(function () {
    // Single dashboard implementation lives at /app/dashboard; this is just the
    // well-known post-login landing URL.
    Route::get('/dashboard', function () {
        return redirect()->route('app.page', ['page' => 'dashboard']);
    })->name('dashboard');
});


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
    $acting = $user->actingRole();

    $rolePages = config('roles.pages');

    if (! isset($rolePages[$acting]) || ! in_array($page, $rolePages[$acting], true)) {
        abort(403);
    }

    $pageTitle = ucfirst(str_replace(['-', '_'], ' ', $page));

    if ($page === 'dashboard') {
        $properties = Property::visibleTo($user)->with('details')->get();
        $totalProperties = $properties->count();

        $statusBuckets = $properties->groupBy(function (Property $property) {
            return in_array($property->property_status, ['For Sale', 'Pending', 'Sold'], true)
                ? $property->property_status
                : 'Inactive';
        });

        $forSaleProperties = $statusBuckets->get('For Sale', collect())->count();
        $pendingProperties = $statusBuckets->get('Pending', collect())->count();
        $soldProperties = $statusBuckets->get('Sold', collect())->count();
        $inactiveProperties = $statusBuckets->get('Inactive', collect())->count();

        $dashboardMode = $acting === 'admin' ? 'org' : 'personal';
        $totalBrokers = null;
        $totalAgents = null;

        if ($dashboardMode === 'org') {
            $totalBrokers = \App\Models\User::where('role_name', 'Broker')
                ->where('account_status', 'Active')
                ->count();
            $totalAgents = \App\Models\User::where('role_name', 'Agent')
                ->where('account_status', 'Active')
                ->count();
        }

        return view('dashboard', compact(
            'totalProperties', 'totalBrokers', 'totalAgents', 'dashboardMode',
            'forSaleProperties', 'pendingProperties', 'soldProperties', 'inactiveProperties',
            'acting', 'pageTitle'
        ));
    }

    $properties = Property::visibleTo($user)->with(['images', 'details'])->latest()->get();

    return view('shared.page', compact('acting', 'page', 'pageTitle', 'properties'));
})->name('app.page');

Route::post('/lead/toggle', function () {
    $user = Auth::user();

    if (! $user || $user->normalizedRole() !== 'lead_broker') {
        return redirect()->back();
    }

    $current = session('acting_as', 'broker');
    $next = $current === 'admin' ? 'broker' : 'admin';
    session(['acting_as' => $next]);

    return redirect()->back();
})->name('lead.toggle')->middleware('auth');

// ADMIN USER MANAGEMENT
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('teams', TeamController::class);
});