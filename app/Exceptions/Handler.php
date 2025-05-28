<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
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
        $this->reportable(function (Throwable $e) {
            // Log error dengan konteks yang lengkap
            Log::error('[' . get_class($e) . '] ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id() ?? 'guest',
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Untuk API request, mengembalikan respons JSON
        if ($request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        // Menangani error secara kustom berdasarkan tipe exception
        if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
            return response()->view('errors.404', [
                'user' => $request->user(),
                'exception' => $e,
            ], 404);
        }

        if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
            return response()->view('errors.403', [
                'user' => $request->user(),
                'exception' => $e,
            ], 403);
        }
        
        if ($e instanceof TooManyRequestsHttpException) {
            return response()->view('errors.429', [
                'user' => $request->user(),
                'exception' => $e,
                'retryAfter' => $e->getHeaders()['Retry-After'] ?? null,
            ], 429);
        }

        if ($e instanceof TokenMismatchException) {
            // Redirect ke halaman sebelumnya dengan pesan error
            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('error', 'Halaman telah kadaluarsa karena tidak ada aktivitas. Silakan coba lagi.');
            
            // Atau tampilkan halaman error kustom
            return response()->view('errors.419', [
                'user' => $request->user(),
                'exception' => $e,
            ], 419);
        }

        if ($e instanceof HttpException && $e->getStatusCode() == 500) {
            return response()->view('errors.500', [
                'user' => $request->user(),
                'exception' => config('app.debug') ? $e : null,
            ], 500);
        }

        if ($e instanceof HttpException && $e->getStatusCode() == 503) {
            return response()->view('errors.503', [
                'user' => $request->user(),
                'exception' => $e,
                'retryAfter' => $e->getHeaders()['Retry-After'] ?? null,
            ], 503);
        }

        // Untuk exception TokenMismatch (419 - Page Expired)
        if ($e instanceof TokenMismatchException) {
            return response()->view('errors.419', [
                'user' => $request->user(),
                'exception' => $e,
            ], 419);
        }

        if ($e instanceof QueryException) {
            // Log error dengan detail koneksi (mask informasi sensitif)
            Log::error('Database Connection Error: ' . $this->maskSensitiveInfo($e->getMessage()), [
                'error_code' => $e->getCode(),
                'connection' => config('database.default'),
                'host' => config('database.connections.' . config('database.default') . '.host'),
            ]);
            
            // Menampilkan halaman error 500 untuk pengguna
            return response()->view('errors.500', [
                'user' => $request->user(),
                'exception' => config('app.debug') ? $e : null,
                'message' => 'Database connection error. Our team has been notified.'
            ], 500);
        }

        // Jika kita tidak menangani exception secara eksplisit,
        // gunakan handling default dari Laravel
        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions and return JSON responses.
     */
    private function handleApiException($request, Throwable $e)
    {
        // Mendapatkan kode status HTTP berdasarkan jenis exception
        $statusCode = $this->getStatusCode($e);

        // Menyiapkan respons JSON
        $response = [
            'success' => false,
            'status' => $statusCode,
            'message' => $this->getErrorMessage($e, $statusCode),
        ];

        // Tambahkan detail validasi jika itu ValidationException
        if ($e instanceof ValidationException) {
            $response['errors'] = $e->errors();
        }

        // Tambahkan detail error jika dalam mode debug
        if (config('app.debug')) {
            $response['exception'] = get_class($e);
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
            $response['trace'] = $e->getTrace();
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Get HTTP status code from exception.
     */
    private function getStatusCode(Throwable $e): int
    {
        // Jika exception memiliki method getStatusCode(), gunakan itu
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        // Tentukan kode status berdasarkan jenis exception
        return match (true) {
            $e instanceof AuthorizationException => 403,
            $e instanceof ModelNotFoundException => 404,
            $e instanceof ValidationException => 422,
            $e instanceof TokenMismatchException => 419,
            $e instanceof TooManyRequestsHttpException => 429,
            default => 500,
        };
    }

    /**
     * Get user-friendly error message based on status code.
     */
    private function getErrorMessage(Throwable $e, int $statusCode): string
    {
        // Gunakan pesan error bawaan jika ada dan bukan di mode debug
        if (!config('app.debug') && method_exists($e, 'getMessage') && !empty($e->getMessage())) {
            return $e->getMessage();
        }

        // Pesan default untuk kode status umum
        return match ($statusCode) {
            400 => 'Bad Request. Permintaan tidak valid.',
            401 => 'Unauthorized. Silahkan login untuk melanjutkan.',
            403 => 'Forbidden. Anda tidak memiliki izin untuk mengakses sumber daya ini.',
            404 => 'Not Found. Sumber daya yang Anda cari tidak ditemukan.',
            419 => 'Page Expired. Silakan segarkan halaman dan coba lagi.',
            422 => 'Validation Error. Data yang diberikan tidak valid.',
            429 => 'Too Many Requests. Anda telah membuat terlalu banyak permintaan. Silakan coba lagi nanti.',
            500 => 'Internal Server Error. Terjadi kesalahan pada server kami.',
            503 => 'Service Unavailable. Layanan sedang dalam pemeliharaan. Silakan coba lagi nanti.',
            default => 'Terjadi kesalahan yang tidak diharapkan.',
        };
    }

    /**
     * Log security-related events.
     */
    private function logSecurityEvent(Throwable $e, $request)
    {
        // Konteks keamanan untuk logging
        $context = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id' => $request->user() ? $request->user()->id : 'guest',
        ];

        // Log berdasarkan jenis exception
        if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
            Log::channel('security')->warning('Forbidden access attempt', $context);
        } elseif ($e instanceof TooManyRequestsHttpException) {
            Log::channel('security')->warning('Rate limit exceeded', $context);
        }

        // Deteksi potensi scanning
        if ($e instanceof NotFoundHttpException) {
            $path = $request->path();
            $suspiciousPaths = ['/wp-admin', '/admin', '/.env', '/phpmyadmin', '/wp-login', '/admin/login'];
            
            foreach ($suspiciousPaths as $suspiciousPath) {
                if (str_contains($path, $suspiciousPath)) {
                    Log::channel('security')->warning('Suspicious path access detected', array_merge($context, [
                        'path' => $path,
                        'suspicious_pattern' => $suspiciousPath,
                    ]));
                    break;
                }
            }
        }
    }

    // Tambahkan method ini di class Handler untuk memaskir informasi sensitif
    private function maskSensitiveInfo($message) {
        // Mask password dari koneksi string jika ada
        $message = preg_replace('/password=([^;]*)/', 'password=********', $message);
        // Mask SQL query params jika perlu
        return $message;
    }
}