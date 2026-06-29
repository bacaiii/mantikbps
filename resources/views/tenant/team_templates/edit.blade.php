@extends('layouts.tenant')

@section('title', 'Assign Tim Kerja')

@section('content')
    @include('tenant.team_templates._form', [
        'formTitle' => 'Assign Tim Kerja',
        'formSubtitle' => 'Ubah nama tim, anggota tetap, dan tugas default dalam template tim.',
        'action' => route('tenant.team-templates.update', $template->id),
        'method' => 'PUT',
        'submitLabel' => 'Simpan Tim Kerja',
    ])
@endsection
