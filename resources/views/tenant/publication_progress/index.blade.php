@extends('layouts.tenant')

@section('title', 'Progres Publikasi')

@php
    $formatDateStack = function ($date) {
        if (!$date) {
            return '<span class="text-muted">-</span>';
        }

        return '<div class="date-stack">
                    <span class="date-main">' . e($date->translatedFormat('j F')) . '</span>
                    <span class="date-year">' . e($date->translatedFormat('Y')) . '</span>
                </div>';
    };

    $remainingDays = function ($date) {
        if (!$date) {
            return 0;
        }

        return max(0, now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false));
    };

    $makeSortUrl = function ($column) use ($sortBy, $sortDir) {
        $newDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

        return route('tenant.publication-progress.index', array_merge(request()->query(), [
            'sort_by' => $column,
            'sort_dir' => $newDir,
        ]));
    };

    $sortIcon = function ($column) use ($sortBy, $sortDir) {
        if ($sortBy !== $column) {
            return '';
        }

        return $sortDir === 'asc'
            ? '<i class="bi bi-caret-up-fill sort-icon"></i>'
            : '<i class="bi bi-caret-down-fill sort-icon"></i>';
    };

    $sortThClass = fn ($column) => $sortBy === $column ? 'sort-active' : '';
    $sortLinkClass = fn ($column) => $sortBy === $column ? 'sort-link active' : 'sort-link';
@endphp

@section('content')
    <div class="card table-card">
        <div class="card-header bg-white border-0">
            <div>
                <h5 class="mb-0 fw-bold">Progres Publikasi</h5>
                <small class="text-muted">Pantau jadwal, dokumen, log aktivitas, dan bantuan upload tim penyusun.</small>
            </div>

            <form method="GET" class="row g-2 mt-3">
                @if(request('sort_by'))
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                @endif
                @if(request('sort_dir'))
                    <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
                @endif

                <div class="col-md-2">
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        @foreach($yearOptions as $year)
                            <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="bulan" class="form-select">
                        <option value="">Semua Bulan</option>
                        @foreach($monthOptions as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" {{ (string) $selectedMonth === (string) $monthNumber ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="penyusunan" {{ request('status') === 'penyusunan' ? 'selected' : '' }}>Penyusunan</option>
                        <option value="pemeriksaan_konten" {{ request('status') === 'pemeriksaan_konten' ? 'selected' : '' }}>Pemeriksaan Konten</option>
                        <option value="pemeriksaan_layout" {{ request('status') === 'pemeriksaan_layout' ? 'selected' : '' }}>Pemeriksaan Layout</option>
                        <option value="pemeriksaan_infografis" {{ request('status') === 'pemeriksaan_infografis' ? 'selected' : '' }}>Pemeriksaan Infografis</option>
                        <option value="persetujuan_pimpinan" {{ request('status') === 'persetujuan_pimpinan' ? 'selected' : '' }}>Persetujuan Pimpinan</option>
                        <option value="operator_website" {{ request('status') === 'operator_website' ? 'selected' : '' }}>Finalisasi Rilis</option>
                        <option value="siap_rilis" {{ request('status') === 'siap_rilis' ? 'selected' : '' }}>Siap Rilis</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Cari nama publikasi...">
                </div>

                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="alert alert-info mb-3"
                style="background: rgba(13, 202, 240, 0.18); border: 1px solid rgba(13, 202, 240, 0.45); border-radius: 16px;">
                <strong>Keterangan fitur:</strong>
                <div class="mt-2">
                    <i class="bi bi-search text-primary me-1"></i>
                    <strong>Pencarian</strong> digunakan untuk menemukan publikasi berdasarkan nama publikasi.
                </div>
                <div class="mt-1">
                    <i class="bi bi-funnel text-primary me-1"></i>
                    <strong>Filter</strong> digunakan untuk menyaring publikasi berdasarkan tahun, bulan, dan tahapan proses.
                </div>
                <div class="mt-1">
                    <i class="bi bi-arrow-down-up text-secondary me-1"></i>
                    <strong>Header tabel</strong> dapat diklik untuk mengurutkan data berdasarkan kolom.
                </div>
                <div class="mt-1">
                    <i class="bi bi-eye text-primary me-1"></i>
                    <strong>Detail</strong> digunakan untuk melihat rincian publikasi dan dokumen yang telah diunggah.
                </div>
                <div class="mt-1">
                    <i class="bi bi-clock-history text-info me-1"></i>
                    <strong>Log History</strong> digunakan untuk melihat riwayat aktivitas penyusunan, upload dokumen, pemeriksaan, dan persetujuan.
                </div>
                <div class="mt-1">
                    <i class="bi bi-people text-success me-1"></i>
                    <strong>Tim Penyusun</strong> membuka form bantuan upload naskah penyusunan apabila tim penyusun berkendala.
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-clean align-middle progress-publication-table">
                    <colgroup>
                        <col class="col-title">
                        <col class="col-release-date">
                        <col class="col-upload-date">
                        <col class="col-check-start-date">
                        <col class="col-start-date">
                        <col class="col-status">
                        <col class="col-action">
                    </colgroup>
                    <thead>
                        <tr>
                            @foreach([
                                'nama_publikasi' => 'Judul Publikasi',
                                'jadwal_rilis' => 'Tanggal Rilis',
                                'jadwal_upload' => 'Jadwal Upload',
                                'jadwal_mulai_pemeriksaan' => 'Mulai Pemeriksaan',
                                'jadwal_mulai_penyusunan' => 'Mulai Penyusunan',
                                'status' => 'Status',
                            ] as $column => $label)
                                <th class="{{ $sortThClass($column) }} {{ in_array($column, ['jadwal_mulai_pemeriksaan', 'jadwal_mulai_penyusunan'], true) ? 'col-check-start-date' : '' }}">
                                    <a href="{{ $makeSortUrl($column) }}" class="{{ $sortLinkClass($column) }}">
                                        <span>{!! in_array($label, ['Mulai Pemeriksaan', 'Mulai Penyusunan'], true) ? str_replace(' ', '<br>', e($label)) : e($label) !!}</span>
                                        {!! $sortIcon($column) !!}
                                    </a>
                                </th>
                            @endforeach
                            <th class="col-action">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($publications as $index => $publication)
                            <tr>
                                <td class="name-cell" title="{{ $publication->nama_publikasi }}">
                                    <div class="progress-title-text">{{ $publication->nama_publikasi }}</div>
                                </td>
                                <td>
                                    {!! $formatDateStack($publication->jadwal_rilis) !!}
                                    <span class="remaining-days-chip">Sisa {{ $remainingDays($publication->jadwal_rilis) }} hari</span>
                                </td>
                                <td>{!! $formatDateStack($publication->jadwal_upload) !!}</td>
                                <td>{!! $formatDateStack($publication->jadwal_mulai_pemeriksaan) !!}</td>
                                <td>{!! $formatDateStack($publication->jadwal_mulai_penyusunan) !!}</td>
                                <td><span class="status-chip {{ $publication->status_css_class }}">{{ $publication->status_label }}</span></td>
                                <td>
                                    <div class="progress-action-group">
                                        <a href="{{ route('tenant.publication-progress.show', $publication->id) }}" class="btn btn-primary btn-sm table-action-btn" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('tenant.publication-progress.history', $publication->id) }}" class="btn btn-secondary btn-sm table-action-btn" title="Log History">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                        <a href="{{ route('tenant.publication-progress.author-team', $publication->id) }}" class="btn btn-success btn-sm table-action-btn" title="Tim Penyusun">
                                            <i class="bi bi-people"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada data progres publikasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-end">
                {{ $publications->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
