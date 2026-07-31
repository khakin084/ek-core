<?php

use App\Http\Middleware\AuditBffMiddleware;
use App\Http\Middleware\AuthenticateSession;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo("/login");
        $middleware->alias([
            'ek-web' => AuthenticateSession::class,  // ek-core web
            'ek-api' => AuthenticateJwt::class,      // API / services
            'perm' => CheckPermission::class,
            'audit.bff' => AuditBffMiddleware::class    //Audit trail
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
