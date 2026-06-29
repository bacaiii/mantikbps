@extends('layouts.tenant')

@section('title', 'Tambah Rincian Pemeriksaan')

@section('content')
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Tambah Rincian Pemeriksaan</h5>
            <small class="text-muted">Tambahkan rincian pada card pedoman yang dipilih.</small>
        </div>
        <div class="card-body">
            @include('tenant.inspection_guidelines._form', [
                'formAction' => route('tenant.inspection-guidelines.store'),
                'formMethod' => 'POST',
                'submitLabel' => 'Simpan Rincian',
                'guideline' => $guideline,
            ])
        </div>
    </div>
@endsection
