<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\checkFinedineAppHeader;
use App\Http\Middleware\checkHeader;
use App\Http\Middleware\checkPayrollHeader;
use App\Http\Middleware\checkPOSAppHeader;
use App\Http\Middleware\checkRoxasHeader;
use App\Http\Middleware\checkSbcATIHeader;
use App\Http\Middleware\checkSBCMobilev2Header;
use App\Http\Middleware\checkSbcRegHeader;
use App\Http\Middleware\checkTimeinHeader;

class_alias(
    \App\Overrides\ConvertEmptyStringsToNull::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class
);

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            '*'
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
