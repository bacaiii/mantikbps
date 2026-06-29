@extends('layouts.admin')

@section('title', 'Tambah Akun BPS')

@section('content')
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Form Tambah Akun Tenant</h5>
        </div>
        <div class="card-body">
            @include('admin.tenant_accounts._form', [
                'formAction' => route('admin.system.tenant-accounts.store'),
                'formMethod' => 'POST',
                'submitLabel' => 'Simpan Akun',
                'account' => null,
            ])
        </div>
    </div>
@endsection