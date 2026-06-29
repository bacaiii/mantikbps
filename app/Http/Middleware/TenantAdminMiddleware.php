<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!in_array(Auth::user()->role, ['admin_provinsi', 'admin_kabkota'])) {
            abort(403, 'Akses hanya untuk Admin Provinsi atau Admin Kabupaten/Kota.');
        }

        if (!Auth::user()->tenant_id) {
            abort(403, 'Akun ini belum terhubung dengan tenant.');
        }

        return $next($request);
    }
}