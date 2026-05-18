<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * Daftar exception yang tidak perlu dilaporkan.
     *
     * Biar sederhana kita kosongkan saja.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * Laporkan / log exception.
     *
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render exception menjadi HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $e)
    {
        // Biarkan behaviour default Laravel yang menangani semua error.
        return parent::render($request, $e);
    }

    /**
     * Dipanggil ketika user tidak ter-autentikasi
     * (misal session timeout, cookie hilang, belum login, dll).
     *
     * Di sini kita paksa:
     *  - Kalau request JSON / AJAX → balas 401
     *  - Kalau request biasa → redirect ke /login
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Untuk API / AJAX
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Untuk request web biasa → selalu paksa ke halaman login
        // Bisa juga pakai route('login') kalau route-nya pakai nama "login"
        return redirect()->guest('/login');
    }
}
