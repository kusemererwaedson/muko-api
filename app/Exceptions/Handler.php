<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            if ($exception instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'message' => 'Method not allowed',
                    'error' => $exception->getMessage()
                ], 405);
            }

            if ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'message' => 'Route not found'
                ], 404);
            }

            if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            if ($exception instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $exception->errors()
                ], 422);
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'Server error',
                'error' => get_class($exception)
            ], 500);
        }

        return parent::render($request, $exception);
    }
}
