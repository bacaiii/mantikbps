<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalTenantWorkUsers = User::whereNotNull('tenant_id')
            ->whereIn('role', [
                'pegawai',
                'pimpinan',
            ])
            ->count();

        $totalProvinsi = User::where('role', 'admin_provinsi')->count();
        $totalKabKota = User::where('role', 'admin_kabkota')->count();

        $latestAccounts = User::with('tenant')
            ->whereIn('role', ['admin_provinsi', 'admin_kabkota'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalTenantWorkUsers',
            'totalProvinsi',
            'totalKabKota',
            'latestAccounts'
        ));
    }
}