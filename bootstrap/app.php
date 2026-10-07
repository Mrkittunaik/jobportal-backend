<?php

use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\RoleMiddleware;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'   => RoleMiddleware::class,
            'active' => EnsureUserActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isApi = fn ($request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $isApi($request));

        $exceptions->render(function (ValidationException $e, $request) use ($isApi) {
            if ($isApi($request)) return ApiResponse::error('Validation failed', 422, $e->errors());
        });
        $exceptions->render(function (AuthenticationException $e, $request) use ($isApi) {
            if ($isApi($request)) return ApiResponse::error('Unauthenticated', 401);
        });
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, $request) use ($isApi) {
            if ($isApi($request)) return ApiResponse::error('You are not allowed to do this', 403);
        });
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, $request) use ($isApi) {
            if ($isApi($request)) return ApiResponse::error('Resource not found', 404);
        });
        $exceptions->render(function (TooManyRequestsHttpException $e, $request) use ($isApi) {
            if ($isApi($request)) return ApiResponse::error('Too many requests, slow down', 429);
        });
        $exceptions->render(function (HttpExceptionInterface $e, $request) use ($isApi) {
            if ($isApi($request)) return ApiResponse::error($e->getMessage() ?: 'Request failed', $e->getStatusCode());
        });
        $exceptions->render(function (\Throwable $e, $request) use ($isApi) {
            if ($isApi($request) && ! ($e instanceof HttpExceptionInterface)) {
                report($e);
                return ApiResponse::error(
                    config('app.debug') ? $e->getMessage() : 'Server error', 500
                );
            }
        });
    })->create();
