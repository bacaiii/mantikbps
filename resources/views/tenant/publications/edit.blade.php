@extends('layouts.tenant')

@section('title', 'Edit Publikasi')

@section('content')
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Form Edit Publikasi</h5>
            <small class="text-muted">Perbarui judul, kategori, periode, dan jadwal publikasi</small>
        </div>

        <div class="card-body">
            @include('tenant.publications._form', [
                'formAction' => route('tenant.publications.update', $publication->id),
                'formMethod' => 'PUT',
                'submitLabel' => 'Update Publikasi',
                'publication' => $publication,
            ])
        </div>
    </div>
@endsection