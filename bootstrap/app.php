<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\SecurityFirewallMiddleware::class);

        $middleware->web(prepend: [
            \App\Http\Middleware\PreventSessionCaching::class,
        ], append: [
            HandleInertiaRequests::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/webhook/*',
            'webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Tangani Rate Limiting (Too Many Requests / 429) dengan pesan ramah & detail detik tersisa
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
            $msg = "Terlalu banyak permintaan. Mohon tunggu {$retryAfter} detik sebelum mencoba kembali.";

            if ($request->header('X-Inertia')) {
                return back()->withErrors([
                    'email'   => $msg,
                    'general' => $msg,
                    'message' => $msg,
                ])->with('error', $msg);
            }

            if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'status'      => 'error',
                    'message'     => $msg,
                    'retry_after' => (int) $retryAfter,
                    'errors'      => [
                        'general' => [$msg],
                        'email'   => [$msg],
                    ],
                ], 429, $e->getHeaders());
            }

            return back()->with('error', $msg);
        });

        // Tangani CSRF Token Mismatch (419 Page Expired) dengan pesan jelas
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                // Record configuration clues, never passwords, tokens or cookie values.
                \Illuminate\Support\Facades\Log::warning('Authentication CSRF mismatch', [
                    'path' => $request->path(),
                    'host' => $request->getHost(),
                    'secure_request' => $request->isSecure(),
                    'session_driver' => config('session.driver'),
                    'session_cookie_present' => $request->cookies->has(config('session.cookie')),
                    'session_domain' => config('session.domain'),
                    'session_secure' => config('session.secure'),
                    'csrf_body_present' => $request->filled('_token'),
                    'csrf_header_present' => $request->hasHeader('X-CSRF-TOKEN'),
                ]);
                $msg = 'Sesi keamanan Anda telah berakhir. Silakan muat ulang (refresh) halaman untuk melanjutkan.';

                if ($request->header('X-Inertia')) {
                    return back()->withErrors([
                        'general' => $msg,
                        'message' => $msg,
                    ])->with('error', $msg);
                }

                if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => $msg,
                        'errors'  => [
                            'general' => [$msg],
                        ],
                    ], 419);
                }

                return back()->with('error', $msg);
            }
        });
    })->create();
