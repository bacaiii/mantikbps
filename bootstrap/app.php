<?php

use App\Http\Middleware\AdminSystemMiddleware;
use App\Http\Middleware\TenantAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EmployeeMiddleware;
use App\Http\Middleware\LeadershipMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin.system' => AdminSystemMiddleware::class,
            'tenant.admin' => TenantAdminMiddleware::class,
            'employee' => EmployeeMiddleware::class,
            'leader' => LeadershipMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();