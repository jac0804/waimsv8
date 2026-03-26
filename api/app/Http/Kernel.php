<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'checkHeader' => \App\Http\Middleware\checkHeader::class,
        'checkTimeinHeader' => \App\Http\Middleware\checkTimeinHeader::class,
        'checkPOSAppHeader' => \App\Http\Middleware\checkPOSAppHeader::class,
        'checkFinedineAppHeader' => \App\Http\Middleware\checkFinedineAppHeader::class,
        'checkPayrollHeader' => \App\Http\Middleware\checkPayrollHeader::class,
        'checkSbcRegHeader' => \App\Http\Middleware\checkSbcRegHeader::class,
        'checkSbcATIHeader' => \App\Http\Middleware\checkSbcATIHeader::class,
        'checkSBCMobilev2Header' => \App\Http\Middleware\checkSBCMobilev2Header::class,
        'checkRoxasHeader' => \App\Http\Middleware\checkRoxasHeader::class,
    ];
}
