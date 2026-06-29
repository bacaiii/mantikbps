@extends('layouts.tenant')

@section('title', 'Tambah Publikasi')

@section('content')
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Form Tambah Publikasi</h5>
            <small class="text-muted">Input data Publikasi yang diperlukan.</small>
        </div>

        <div class="card-body">
            @include('tenant.publications._form', [
                'formAction' => route('tenant.publications.store'),
                'formMethod' => 'POST',
                'submitLabel' => 'Simpan Publikasi',
                'publication' => $publication,
            ])
        </div>
    </div>
@endsection