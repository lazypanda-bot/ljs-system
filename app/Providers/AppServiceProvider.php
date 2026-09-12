<?php

namespace App\Providers;

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
        View::composer(['layouts.sidebar', 'layouts.navbar'], function ($view) {
            $user = Auth::user();
            $isLeadBroker = false;
            $acting = null;
            $pageLabel = null;

            if ($user) {
                $isLeadBroker = $user->normalizedRole() === 'lead_broker';
                $acting = $user->actingRole();

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
            ]);
        });
    }
}
