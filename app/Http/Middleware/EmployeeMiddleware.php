<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->role !== 'pegawai') {
            abort(403, 'Akses hanya untuk pegawai.');
        }

        if (!Auth::user()->tenant_id) {
            abort(403, 'Akun belum terhubung dengan tenant.');
        }

        return $next($request);
    }
}