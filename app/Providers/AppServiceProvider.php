<?php

namespace App\Providers;

use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
//for ngrok URL handling
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS only for requests that actually arrive through ngrok.
        $requestHost = request()->getHost();
        if (str_contains($requestHost, 'ngrok-free.dev') || str_contains($requestHost, 'ngrok.io')) {
            URL::forceScheme('https');
        }

        // Runs on every render of the sidebar/navbar so the "acting as" state and
        // page header are always correct, regardless of which route rendered the
        // page. sidebar.blade.php builds its own link list from config('roles.pages'),
        // so this only needs to supply the lead-broker toggle state and page title.
        View::composer(['layouts.app', 'layouts.sidebar', 'layouts.navbar'], function ($view) {
            $user = Auth::user();
            $isLeadBroker = false;
            $acting = null;
            $pageLabel = null;
            $pageIcon = 'fa-chart-pie';
            $unreadNotificationCount = 0;
            $recentNotifications = collect();
            $unreadMessageNotificationCount = 0;
            $unreadOtherNotificationCount = 0;
            $recentMessageNotifications = collect();
            $recentOtherNotifications = collect();

            if ($user instanceof User) {
                $isLeadBroker = $user->normalizedRole() === 'lead_broker';
                $acting = $user->actingRole();
                $unreadNotificationCount = AppNotification::where('user_id', $user->user_id)
                    ->whereNull('read_at')
                    ->count();
                $recentNotifications = AppNotification::where('user_id', $user->user_id)
                    ->whereNull('read_at')
                    ->latest()
                    ->take(5)
                    ->get();

                if ($user->normalizedRole() === 'client') {
                    $unreadMessageNotificationCount = AppNotification::where('user_id', $user->user_id)
                        ->whereNull('read_at')
                        ->where('type', 'message')
                        ->count();
                    $unreadOtherNotificationCount = AppNotification::where('user_id', $user->user_id)
                        ->whereNull('read_at')
                        ->where('type', '!=', 'message')
                        ->count();
                    $recentMessageNotifications = AppNotification::where('user_id', $user->user_id)
                        ->whereNull('read_at')
                        ->where('type', 'message')
                        ->latest()
                        ->take(5)
                        ->get();
                    $recentOtherNotifications = AppNotification::where('user_id', $user->user_id)
                        ->whereNull('read_at')
                        ->where('type', '!=', 'message')
                        ->latest()
                        ->take(5)
                        ->get();
                }

                $currentPage = request()->route('page');
                if ($currentPage) {
                    $pageLabel = match ($currentPage) {
                        'property' => 'Properties',
                        'appointment' => 'Appointments',
                        'review' => 'Reviews',
                        'report' => 'Reports',
                        default => ucfirst(str_replace(['-', '_'], ' ', $currentPage)),
                    };
                    $pageIcon = match ($currentPage) {
                        'users' => 'fa-users',
                        'agents' => 'fa-user-tie',
                        'messages' => 'fa-comments',
                        'property', 'listings' => 'fa-building',
                        'appointment', 'appointments' => 'fa-calendar-check',
                        'review' => 'fa-star',
                        'report' => 'fa-chart-column',
                        'settings' => 'fa-gear',
                        default => 'fa-chart-pie',
                    };
                } elseif (request()->routeIs('dashboard')) {
                    $pageLabel = 'Dashboard';
                } elseif (request()->routeIs('appointments.index')) {
                    $pageLabel = 'Appointments';
                } elseif (request()->routeIs('reports.*')) {
                    $pageLabel = 'Reports';
                    $pageIcon = 'fa-chart-column';
                } elseif (request()->routeIs(['messages.inbox', 'app.messages'])) {
                    $pageLabel = 'Messages';
                    $pageIcon = 'fa-comments';
                } elseif (request()->routeIs('staff.properties.show')) {
                    $pageLabel = 'Property Details';
                    $pageIcon = 'fa-building';
                } elseif (request()->routeIs('admin.users.*')) {
                    $pageLabel = 'Users';
                    $pageIcon = 'fa-users';
                } elseif (request()->is('app/users')) {
                    $pageLabel = 'Users';
                    $pageIcon = 'fa-users';
                } elseif (request()->routeIs('admin.teams.*')) {
                    $pageLabel = 'Teams';
                    $pageIcon = 'fa-users';
                }
            }

            $view->with([
                'roleToggle' => $isLeadBroker,
                'actingAs' => $acting,
                'pageLabel' => $pageLabel,
                'pageIcon' => $pageIcon,
                'unreadNotificationCount' => $unreadNotificationCount,
                'recentNotifications' => $recentNotifications,
                'unreadMessageNotificationCount' => $unreadMessageNotificationCount,
                'unreadOtherNotificationCount' => $unreadOtherNotificationCount,
                'recentMessageNotifications' => $recentMessageNotifications,
                'recentOtherNotifications' => $recentOtherNotifications,
            ]);
        });
    }
}
