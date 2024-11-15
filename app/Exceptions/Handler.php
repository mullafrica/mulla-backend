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
