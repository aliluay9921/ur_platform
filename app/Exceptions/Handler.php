<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
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
        // return $this->reportable(function (Throwable $e) {
        // });

        $this->renderable(function (Throwable $exception, $request) {
            if ($exception instanceof Exception) {
                if ($exception->getMessage() == 'Route [login] not defined.') {
                    return response()->json(
                        [
                            'message' => $exception->getMessage(),
                        ],
                        402
                    );
                } elseif ($exception->getMessage() == "غير مصرح لك بالدخول") {
                    return response()->json(
                        [
                            'message' => $exception->getMessage(),
                        ],
                        403
                    );
                }
            }
            if ($exception instanceof AuthenticationException) {

                return response()->json(
                    [
                        'message' => $exception->getMessage(),
                    ],
                    402
                );
            }
        });
    }
}