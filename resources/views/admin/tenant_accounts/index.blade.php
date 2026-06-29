@extends('layouts.admin')

@section('title', 'Kelola Akun BPS')

@section('content')
    <div class="card table-card">
        <div class="card-header bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-0 fw-bold">Data Akun Tenant BPS</h5>
                <small class="text-muted">Admin sistem hanya mengelola akun tenant, bukan tim kerja di dalam tenant</small>
            </div>

            <div class="d-flex gap-2">
                    <a href="{{ route('admin.system.tenant-accounts.export') }}" class="btn btn-success">
                        <i class="bi bi-download me-1"></i> Download Rekap CSV
                    </a>
                <a href="{{ route('admin.system.tenant-accounts.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Akun
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>ID Login</th>
                            <th>Email</th>
                            <th>Nama BPS</th>
                            <th>Wilayah</th>
                            <th>Kode Wilayah</th>
                            <th>No HP</th>
                            <th>Password</th>
                            <th>Role</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $index => $account)
                            <tr>
                                <td>{{ $accounts->firstItem() + $index }}</td>
                                <td>{{ $account->login_id }}</td>
                                <td>{{ $account->email }}</td>
                                <td>{{ optional($account->tenant)->name }}</td>
                                <td>{{ optional($account->tenant)->wilayah }}</td>
                                <td>{{ optional($account->tenant)->code ?? '-' }}</td>
                                <td>{{ $account->phone }}</td>
                                <td style="min-width: 220px;">
                                    <div class="input-group input-group-sm">
                                        <input
                                            type="password"
                                            id="pwd-{{ $account->id }}"
                                            class="form-control"
                                            value="{{ $account->password_preview }}"
                                            readonly
                                        >
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('pwd-{{ $account->id }}', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info">
                                        {{ $account->role === 'admin_provinsi' ? 'Admin Provinsi' : 'Admin Kabupaten/Kota' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.system.tenant-accounts.edit', $account->id) }}"
                                           class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form action="{{ route('admin.system.tenant-accounts.destroy', $account->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus akun tenant ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Belum ada data akun tenant.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $accounts->links() }}
            </div>
        </div>
    </div>
@endsection