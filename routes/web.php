<?php

use App\Models\Appointment;
use App\Models\Property;
use App\Models\User;
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
use App\Http\Controllers\MessageController;
use App\Http\Controllers\StaffPropertyController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ContactController;

// HOME / LANDING PAGE
Route::get('/', function () {
    $properties = Property::with(['images', 'details'])
        ->where('approval_status', 'Approved')
        ->latest()
        ->get();

    $collection = $properties; 

    $featuredProperties = Property::with(['images', 'details', 'user.professionalProfile'])
        ->where('featured', true)
        ->where('approval_status', 'Approved')
        ->latest()
        ->take(5)
        ->get();

    $topProfessionals = User::query()
        ->with('professionalProfile')
        ->withCount('receivedReviews')
        ->withAvg('receivedReviews', 'rating')
        ->where('account_status', 'Active')
        ->whereIn('role_name', ['Broker', 'Agent', 'Lead Broker'])
        ->orderByDesc('received_reviews_count')
        ->orderByDesc('received_reviews_avg_rating')
        ->take(5)
        ->get();

    return view('customer.home', compact('properties', 'collection', 'featuredProperties', 'topProfessionals'));
})->name('home');

// PROPERTIES CATALOG
Route::get('/properties', function (Request $request) {
    $approvedProperties = Property::query()->where('approval_status', 'Approved');
    $propertyTypes = (clone $approvedProperties)
        ->whereNotNull('property_type')
        ->whereRaw("TRIM(property_type) != ''")
        ->distinct()
        ->orderBy('property_type')
        ->pluck('property_type');
    $propertyLocations = (clone $approvedProperties)
        ->select('city as location')
        ->whereNotNull('city')
        ->whereRaw("TRIM(city) != ''")
        ->distinct()
        ->pluck('location')
        ->merge((clone $approvedProperties)
            ->select('province as location')
            ->whereNotNull('province')
            ->whereRaw("TRIM(province) != ''")
            ->distinct()
            ->pluck('location'))
        ->map(fn ($location) => trim($location))
        ->filter()
        ->unique()
        ->sort(SORT_NATURAL | SORT_FLAG_CASE)
        ->values();

    $properties = (clone $approvedProperties)
        ->with(['images', 'details'])
        ->when($request->filled('type'), fn ($query) => $query->where('property_type', $request->string('type')))
        ->when($request->filled('location'), fn ($query) => $query->where(function ($query) use ($request) {
            $location = $request->string('location');
            $query->where('city', $location)->orWhere('province', $location);
        }))
        ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request) {
            $search = $request->string('search');
            $query->where('property_name', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")
                ->orWhere('province', 'like', "%{$search}%");
        }))
        ->when($request->price_range === '0-5m', fn ($query) => $query->whereBetween('price', [0, 5000000]))
        ->when($request->price_range === '5m-10m', fn ($query) => $query->whereBetween('price', [5000000, 10000000]))
        ->when($request->price_range === '10m+', fn ($query) => $query->where('price', '>=', 10000000))
        ->when($request->sort === 'price_low', fn ($query) => $query->orderBy('price'))
        ->when($request->sort === 'price_high', fn ($query) => $query->orderByDesc('price'))
        ->when($request->sort === 'latest', fn ($query) => $query->latest())
        ->when($request->sort === 'featured' || ! $request->filled('sort'), fn ($query) => $query->orderByDesc('featured')->latest())
        ->paginate(12)
        ->withQueryString();
    return view('customer.properties', compact('properties', 'propertyTypes', 'propertyLocations'));
})->name('properties');

// ABOUT
Route::get('/about', function () {
    return view('customer.about');
})->name('about');

Route::get('/contact', function () {
    return view('customer.contact');
})->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.submit');

// ADMIN PROPERTY MANAGEMENT ACTIONS
Route::middleware('auth')->group(function () {
    Route::post('/admin/properties', [PropertyController::class, 'store'])->name('admin.properties.store');
    Route::post('/admin/properties/{property}/review', [PropertyController::class, 'reviewListing'])->name('admin.properties.review');
    Route::patch('/admin/properties/{property}/status', [PropertyController::class, 'updateStatus'])->name('admin.properties.status');
    Route::delete('/admin/properties/{property}', [PropertyController::class, 'destroy'])->name('admin.properties.destroy');
    Route::get('/admin/properties/{property}/edit', [PropertyController::class, 'edit'])->name('admin.properties.edit');
    Route::put('/admin/properties/{property}', [PropertyController::class, 'update'])->name('admin.properties.update');
});

Route::post('/admin/properties/{id}/generate-referral', [PropertyController::class, 'generateReferral'])
    ->middleware('auth')
    ->name('agent.properties.generate-code');

// LIFESTYLE
Route::get('/lifestyle-match', [LifestyleController::class, 'index'])->name('lifestyle.match');
Route::post('/lifestyle-match/results', [LifestyleController::class, 'results'])->name('lifestyle.match.results');

// ADD PROPERTY
Route::get('/admin/properties/create', [PropertyController::class, 'create'])->middleware('auth')->name('admin.subfiles.addproperty');

// VIEW DETAILS
Route::get('/r/{code}', [ReferralController::class, 'open'])->name('referrals.open');
Route::get('/properties/{id}', [PropertyController::class, 'show'])->name('properties.show');
Route::get('/app/listings/{property}', [StaffPropertyController::class, 'show'])->middleware('auth')->name('staff.properties.show');

// ALL AGENTS
Route::get('/agents', [AgentController::class, 'agents'])->name('agents');
Route::get('/agents/{professional}', [AgentController::class, 'show'])->name('agents.show');
Route::get('/app/appointments', [AppointmentController::class, 'index'])->middleware('auth')->name('appointments.index');
Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->middleware('auth')->name('appointments.status');
Route::post('/appointments/{appointment}/proposals', [AppointmentController::class, 'proposeTime'])->middleware('auth')->name('appointments.proposals.store');
Route::get('/appointments/{appointment}/availability', [AppointmentController::class, 'proposalAvailability'])->middleware('auth')->name('appointments.proposals.availability');
Route::patch('/appointment-proposals/{proposal}', [AppointmentController::class, 'respondToProposal'])->middleware('auth')->name('appointments.proposals.respond');
Route::post('/appointments/block-date', [AppointmentController::class, 'blockDate'])->middleware('auth')->name('appointments.block-date');
Route::get('/appointments/active-count', [AppointmentController::class, 'activeCount'])->middleware('auth')->name('appointments.active-count');
Route::get('/properties/{property}/appointments/create', [AppointmentController::class, 'create'])->middleware('auth')->name('appointments.create');
Route::get('/properties/{property}/appointments/availability', [AppointmentController::class, 'availability'])->middleware('auth')->name('appointments.availability');
Route::post('/properties/{property}/appointments', [AppointmentController::class, 'store'])->middleware('auth')->name('appointments.store');
Route::get('/app/reports/export', [ReportController::class, 'export'])->middleware('auth')->name('reports.export');
Route::get('/messages', [MessageController::class, 'index'])->middleware('auth')->name('messages.inbox');
Route::delete('/messages/conversations/{conversation}', [MessageController::class, 'deleteConversation'])->middleware('auth')->name('messages.conversations.delete');
Route::get('/messages/attachments/{message}', [MessageController::class, 'downloadAttachment'])->middleware('auth')->name('messages.attachments.download');
Route::get('/messages/direct/{recipient}', [MessageController::class, 'direct'])->middleware('auth')->name('messages.direct');
Route::post('/messages/compose', [MessageController::class, 'compose'])->middleware('auth')->name('messages.compose');
Route::post('/properties/{property}/message', [MessageController::class, 'propertyDirect'])->middleware('auth')->name('properties.message');
Route::post('/messages/{conversation}', [MessageController::class, 'store'])->middleware('auth')->name('messages.store');
Route::delete('/messages/{message}', [MessageController::class, 'destroyMessage'])->middleware('auth')->name('messages.destroy');
Route::patch('/messages/{message}', [MessageController::class, 'updateMessage'])->middleware('auth')->name('messages.update');
Route::get('/messages/{conversation}/updates', [MessageController::class, 'updates'])->middleware('auth')->name('messages.updates');
Route::get('/notifications/count', [MessageController::class, 'notificationCount'])->middleware('auth')->name('notifications.count');
Route::get('/notifications', [MessageController::class, 'notifications'])->middleware('auth')->name('notifications.index');
Route::get('/notifications/{notification}', [MessageController::class, 'openNotification'])->middleware('auth')->name('notifications.open');
Route::post('/agents/{professional}/reviews', [AgentController::class, 'storeReview'])
    ->middleware('auth')
    ->name('agents.reviews.store');

// USERS
Route::get('/app/users', [UserController::class, 'index'])->middleware('auth');

