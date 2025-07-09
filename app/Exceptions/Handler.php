<?php

namespace App\Exceptions;

use App\Traits\Reusables;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use Reusables;
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->renderable(function (NotFoundHttpException $e) {
            return response()->json(['message' => 'You are not authorized.'], status: 404);
        });

        $this->renderable(function (ThrottleRequestsException $e) {
            return response()->json([
                'message' => 'Too many requests. Please try again after 1 minute.',
                'status' => false
            ], 400);
        });

        $this->reportable(function (Throwable $e) {
            // Enhanced Discord logging for system errors
            $user = auth()->user();
            $request = request();
            
            \App\Jobs\DiscordBots::dispatch([
                'message' => '🚨 **System error**',
                'details' => [
                    'error_type' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                    'code' => $e->getCode(),
                    'user_id' => $user ? $user->id : 'Guest',
                    'user_email' => $user ? $user->email : 'N/A',
                    'route' => $request->route() ? $request->route()->getName() : 'Unknown',
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toDateTimeString(),
                    'trace' => collect($e->getTrace())->take(3)->map(function ($trace) {
                        return [
                            'file' => basename($trace['file'] ?? 'unknown'),
                            'line' => $trace['line'] ?? 'unknown',
                            'function' => $trace['function'] ?? 'unknown'
                        ];
                    })->toArray()
                ]
            ]);
            
            // Keep the original simple message for backup
            $message = sprintf(
                "Error: %s \nFile: %s \nLine: %s \nCode: %s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getCode()
            );

            $this->sendToDiscord($message);
        });
    }
}
