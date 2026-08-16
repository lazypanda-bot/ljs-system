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
                // Legacy admin links using separate admin route names:
                // $links = [
                //     ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'icon' => 'dashboard'],
                //     ['label' => 'Users', 'url' => route('admin.users'), 'icon' => 'users'],
                //     ['label' => 'Messages', 'url' => route('admin.messages'), 'icon' => 'messages'],
                //     ['label' => 'Properties', 'url' => route('admin.property'), 'icon' => 'property'],
                //     ['label' => 'Appointments', 'url' => route('admin.appointments'), 'icon' => 'appointment'],
                //     ['label' => 'Reviews', 'url' => route('admin.review'), 'icon' => 'review'],
                //     ['label' => 'Reports', 'url' => route('admin.reports'), 'icon' => 'report'],
                // ];
                $links = [
                    ['label' => 'Dashboard', 'url' => route('app.page', ['page' => 'dashboard']), 'icon' => 'dashboard'],
                    ['label' => 'Users', 'url' => route('app.page', ['page' => 'users']), 'icon' => 'users'],
                    ['label' => 'Messages', 'url' => route('app.page', ['page' => 'messages']), 'icon' => 'messages'],
                    ['label' => 'Properties', 'url' => route('app.page', ['page' => 'property']), 'icon' => 'property'],
                    ['label' => 'Appointments', 'url' => route('app.page', ['page' => 'appointments']), 'icon' => 'appointment'],
                    ['label' => 'Reviews', 'url' => route('app.page', ['page' => 'review']), 'icon' => 'review'],
                    ['label' => 'Reports', 'url' => route('app.page', ['page' => 'report']), 'icon' => 'report'],
                ];
            } elseif ($acting === 'broker') {
                // Legacy broker links using separate broker route names:
                // $links = [
                //     ['label' => 'Dashboard', 'url' => route('broker.dashboard'), 'icon' => 'dashboard'],
                //     ['label' => 'Agents', 'url' => route('broker.agents'), 'icon' => 'users'],
                //     ['label' => 'Messages', 'url' => route('broker.messages'), 'icon' => 'messages'],
                //     ['label' => 'My Listings', 'url' => route('broker.listings'), 'icon' => 'property'],
                //     ['label' => 'Appointments', 'url' => route('broker.appointments'), 'icon' => 'appointment'],
                //     ['label' => 'Reviews', 'url' => route('broker.reviews'), 'icon' => 'review'],
                //     ['label' => 'Reports', 'url' => route('broker.reports'), 'icon' => 'report'],
                //     ['label' => 'Settings', 'url' => route('broker.settings'), 'icon' => 'users'],
                // ];
                $links = [
                    ['label' => 'Dashboard', 'url' => route('app.page', ['page' => 'dashboard']), 'icon' => 'dashboard'],
                    ['label' => 'Agents', 'url' => route('app.page', ['page' => 'agents']), 'icon' => 'users'],
                    ['label' => 'Messages', 'url' => route('app.page', ['page' => 'messages']), 'icon' => 'messages'],
                    ['label' => 'My Listings', 'url' => route('app.page', ['page' => 'listings']), 'icon' => 'property'],
                    ['label' => 'Appointments', 'url' => route('app.page', ['page' => 'appointments']), 'icon' => 'appointment'],
                    ['label' => 'Reviews', 'url' => route('app.page', ['page' => 'review']), 'icon' => 'review'],
                    ['label' => 'Reports', 'url' => route('app.page', ['page' => 'report']), 'icon' => 'report'],
                    // ['label' => 'Settings', 'url' => route('app.page', ['page' => 'settings']), 'icon' => 'users'],
                ];
            } elseif ($acting === 'agent') {
                // Legacy agent links using separate agent route names:
                // $links = [
                //     ['label' => 'Dashboard', 'url' => route('agent.dashboard'), 'icon' => 'dashboard'],
                //     ['label' => 'Messages', 'url' => route('agent.messages'), 'icon' => 'messages'],
                //     ['label' => 'My Listings', 'url' => route('agent.listings'), 'icon' => 'property'],
                //     ['label' => 'Appointments', 'url' => route('agent.appointment'), 'icon' => 'appointment'],
                //     ['label' => 'Reviews', 'url' => route('agent.review'), 'icon' => 'review'],
                //     ['label' => 'Reports', 'url' => route('agent.reports'), 'icon' => 'report'],
                //     ['label' => 'Settings', 'url' => route('agent.settings'), 'icon' => 'users'],
                // ];
                $links = [
                    ['label' => 'Dashboard', 'url' => route('app.page', ['page' => 'dashboard']), 'icon' => 'dashboard'],
                    ['label' => 'Messages', 'url' => route('app.page', ['page' => 'messages']), 'icon' => 'messages'],
                    ['label' => 'My Listings', 'url' => route('app.page', ['page' => 'listings']), 'icon' => 'property'],
                    ['label' => 'Appointments', 'url' => route('app.page', ['page' => 'appointment']), 'icon' => 'appointment'],
                    ['label' => 'Reviews', 'url' => route('app.page', ['page' => 'review']), 'icon' => 'review'],
                    ['label' => 'Reports', 'url' => route('app.page', ['page' => 'report']), 'icon' => 'report'],
                    // ['label' => 'Settings', 'url' => route('app.page', ['page' => 'settings']), 'icon' => 'users'],
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
