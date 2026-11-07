<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PreservePageContext
{
    private const PATHS = [
        'users' => ['/app/users', '/admin/users', '/admin/teams'],
        'properties' => ['/app/property', '/app/listings'],
        'appointments' => ['/app/appointments', '/app/appointment'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Keep existing controller redirects as fallbacks when there is no list context.
        if (! $request->isMethod('GET') && $response instanceof RedirectResponse
            && in_array('success', $request->session()->get('_flash.new', []), true)) {
            $group = match (true) {
                $request->routeIs('admin.users.*', 'admin.teams.*') => 'users',
                $request->routeIs('admin.properties.*') => 'properties',
                $request->routeIs('appointments.status', 'appointments.proposals.*', 'appointments.block-date') => 'appointments',
                default => null,
            };
            $returnTo = $request->input('_return_to');
            if ($group && is_string($returnTo) && $this->isAllowed($returnTo, $group)) {
                $response->setTargetUrl($returnTo);
            }
        }

        // Deleting or filtering the last item on a page must not leave an empty table.
        if ($request->isMethod('GET') && $request->filled('page')
            && in_array('/'.$request->path(), array_merge(...array_values(self::PATHS)), true)
            && method_exists($response, 'getOriginalContent')) {
            $view = $response->getOriginalContent();
            if ($view instanceof View) {
                foreach (['users', 'appointments', 'properties'] as $key) {
                    $paginator = $view->getData()[$key] ?? null;
                    if ($paginator instanceof LengthAwarePaginator && $paginator->currentPage() > $paginator->lastPage()) {
                        $query = array_merge($request->query(), ['page' => $paginator->lastPage()]);

                        return redirect('/'.$request->path().'?'.http_build_query($query));
                    }
                }
            }
        }

        return $response;
    }

    private function isAllowed(string $url, string $group): bool
    {
        if (! str_starts_with($url, '/') || str_starts_with($url, '//') || preg_match('/[\\\\\r\n]/', $url)) {
            return false;
        }
        $parts = parse_url($url);

        return is_array($parts) && ! isset($parts['host']) && ! isset($parts['scheme'])
            && in_array($parts['path'] ?? '', self::PATHS[$group], true);
    }
}
