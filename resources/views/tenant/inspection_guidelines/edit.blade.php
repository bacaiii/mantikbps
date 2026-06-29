@extends('layouts.tenant')

@section('title', 'Edit Rincian Pemeriksaan')

@section('content')
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Edit Rincian Pemeriksaan</h5>
            <small class="text-muted">Perbarui isi rincian pemeriksaan tanpa mengubah konteks card pedoman.</small>
        </div>
        <div class="card-body">
            @include('tenant.inspection_guidelines._form', [
                'formAction' => route('tenant.inspection-guidelines.update', $guideline->id),
                'formMethod' => 'PUT',
                'submitLabel' => 'Update Rincian',
                'guideline' => $guideline,
            ])
        </div>
    </div>
@endsection
