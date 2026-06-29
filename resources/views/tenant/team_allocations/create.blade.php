@extends('layouts.tenant')

@section('title', 'Alokasi Tim')

@section('content')
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Form Alokasi Tim</h5>
            <small class="text-muted">Pilih judul publikasi dan template tim kerja yang akan dialokasikan.</small>
        </div>
        <div class="card-body">
            @if($publications->isEmpty())
                <div class="alert alert-warning">
                    Semua publikasi sudah memiliki tim kerja atau belum ada publikasi yang tersedia.
                </div>
            @endif

            @if($teamTemplates->isEmpty())
                <div class="alert alert-warning">
                    Belum ada template tim aktif. Buat template terlebih dahulu pada menu <strong>Atur Tim Kerja</strong>.
                </div>
            @endif

            @include('tenant.team_allocations._form', [
                'formAction' => route('tenant.team-allocations.store'),
                'formMethod' => 'POST',
                'submitLabel' => 'Simpan Alokasi Tim',
                'team' => $team,
                'publications' => $publications,
                'teamTemplates' => $teamTemplates,
            ])
        </div>
    </div>
@endsection
