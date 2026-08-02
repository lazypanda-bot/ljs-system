<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        View::composer(['layouts.sidebar', 'layouts.navbar'], function ($view) {
            $user = Auth::user();
            $isLeadBroker = false;
            $acting = null;

            if (Auth::check()) {
                $role = 'guest';
                if (! empty($user?->role_id)) {
                    $role = DB::table('roles')->where('role_id', $user->role_id)->value('role_name') ?? 'guest';
                }

                $isLeadBroker = $role === 'lead_broker';

                // lead_broker toggles between broker/admin view; all other logged in users use their own role.
                if ($isLeadBroker) {
                    $acting = session('acting_as', 'broker');
                } else {
                    $acting = $role;
                }
            }

            $links = [];

            if ($acting === 'admin') {
                $links = [
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'route' => 'admin.dashboard', 'icon' => 'dashboard'],
                    ['label' => 'Users', 'url' => route('admin.users'), 'route' => 'admin.users', 'icon' => 'users'],
                    ['label' => 'Messages', 'url' => route('admin.messages'), 'route' => 'admin.messages', 'icon' => 'messages'],
                    ['label' => 'Properties', 'url' => route('admin.property'), 'route' => 'admin.property', 'icon' => 'property'],
                    ['label' => 'Appointments', 'url' => route('admin.appointments'), 'route' => 'admin.appointments', 'icon' => 'appointment'],
                    ['label' => 'Reviews', 'url' => route('admin.review'), 'route' => 'admin.review', 'icon' => 'review'],
                    ['label' => 'Reports', 'url' => route('admin.reports'), 'route' => 'admin.reports', 'icon' => 'report'],
                ];
            } elseif ($acting === 'broker') {
                $links = [
                    ['label' => 'Dashboard', 'url' => route('broker.dashboard'), 'route' => 'broker.dashboard', 'icon' => 'dashboard'],
                    ['label' => 'Agents', 'url' => route('broker.agents'), 'route' => 'broker.agents', 'icon' => 'users'],
                    ['label' => 'Messages', 'url' => route('broker.messages'), 'route' => 'broker.messages', 'icon' => 'messages'],
                    ['label' => 'My Listings', 'url' => route('broker.listings'), 'route' => 'broker.listings', 'icon' => 'property'],
                    ['label' => 'Appointments', 'url' => route('broker.appointments'), 'route' => 'broker.appointments', 'icon' => 'appointment'],
                    ['label' => 'Reviews', 'url' => route('broker.reviews'), 'route' => 'broker.reviews', 'icon' => 'review'],
                    ['label' => 'Reports', 'url' => route('broker.reports'), 'route' => 'broker.reports', 'icon' => 'report'],
                ];
            } elseif ($acting === 'agent') {
                $links = [
                    ['label' => 'Dashboard', 'url' => route('agent.dashboard'), 'route' => 'agent.dashboard', 'icon' => 'dashboard'],
                    ['label' => 'Messages', 'url' => route('agent.messages'), 'route' => 'agent.messages', 'icon' => 'messages'],
                    ['label' => 'My Listings', 'url' => route('agent.listings'), 'route' => 'agent.listings', 'icon' => 'property'],
                    ['label' => 'Appointments', 'url' => route('agent.appointment'), 'route' => 'agent.appointment', 'icon' => 'appointment'],
                    ['label' => 'Reviews', 'url' => route('agent.review'), 'route' => 'agent.review', 'icon' => 'review'],
                    ['label' => 'Reports', 'url' => route('agent.reports'), 'route' => 'agent.reports', 'icon' => 'report'],
                ];
            } else {
                // guest: no sidebar links
                $links = [];
            }

            // determine active page label by matching current URL or route (if provided)
            $activeLabel = null;
            $currentUrl = url()->current();
            foreach ($links as $l) {
                if (! empty($l['url']) && $l['url'] !== '#' && url($l['url']) === $currentUrl) {
                    $activeLabel = $l['label'];
                    break;
                }
                if (! empty($l['route']) && 
                    method_exists(
                        \Illuminate\Support\Facades\Route::class,
                        'currentRouteName'
                    ) && \Illuminate\Support\Facades\Route::currentRouteName() === $l['route']) {
                    $activeLabel = $l['label'];
                    break;
                }
            }

            $view->with([
                'sidebarLinks' => $links,
                'roleToggle' => $isLeadBroker,
                'actingAs' => $acting,
                'pageLabel' => $activeLabel,
            ]);
        });
    }
}
