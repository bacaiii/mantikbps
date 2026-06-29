@extends('layouts.admin')

@section('title', 'Edit Akun BPS')

@section('content')
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Form Edit Akun Tenant</h5>
        </div>
        <div class="card-body">
            @include('admin.tenant_accounts._form', [
                'formAction' => route('admin.system.tenant-accounts.update', $user->id),
                'formMethod' => 'PUT',
                'submitLabel' => 'Update Akun',
                'account' => $user,
            ])
        </div>
    </div>
@endsection