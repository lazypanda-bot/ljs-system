<?php

// use Illuminate\Foundation\Application;
// use Illuminate\Foundation\Configuration\Exceptions;
// use Illuminate\Foundation\Configuration\Middleware;

// return Application::configure(basePath: dirname(__DIR__))
//     ->withRouting(
//         web: __DIR__.'/../routes/web.php',
//         commands: __DIR__.'/../routes/console.php',
//         health: '/up',
//     )
//     ->withMiddleware(function (Middleware $middleware): void {
//         //
//     })
//     ->withExceptions(function (Exceptions $exceptions): void {
//         //
//     })->create();


use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Previous registration: $middleware->web(append: [\App\Http\Middleware\PreventAuthenticatedPageCaching::class]);
        $middleware->web(append: [\App\Http\Middleware\PreventAuthenticatedPageCaching::class, \App\Http\Middleware\PreservePageContext::class]);
        $middleware->redirectGuestsTo(function () {
            session()->flash('auth_modal_error', true);

            return route('home');
        });

        $middleware->validateCsrfTokens(except: [
            'temp-login',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            return redirect()->route('home')
                ->with('error', 'Please refresh the page and try again.');
        });
    })->create();
