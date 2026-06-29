@extends('layouts.tenant')

@section('title', 'Kelola Akun Pengguna')

@section('content')
    <div class="card table-card">
        <div class="card-header bg-white border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Data Akun Pengguna Tenant</h5>
                    <small class="text-muted">
                        Kelola akun login untuk pegawai dan pimpinan.
                    </small>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('tenant.user-accounts.export') }}" class="btn btn-success">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Rekap CSV
                    </a>

                    <a href="{{ route('tenant.user-accounts.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Pengguna
                    </a>
                </div>
            </div>

            <form method="GET" class="row g-2 mt-3">
                <div class="col-md-4">
                    <select name="role" class="form-select">
                        <option value="">Semua Jenis Pengguna</option>
                        <option value="pegawai" {{ request('role') === 'pegawai' ? 'selected' : '' }}>Pegawai</option>
                        <option value="pimpinan" {{ request('role') === 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        value="{{ request('q') }}"
                        placeholder="Cari nama, email, atau ID login..."
                    >
                </div>

                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="alert alert-info mb-3"
                 style="background: rgba(13, 202, 240, 0.18); border: 1px solid rgba(13, 202, 240, 0.45); border-radius: 16px;">
                <strong>Keterangan fitur:</strong>

                <div class="mt-2">
                    <i class="bi bi-plus-circle text-primary me-1"></i>
                    <strong>Tambah Pengguna</strong> digunakan untuk menambahkan akun pegawai atau pimpinan.
                </div>

                <div class="mt-1">
                    <i class="bi bi-file-earmark-spreadsheet text-success me-1"></i>
                    <strong>Rekap CSV</strong> digunakan untuk mengunduh data akun pengguna dalam bentuk file CSV.
                </div>

                <div class="mt-1">
                    <i class="bi bi-search text-primary me-1"></i>
                    <strong>Filter</strong> digunakan untuk mencari atau menyaring akun berdasarkan jenis pengguna, nama, email, atau ID login.
                </div>

                <div class="mt-1">
                    <i class="bi bi-eye text-secondary me-1"></i>
                    <strong>Lihat Password</strong> digunakan untuk menampilkan atau menyembunyikan password akun.
                </div>

                <div class="mt-1">
                    <i class="bi bi-pencil-square text-warning me-1"></i>
                    <strong>Edit</strong> digunakan untuk mengubah data akun pengguna.
                </div>

                <div class="mt-1">
                    <i class="bi bi-trash text-danger me-1"></i>
                    <strong>Delete</strong> digunakan untuk menghapus akun pengguna dari sistem.
                </div>
            </div>

            <div class="table-fit-wrapper">
                <table class="table table-clean user-account-table mb-0">
                    <thead>
                        <tr>
                            <th class="col-no">No</th>
                            <th class="col-login">ID Login</th>
                            <th class="col-name">Nama</th>
                            <th class="col-email">Email</th>
                            <th class="col-phone">No HP</th>
                            <th class="col-password">Password</th>
                            <th class="col-action">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($users as $index => $user)
                            <tr>
                                <td class="col-no">{{ $users->firstItem() + $index }}</td>

                                <td class="col-login">{{ $user->login_id }}</td>

                                <td class="col-name">{{ $user->name }}</td>

                                <td class="col-email">{{ $user->email }}</td>

                                <td class="col-phone">{{ $user->phone ?? '-' }}</td>

                                <td class="col-password">
                                    <div class="input-group input-group-sm">
                                        <input
                                            type="password"
                                            id="pwd-user-{{ $user->id }}"
                                            class="form-control password-input"
                                            value="{{ $user->password_preview }}"
                                            readonly
                                        >

                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePassword('pwd-user-{{ $user->id }}', this)"
                                            title="Lihat Password"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </td>

                                <td class="col-action">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('tenant.user-accounts.edit', $user->id) }}"
                                           class="btn btn-warning btn-sm table-action-btn"
                                           title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form action="{{ route('tenant.user-accounts.destroy', $user->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus akun pengguna ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button class="btn btn-danger btn-sm table-action-btn" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Belum ada akun pengguna.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-end">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection