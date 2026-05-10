<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (!$request->expectsJson() && !$request->is('api/*')) {
                return null;
            }

            $requestId = $request->headers->get('X-Request-Id') ?: (string) str()->uuid();

            if ($e instanceof ValidationException) {
                $validationErrors = $e->errors();

                return response()->json([
                    'success' => false,
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'I dati inviati non sono validi.',
                    // Compatibilita con frontend che usa e.response.data.error
                    'error' => 'I dati inviati non sono validi.',
                    'request_id' => $requestId,
                    // Manteniamo il formato Laravel per i test assertJsonValidationErrors.
                    'errors' => $validationErrors,
                    'details' => $validationErrors,
                ], 422);
            }

            $status = $e instanceof AuthenticationException
                ? 401
                : ($e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500);

            $isServerError = $status >= 500;
            $code = $status === 401
                ? 'UNAUTHENTICATED'
                : ($isServerError ? 'INTERNAL_SERVER_ERROR' : 'HTTP_ERROR');
            $message = $isServerError
                ? 'Errore interno del server.'
                : ($status === 401 ? 'Unauthenticated.' : ($e->getMessage() ?: 'Richiesta non valida.'));

            if ($isServerError) {
                Log::error('Unhandled API exception', [
                    'request_id' => $requestId,
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'exception' => $e,
                ]);
            } else {
                Log::warning('Handled API HTTP exception', [
                    'request_id' => $requestId,
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'status' => $status,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => false,
                'code' => $code,
                'message' => $message,
                'error' => $message,
                'request_id' => $requestId,
            ], $status);
        });
    })->create();