// AUTHENTICATION ROUTES
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/login/otp', [AuthController::class, 'showOtp'])->name('login.otp');
Route::post('/login/otp', [AuthController::class, 'verifyOtp'])->name('login.otp.verify');
Route::post('/login/otp/resend', [AuthController::class, 'resendOtp'])->name('login.otp.resend');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1')->name('password.forgot');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1')->name('password.reset');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::post('/settings/profile', [AuthController::class, 'updateProfile'])->name('settings.profile.update')->middleware('auth');
Route::get('/settings/credentials', [AuthController::class, 'downloadOwnCredentials'])->name('settings.credentials.download')->middleware('auth');
Route::post('/settings/password', [AuthController::class, 'updatePassword'])->name('settings.password.update')->middleware('auth');
Route::delete('/settings/account', [AuthController::class, 'destroyAccount'])->name('settings.account.destroy')->middleware('auth');

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



Route::middleware('auth')->get('/app/messages', [MessageController::class, 'index'])->name('app.messages');

Route::middleware('auth')->get('/app/{page}', function ($page) {
    $user = Auth::user();

    if (! ($user instanceof User)) {
        abort(403);
    }

    $acting = $user->actingRole();

    if ($page === 'client-profile') {
        return redirect()->route('app.page', ['page' => 'settings']);
    }

    $rolePages = config('roles.pages');

    if (! isset($rolePages[$acting]) || ! in_array($page, $rolePages[$acting], true)) {
        abort(403);
    }

    $pageTitle = ucfirst(str_replace(['-', '_'], ' ', $page));

    if ($page === 'appointments') {
        return app(AppointmentController::class)->index(request());
    }
    if ($page === 'appointment') {
        return app(AppointmentController::class)->index(request());
    }
    if ($page === 'review') return app(ReviewController::class)->index(request());
    if ($page === 'report') return app(ReportController::class)->index(request());

    if ($page === 'agents') {
        return app(\App\Http\Controllers\ManagedAgentsController::class)->index(request());
    }

    if ($page === 'dashboard') {
        $calendarMonth = (int) request('month', now()->month);
        $calendarYear = (int) request('year', now()->year);
        $calendarTimezone = $user->timezone ?: config('appointments.default_timezone');
        $calendarStart = \Carbon\CarbonImmutable::create($calendarYear, $calendarMonth, 1, 0, 0, 0, $calendarTimezone);
        $calendarEnd = $calendarStart->endOfMonth();
        $calendarStaffIds = collect([(int) $user->user_id]);
        $properties = Property::visibleTo($user)->with('details')->get();
        $totalProperties = $properties->count();

        $calendarAppointmentsQuery = \App\Models\Appointment::query()
            ->with(['assignment.property', 'assignment.user'])
            ->where(function ($query) use ($calendarStart, $calendarEnd) {
                $query->whereBetween('starts_at_utc', [$calendarStart->utc()->format('Y-m-d H:i:s'), $calendarEnd->utc()->format('Y-m-d H:i:s')])
                    ->orWhere(function ($query) use ($calendarStart, $calendarEnd) {
                        $query->whereNull('starts_at_utc')
                            ->whereBetween('preferred_date', [$calendarStart->subDay()->toDateString(), $calendarEnd->addDay()->toDateString()]);
                    });
            });

        if ($acting === 'client') {
            $calendarAppointmentsQuery->where(function ($query) use ($user) {
                $query->where('client_id', $user->user_id)
                    ->orWhere(function ($query) use ($user) {
                        $query->whereNull('client_id')->where('contact_email', $user->email);
                    });
            });
        } else {
            // Staff calendars only show the viewer's own appointments; a Lead Broker's admin and broker modes stay separate.
            $calendarAppointmentsQuery->whereHas('assignment', fn ($query) => $query->whereIn('user_id', $calendarStaffIds));
            $calendarAppointmentsQuery->whereHas('assignment.property', fn ($property) => $acting === 'admin'
                ? $property->where('listing_mode', 'admin')
                : $property->where(fn ($mode) => $mode->whereNull('listing_mode')->orWhere('listing_mode', '!=', 'admin')));
        }

        $calendarAppointments = $calendarAppointmentsQuery->get()->map(function ($appointment) use ($calendarTimezone) {
            $localStart = $appointment->startInTimezone($calendarTimezone);

            return [
                'preferred_date' => $localStart->toDateString(),
                'preferred_time' => $localStart->format('H:i'),
                'contact_name' => $appointment->contact_name,
                'property_name' => $appointment->assignment?->property?->property_name ?? 'Property appointment',
                'professional_name' => $appointment->assignment?->user?->name,
                'appointment_status' => $appointment->appointment_status,
            ];
        });

        $calendarBlockedDates = $acting === 'client'
            ? collect()
            : \App\Models\BlockedDate::query()
                ->whereBetween('date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
                ->where('user_id', $user->user_id)
                ->when($acting === 'admin', fn ($query) => $query->where(fn ($query) => $query
                    ->where('acting_role', 'admin')
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('role_name', 'Admin'))))
                ->when($acting !== 'admin', fn ($query) => $query
                    ->where(fn ($query) => $query->whereNull('acting_role')->orWhere('acting_role', '!=', 'admin')))
                ->with('user')
                ->get()
                ->map(fn ($blocked) => [
                    'date' => $blocked->date instanceof \DateTimeInterface ? $blocked->date->format('Y-m-d') : substr((string) $blocked->date, 0, 10),
                    'start_time' => $blocked->start_time,
                    'end_time' => $blocked->end_time,
                    'reason' => $blocked->reason,
                    'blocked_by' => $blocked->user?->name,
                ]);
        $recentActivities = \App\Models\AppNotification::visibleTo($user)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($notification) => (object) [
                'title' => $notification->title,
                'description' => $notification->body,
                'created_at' => $notification->created_at,
                'icon' => match ($notification->type) {
                    'message' => 'fas fa-comment',
                    'team' => 'fas fa-users',
                    default => 'fas fa-house',
                },
                'icon_class' => match ($notification->type) {
                    'message' => 'activity-message',
                    'team' => 'activity-team',
                    default => 'activity-listing',
                },
            ]);

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

        return view('pages.dashboard', compact(
            'totalProperties', 'totalBrokers', 'totalAgents', 'dashboardMode',
            'forSaleProperties', 'pendingProperties', 'soldProperties', 'inactiveProperties',
            'acting', 'pageTitle', 'calendarAppointments', 'calendarBlockedDates', 'recentActivities'
        ));
    }

    if ($page === 'settings') {
        $user->loadMissing('professionalProfile');
        $appointments = $user->normalizedRole() === 'client'
            ? \App\Models\Appointment::query()
                ->with(['assignment.property', 'assignment.user'])
                ->where(function ($query) use ($user) {
                    $query->where('client_id', $user->user_id)
                        ->orWhere(function ($query) use ($user) {
                            $query->whereNull('client_id')->where('contact_email', $user->email);
                        });
                })
                ->orderBy('starts_at_utc')
                ->get()
            : collect();

        return view('pages.settings', compact('acting', 'page', 'pageTitle', 'appointments'));
    }

    $assignableUsers = $page === 'property'
        ? User::where('account_status', 'Active')->whereIn('role_name', ['Broker', 'Agent', 'Lead Broker'])->orderBy('name')->get()
        : collect();

    $properties = Property::visibleTo($user)->with(['images', 'details', 'user', 'assignments.user', 'referralCodes'])->latest()->get();
    $pendingListings = $page === 'property'
        ? $properties->where('approval_status', 'Pending')->values()
        : collect();
    $myProperties = $page === 'listings'
        ? $properties->filter(fn ($property) => (int) $property->user_id === (int) $user->user_id)->values()
        : collect();
    $teamProperties = collect();
    $sharedPageViews = [
        'messages' => 'pages.messages',
        'listings' => 'pages.listings',
        'appointments' => 'pages.appointments',
        'review' => 'pages.reviews',
        'settings' => 'pages.settings',
    ];
    $pageView = $sharedPageViews[$page] ?? $acting . '.' . $acting . '-' . str_replace('_', '-', $page);

    abort_unless(view()->exists($pageView), 404);

    return view($pageView, compact('acting', 'page', 'pageTitle', 'properties', 'myProperties', 'teamProperties', 'assignableUsers', 'pendingListings'));
})->name('app.page');

Route::post('/lead/toggle', function () {
    $user = Auth::user();

    if (! ($user instanceof User) || $user->normalizedRole() !== 'lead_broker') {
        return redirect()->back();
    }

    $current = session('acting_as', 'broker');
    $next = $current === 'admin' ? 'broker' : 'admin';
    session(['acting_as' => $next]);

    return redirect()->route('app.page', ['page' => 'dashboard']);
})->name('lead.toggle')->middleware('auth');

// ADMIN USER MANAGEMENT
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users/{user}/credentials', [UserController::class, 'downloadCredentials'])->name('users.credentials');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::resource('users', UserController::class);
    Route::resource('teams', TeamController::class);
});