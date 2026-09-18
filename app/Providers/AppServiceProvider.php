<?php

namespace App\Providers;

use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

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
        // Runs on every render of the sidebar/navbar so the "acting as" state and
        // page header are always correct, regardless of which route rendered the
        // page. sidebar.blade.php builds its own link list from config('roles.pages'),
        // so this only needs to supply the lead-broker toggle state and page title.
        View::composer(['layouts.app', 'layouts.sidebar', 'layouts.navbar'], function ($view) {
            $user = Auth::user();
            $isLeadBroker = false;
            $acting = null;
            $pageLabel = null;
            $unreadNotificationCount = 0;
            $recentNotifications = collect();

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

                $currentPage = request()->route('page');
                if ($currentPage) {
                    $pageLabel = ucfirst(str_replace(['-', '_'], ' ', $currentPage));
                } elseif (request()->routeIs('dashboard')) {
                    $pageLabel = 'Dashboard';
                }
            }

            $view->with([
                'roleToggle' => $isLeadBroker,
                'actingAs' => $acting,
                'pageLabel' => $pageLabel,
                'unreadNotificationCount' => $unreadNotificationCount,
                'recentNotifications' => $recentNotifications,
            ]);
        });
    }
}
