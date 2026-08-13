<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            App\Http\Middleware\InitializeTenantConnection::class,
        ]);

        $middleware->web(append: [
            App\Http\Middleware\EnsureActiveUser::class,
        ]);

        $middleware->prependToPriorityList(
            Illuminate\Cookie\Middleware\EncryptCookies::class,
            App\Http\Middleware\InitializeTenantConnection::class,
        );

        $middleware->prependToPriorityList(
            Illuminate\Routing\Middleware\SubstituteBindings::class,
            App\Http\Middleware\IdentifyTenant::class,
        );

        $middleware->validateCsrfTokens(except: [
            'api/public/request-otp',
            'api/public/verify-otp',
            'api/public/activity',
            'api/public/event/*/register',
            'api/chatbot/message',
            'iclock/*',
            'api/iclock/*',
            'biometric/*',
            'api/biometric/*',
        ]);

        $middleware->alias([
            'permission' => App\Http\Middleware\CheckPermission::class,
            'pp.token' => App\Http\Middleware\ResolvePpToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, \Illuminate\Http\Request $request) {
            $msg = 'The uploaded file exceeds the maximum allowed server upload limit (8MB). Please choose a smaller file.';
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $msg,
                    'errors' => [
                        'file' => [$msg],
                    ],
                ], 413);
            }

            return response()->redirectTo(url()->previous())->withErrors(['file' => $msg]);
        });
    })->create();
