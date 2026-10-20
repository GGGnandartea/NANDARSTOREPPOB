<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityFirewallMiddleware
{
    /**
     * Pola serangan yang diblokir seketika oleh Firewall Aplikasi (WAF Internal).
     */
    protected array $maliciousPatterns = [
        // 1. SQL Injection Signatures
        '/(\bUNION\s+(ALL\s+)?SELECT\b)/i',
        '/(\bINTO\s+(OUTFILE|DUMPFILE)\b)/i',
        '/(\bINFORMATION_SCHEMA\b)/i',
        '/(\b(WAITFOR\s+DELAY|BENCHMARK|SLEEP)\s*\()/i',
        '/(;\s*(DROP|ALTER|TRUNCATE|DELETE)\s+(TABLE|FROM)\b)/i',
        '/(\'\s*OR\s*[\'"]?1[\'"]?\s*=\s*[\'"]?1[\'"]?)/i',
        '/(\'\s*OR\s*true\b)/i',
        '/(\bGROUP_CONCAT\s*\()/i',
        '/(\bEXTRACTVALUE\s*\()/i',
        '/(\bUPDATEXML\s*\()/i',

        // 2. Directory / Path Traversal & File Inclusion
        '/(\.\.\/|\.\.\\\\|\.\.%2f|\.\.%5c)/i',
        '/(%2e%2e%2f|%2e%2e\/)/i',
        '/(\/etc\/(passwd|shadow|hosts))/i',
        '/(\/proc\/self\/environ)/i',

        // 3. Cross-Site Scripting (XSS) & Dangerous Script Injection
        '/(<script\b[^>]*>)/i',
        '/(javascript:\s*[a-zA-Z0-9_\.])/i',
        '/(data:text\/html)/i',
        '/(\bonerror\s*=\s*)/i',
        '/(\bonload\s*=\s*)/i',
        '/(\bonmouseover\s*=\s*)/i',
        '/(<iframe\b[^>]*>)/i',
        '/(<svg\b[^>]*\bonload)/i',

        // 4. Remote Code Execution (RCE) / PHP Backdoor Payloads
        '/(<\?php|<\?=)/i',
        '/(\b(passthru|shell_exec|eval|base64_decode|system|assert)\s*\()/i',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Periksa Null-Byte Injection
        $rawUri = $request->getRequestUri();
        if (str_contains($rawUri, '%00') || str_contains($rawUri, "\0")) {
            $this->logAndBlock($request, 'Null-Byte Injection');
        }

        // 2. Periksa Pola Berbahaya di URI & Query String
        $queryString = urldecode($request->getQueryString() ?? '');
        $fullUrlDecoded = urldecode($rawUri);

        foreach ($this->maliciousPatterns as $pattern) {
            if (preg_match($pattern, $queryString) || preg_match($pattern, $fullUrlDecoded)) {
                $this->logAndBlock($request, 'Malicious URL / Query: ' . $pattern);
            }
        }

        // 3. Periksa Input Form / Body (Kecuali route upload file yang diverifikasi terpisah)
        $inputs = $request->except(['_token', 'password', 'password_confirmation', 'current_password']);
        $this->inspectInputsRecursive($request, $inputs);

        // Lanjutkan request
        $response = $next($request);

        // 4. Injeksi HTTP Security Headers (Proteksi WAF Browser)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        if (!$response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Sembunyikan signature PHP & Server jika ada
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }
        $response->headers->remove('X-Powered-By');

        return $response;
    }

    /**
     * Rekursif memeriksa setiap input teks dari user.
     */
    protected function inspectInputsRecursive(Request $request, array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->inspectInputsRecursive($request, $value);
            } elseif (is_string($value)) {
                // Periksa apakah ada upaya bypass skrip jahat
                foreach ($this->maliciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $this->logAndBlock($request, "Malicious Input in [{$key}]: " . $pattern);
                    }
                }
            }
        }
    }

    /**
     * Catat log insiden keamanan dan hentikan request seketika (HTTP 403 Forbidden).
     */
    protected function logAndBlock(Request $request, string $reason): void
    {
        Log::warning('[SECURITY FIREWALL BLOCKED]', [
            'ip'         => $request->ip(),
            'method'     => $request->method(),
            'url'        => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'reason'     => $reason,
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            abort(response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak: Permintaan tidak valid terdeteksi oleh sistem keamanan.',
            ], 403));
        }

        abort(403, 'Akses Ditolak: Permintaan terdeteksi berbahaya oleh Firewall Sistem.');
    }
}

