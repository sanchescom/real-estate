<?php

declare(strict_types=1);

use App\Shared\App\ApiResponse;
use App\Shared\App\Middleware\ETagMiddleware;
use App\Shared\App\Middleware\InnerApiKeyMiddleware;
use App\Shared\App\Middleware\LogPeakMemoryMiddleware;
use App\Shared\App\Middleware\RequestIdMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'inner-api' => InnerApiKeyMiddleware::class,
        ]);
        $middleware->append(RequestIdMiddleware::class);
        $middleware->append(LogPeakMemoryMiddleware::class);
        $middleware->append(ETagMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (ValidationException $e): JsonResponse {
            /** @var ApiResponse $api */
            $api = app(ApiResponse::class);

            return $api->validationErrors($e->errors());
        });

        $exceptions->renderable(function (ModelNotFoundException $e): JsonResponse {
            /** @var ApiResponse $api */
            $api = app(ApiResponse::class);

            return $api->error('Not Found', 404, $e->getModel().' not found.');
        });

        $exceptions->renderable(function (NotFoundHttpException $e): JsonResponse {
            /** @var ApiResponse $api */
            $api = app(ApiResponse::class);

            return $api->error(
                'Not Found',
                404,
                $e->getMessage() ?: 'The requested resource was not found.',
            );
        });

        $exceptions->renderable(function (AuthenticationException $e): JsonResponse {
            /** @var ApiResponse $api */
            $api = app(ApiResponse::class);

            return $api->error('Unauthenticated', 401, $e->getMessage());
        });
    })->create();
