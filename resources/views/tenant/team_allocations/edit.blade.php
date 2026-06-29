@extends('layouts.tenant')

@section('title', 'Edit Tim Kerja')

@section('content')
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Form Edit Tim Kerja</h5>
            <small class="text-muted">Ubah nama tim dan publikasi yang terhubung.</small>
        </div>
        <div class="card-body">
            @include('tenant.team_allocations._form', [
                'formAction' => route('tenant.team-allocations.update', $team->id),
                'formMethod' => 'PUT',
                'submitLabel' => 'Update Tim Kerja',
                'team' => $team,
                'publications' => $publications,
            ])
        </div>
    </div>
@endsection
