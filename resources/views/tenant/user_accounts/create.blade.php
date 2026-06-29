@extends('layouts.tenant')

@section('title', 'Tambah Akun Pengguna')

@section('content')
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Form Tambah Akun Pengguna</h5>
            <small class="text-muted">Buat akun login untuk pegawai atau pimpinan</small>
        </div>

        <div class="card-body">
            @include('tenant.user_accounts._form', [
                'formAction' => route('tenant.user-accounts.store'),
                'formMethod' => 'POST',
                'submitLabel' => 'Simpan Akun',
                'userAccount' => null,
            ])
        </div>
    </div>
@endsection