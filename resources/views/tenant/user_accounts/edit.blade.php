@extends('layouts.tenant')

@section('title', 'Edit Akun Pengguna')

@section('content')
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Form Edit Akun Pengguna</h5>
            <small class="text-muted">Perbarui nama, email, jenis pengguna, dan password pengguna</small>
        </div>

        <div class="card-body">
            @include('tenant.user_accounts._form', [
                'formAction' => route('tenant.user-accounts.update', $user->id),
                'formMethod' => 'PUT',
                'submitLabel' => 'Update Akun',
                'userAccount' => $user,
            ])
        </div>
    </div>
@endsection