@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">Total Akun User Tenant</div>
                    <h2 class="fw-bold mb-0">{{ $totalTenantWorkUsers }}</h2>
                    <small class="text-muted">
                        Tim penyusun, pemeriksa konten, pemeriksa layout, dan pimpinan
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">Admin Provinsi</div>
                    <h2 class="fw-bold mb-0">{{ $totalProvinsi }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">Admin Kab/Kota</div>
                    <h2 class="fw-bold mb-0">{{ $totalKabKota }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">Akun Tenant Terbaru</h5>
                <small class="text-muted">Ringkasan akun admin tenant yang sudah dibuat</small>
            </div>
            <a href="{{ route('admin.system.tenant-accounts.index') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-arrow-right-circle me-1"></i> Kelola Akun
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>ID Login</th>
                            <th>Nama BPS</th>
                            <th>Wilayah</th>
                            <th>Role</th>
                            <th>Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestAccounts as $account)
                            <tr>
                                <td>{{ $account->login_id }}</td>
                                <td>{{ optional($account->tenant)->name }}</td>
                                <td>{{ optional($account->tenant)->wilayah }}</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $account->role === 'admin_provinsi' ? 'Admin Provinsi' : 'Admin Kabupaten/Kota' }}
                                    </span>
                                </td>
                                <td>{{ $account->created_at->format('d-m-Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada akun tenant.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection