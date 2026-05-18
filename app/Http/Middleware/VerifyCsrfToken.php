<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;
use Closure;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends BaseVerifier
{
    protected $except = [
        // Tambahkan route yang tidak perlu CSRF verification di sini
        // Contoh: 'api/*'
    ];

    public function handle($request, Closure $next)
    {
        // Jika request adalah GET, langsung lewatkan
        if ($this->isReading($request)) {
            return $next($request);
        }

        // Cek apakah session aktif
        if (!$request->session()->isStarted()) {
            $request->session()->start();
        }

        // Cek apakah token valid
        if ($this->tokensMatch($request)) {
            return $next($request);
        }

        // Regenerate session dan token jika tidak valid
        $request->session()->regenerateToken();
        
        // Redirect ke halaman yang sama dengan pesan error
        return redirect($request->fullUrl())->with([
            'error' => 'Sesi telah kadaluarsa. Silakan refresh halaman dan coba lagi.',
            'old_input' => $request->except('_token')
        ]);
    }
}