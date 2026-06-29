<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LeadershipMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->role !== 'pimpinan') {
            abort(403, 'Akses hanya untuk pimpinan.');
        }

        if (!Auth::user()->tenant_id) {
            abort(403, 'Akun pimpinan belum terhubung dengan tenant.');
        }

        return $next($request);
    }
}
