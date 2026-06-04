<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Src\Shared\Domain\Exception\DomainException;
use Src\Shared\Infrastructure\Http\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e) {
            return ApiResponse::error(
                'validation_error',
                'Os dados fornecidos são inválidos.',
                422,
                $e->errors(),
            );
        });

        $exceptions->render(function (DomainException $e) {
            return ApiResponse::error(
                $e->errorCode(),
                $e->getMessage(),
                $e->httpStatus(),
            );
        });
    })->create();
