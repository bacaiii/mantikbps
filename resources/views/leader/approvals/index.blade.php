@extends('layouts.leader')

@section('title', 'Persetujuan Rilis')

@section('content')
    <div class="card table-card">
        <div class="card-header bg-white border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Daftar Persetujuan Publikasi</h5>
                    <small class="text-muted">Publikasi yang sudah selesai diperiksa akan masuk ke tahap persetujuan pimpinan.</small>
                </div>
            </div>

            <form method="GET" class="row g-2 mt-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status Akhir</option>
                        <option value="persetujuan_pimpinan" {{ request('status') === 'persetujuan_pimpinan' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                        <option value="operator_website" {{ request('status') === 'operator_website' ? 'selected' : '' }}>Finalisasi Rilis</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Cari nama publikasi...">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Filter</button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="approval-card-list">
                @forelse($publications as $publication)
                    <div class="approval-list-card approval-list-card-large">
                        <div class="approval-card-main">
                            <div class="approval-status-line">
                                <span class="badge {{ $publication->kategori === 'ARC' ? 'bg-primary' : 'bg-secondary' }} compact-badge">{{ $publication->kategori }}</span>
                                <span class="status-chip {{ $publication->status_css_class }}">{{ $publication->status_label }}</span>
                            </div>
                            <h6>{{ $publication->nama_publikasi }}</h6>
                            <div class="approval-meta-grid">
                                <span><i class="bi bi-calendar-event"></i> Rilis: {{ $publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('j F Y') : '-' }}</span>
                                <span><i class="bi bi-people"></i> Tim: {{ optional($publication->team)->name ?? '-' }}</span>
                                <span><i class="bi bi-check2-circle"></i> Akurasi Publikasi: {{ $publication->akurasi_publikasi ?? '-' }}</span>
                            </div>
                        </div>
                        <a href="{{ route('leader.approvals.show', $publication->id) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-eye me-1"></i> Detail
                        </a>
                    </div>
                @empty
                    <div class="empty-state-soft">
                        <i class="bi bi-inboxes"></i>
                        <strong>Belum ada data</strong>
                        <span>Data persetujuan akan muncul setelah publikasi melewati pemeriksaan infografis.</span>
                    </div>
                @endforelse
            </div>

            <div class="mt-3 d-flex justify-content-end">
                {{ $publications->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
