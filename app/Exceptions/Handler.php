<?php

namespace App\Exceptions;

use Throwable;
use PDOException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        ApiGenericException::class,
        UserNotFound::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Handle validation errors separately
        if ($exception instanceof ValidationException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'status'  => 422,
                    'errors'  => $exception->errors(),
                ], 422);
            }

            // For web requests, redirect back with input & errors
            return redirect()->back()
                ->withErrors($exception->errors())
                ->withInput($request->all());
        }

        // Map exceptions to HTTP status codes and custom messages
        $exceptionMap = [
            ModelNotFoundException::class => ['status' => 404, 'message' => 'Resource not found.'],
            QueryException::class         => ['status' => 500, 'message' => 'Database query error.'],
            PDOException::class           => ['status' => 500, 'message' => 'Database connection error.'],
            ApiGenericException::class    => ['status' => 500, 'message' => 'API error occurred.'],
            \RuntimeException::class      => ['status' => 500, 'message' => null],
            \ErrorException::class        => ['status' => 500, 'message' => null],
        ];

        $exceptionClass = get_class($exception);
        $statusCode     = 500;
        $customMessage  = $exception->getMessage();

        // Override with mapped message/status if exists
        foreach ($exceptionMap as $class => $data) {
            if ($exception instanceof $class) {
                $statusCode = $data['status'];
                $customMessage = $data['message'] ?? $exception->getMessage();
                break;
            }
        }

        // Handle database errors: hide detailed message in production
        if ($exception instanceof QueryException || $exception instanceof PDOException) {
            if (!app()->environment('local')) {
                $customMessage = 'Something went wrong. Please try again later.';
            }
        }

        // Return JSON response if requested
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $customMessage,
                'status'  => $statusCode,
                'errors'  => ['message' => $exception->getMessage()],
            ], $statusCode);
        }

        // Render custom error view
        $viewName = 'errors.' . $statusCode; // e.g., errors.404, p360::errors.500

        // Fallback to p360::errors.500 if the view doesn't exist
        if (!view()->exists($viewName)) {
            $viewName = 'p360::errors.500';
        }

        return response()->view($viewName, [
            'message' => $customMessage,
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'exception' => $exception,
        ], $statusCode);
    }
}
