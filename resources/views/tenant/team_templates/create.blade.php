@extends('layouts.tenant')

@section('title', 'Buat Template Tim Kerja')

@section('content')
    @include('tenant.team_templates._form', [
        'formTitle' => 'Buat Template Tim Kerja',
        'formSubtitle' => 'Buat nama tim dan isi anggota tetap berdasarkan bidang kerja.',
        'action' => route('tenant.team-templates.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan Template Tim',
    ])
@endsection
