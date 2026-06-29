@extends('layouts.tenant')

@section('title', 'Edit Knowledge')

@section('content')
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Edit Link Knowledge</h5>
            <small class="text-muted">Perbarui link materi edukasi untuk pegawai.</small>
        </div>
        <div class="card-body">
            @include('tenant.knowledge._form', [
                'formAction' => route('tenant.knowledge.update', $knowledgeLink->id),
                'formMethod' => 'PUT',
                'submitLabel' => 'Update Link',
                'knowledgeLink' => $knowledgeLink,
            ])
        </div>
    </div>
@endsection
