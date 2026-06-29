@extends('layouts.tenant')

@section('title', 'Monitoring dan Evaluasi Kabupaten/Kota')

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

    $makeSortUrl = function ($column) use ($sortBy, $sortDir) {
        $newDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

        return route('tenant.monitoring.index', array_merge(request()->query(), [
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
    <div class="card table-card monitoring-control-card mb-4">
        <div class="card-header bg-white border-0">
            <div>
                <h5 class="mb-0 fw-bold">Monitoring Publikasi Kabupaten/Kota</h5>
                <small class="text-muted">Rekap publikasi kabupaten/kota berdasarkan tahun, bulan rilis, status proses, dan kelengkapan dokumen publikasi.</small>
            </div>

            <form method="GET" class="row g-2 mt-3 monitoring-filter-row align-items-center">
                @if(request('sort_by'))
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                @endif
                @if(request('sort_dir'))
                    <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
                @endif

                <div class="col-md-2 col-xl-1">
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        @foreach($yearOptions as $year)
                            <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-xl-2">
                    <select name="bulan" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Bulan</option>
                        @foreach($monthOptions as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" {{ (int) $selectedMonth === (int) $monthNumber ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-xl-2">
                    <select name="wilayah" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Wilayah</option>
                        @foreach($wilayahOptions as $wilayah)
                            <option value="{{ $wilayah }}" {{ request('wilayah') === $wilayah ? 'selected' : '' }}>
                                {{ $wilayah }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-xl-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
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

                <div class="col-md-4 col-xl-3">
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Cari nama publikasi...">
                </div>

                <div class="col-md-2 col-xl-2">
                    <button class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="monitoring-summary-grid mb-4">
        <div class="monitoring-summary-card">
            <span class="monitoring-summary-icon bg-primary-subtle text-primary"><i class="bi bi-journal-text"></i></span>
            <div>
                <small>Total Publikasi</small>
                <strong>{{ $summary['total'] }}</strong>
            </div>
        </div>
        <div class="monitoring-summary-card">
            <span class="monitoring-summary-icon bg-info-subtle text-info"><i class="bi bi-hourglass-split"></i></span>
            <div>
                <small>Dalam Proses</small>
                <strong>{{ $summary['dalam_proses'] }}</strong>
            </div>
        </div>
        <div class="monitoring-summary-card">
            <span class="monitoring-summary-icon bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></span>
            <div>
                <small>Siap Rilis</small>
                <strong>{{ $summary['siap_rilis'] }}</strong>
            </div>
        </div>
        <div class="monitoring-summary-card">
            <span class="monitoring-summary-icon bg-success-subtle text-success"><i class="bi bi-folder-check"></i></span>
            <div>
                <small>Dokumen Lengkap</small>
                <strong>{{ $summary['lengkap'] }}</strong>
            </div>
        </div>
        <div class="monitoring-summary-card">
            <span class="monitoring-summary-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></span>
            <div>
                <small>Belum Lengkap</small>
                <strong>{{ $summary['belum_lengkap'] }}</strong>
            </div>
        </div>
    </div>

    <div class="card table-card mb-4">
        <div class="card-header bg-white border-0 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h6 class="mb-0 fw-bold">Rekap Bulanan per Kabupaten/Kota</h6>
                <small class="text-muted">Mengganti pemantauan manual per bulan dengan rekap jumlah publikasi, siap rilis, dan kelengkapan dokumen.</small>
            </div>
            <div class="monitoring-legend">
                <span><i class="bi bi-circle-fill text-primary"></i> Total Publikasi</span>
                <span><i class="bi bi-circle-fill text-success"></i> Siap</span>
                <span><i class="bi bi-circle-fill text-warning"></i> Belum lengkap</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle monitoring-month-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Wilayah</th>
                            @foreach($monthOptions as $monthName)
                                <th>{{ $monthName }}</th>
                            @endforeach
                            <th>Total<br>Publikasi</th>
                            <th>Total<br>ARC/Non-ARC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($regionalRecap as $recap)
                            <tr>
                                @php
                                    $recapCode = $recap['tenant']->code ?? null;
                                    $recapCodeDisplay = $recapCode && stripos($recapCode, 'tenant') === false
                                        ? $recapCode
                                        : ($recap['tenant']->wilayah ?? $recap['tenant']->name ?? '-');
                                @endphp
                                <td class="fw-bold text-center">{{ $recapCodeDisplay }}</td>
                                <td class="fw-semibold monitoring-region-name">{{ $recap['tenant']->wilayah ?? $recap['tenant']->name }}</td>
                                @foreach($recap['months'] as $month)
                                    <td class="text-center">
                                        @if($month['total'] > 0)
                                            <div class="monitoring-month-cell {{ $month['incomplete'] > 0 ? 'has-warning' : 'is-complete' }}">
                                                <span class="monitoring-month-main">{{ $month['ready'] }}/{{ $month['total'] }}</span>
                                                <small>{{ $month['incomplete'] }} belum lengkap</small>
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center">
                                    <div class="monitoring-total-stack">
                                        <strong>{{ $recap['total'] }}</strong>
                                        <small>({{ $recap['ready'] }}/{{ $recap['total'] }} selesai)</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="monitoring-total-stack">
                                        <strong>{{ $recap['arc'] }}/{{ $recap['non_arc'] }}</strong>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="text-center text-muted">Belum ada data wilayah kabupaten/kota.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-header bg-white border-0">
            <div>
                <h6 class="mb-0 fw-bold">Detail Kelengkapan Publikasi</h6>
                <small class="text-muted">Menampilkan status alur dan kelengkapan dokumen setiap publikasi kabupaten/kota.</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle table-clean monitoring-table monitoring-detail-table">
                    <colgroup>
                        <col class="col-no">
                        <col class="col-region">
                        <col class="col-title">
                        <col class="col-category">
                        <col class="col-date">
                        <col class="col-status">
                        <col class="col-complete">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="col-no {{ $sortThClass('created_at') }}">
                                <a href="{{ $makeSortUrl('created_at') }}" class="{{ $sortLinkClass('created_at') }}">
                                    <span>No</span>{!! $sortIcon('created_at') !!}
                                </a>
                            </th>
                            <th class="col-region {{ $sortThClass('wilayah') }}">
                                <a href="{{ $makeSortUrl('wilayah') }}" class="{{ $sortLinkClass('wilayah') }}">
                                    <span>Wilayah</span>{!! $sortIcon('wilayah') !!}
                                </a>
                            </th>
                            <th class="col-title {{ $sortThClass('nama_publikasi') }}">
                                <a href="{{ $makeSortUrl('nama_publikasi') }}" class="{{ $sortLinkClass('nama_publikasi') }}">
                                    <span>Nama Publikasi</span>{!! $sortIcon('nama_publikasi') !!}
                                </a>
                            </th>
                            <th class="col-category {{ $sortThClass('kategori') }}">
                                <a href="{{ $makeSortUrl('kategori') }}" class="{{ $sortLinkClass('kategori') }}">
                                    <span>Kategori</span>{!! $sortIcon('kategori') !!}
                                </a>
                            </th>
                            <th class="col-date {{ $sortThClass('jadwal_rilis') }}">
                                <a href="{{ $makeSortUrl('jadwal_rilis') }}" class="{{ $sortLinkClass('jadwal_rilis') }}">
                                    <span>Jadwal Rilis</span>{!! $sortIcon('jadwal_rilis') !!}
                                </a>
                            </th>
                            <th class="col-status {{ $sortThClass('status') }}">
                                <a href="{{ $makeSortUrl('status') }}" class="{{ $sortLinkClass('status') }}">
                                    <span>Status</span>{!! $sortIcon('status') !!}
                                </a>
                            </th>
                            <th class="col-complete">Kelengkapan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($publications as $index => $publication)
                            <tr>
                                <td class="col-no">{{ $publications->firstItem() + $index }}</td>
                                <td class="col-region-cell">
                                    @php
                                        $tenantCode = optional($publication->tenant)->code;
                                        $tenantCodeDisplay = $tenantCode && stripos($tenantCode, 'tenant') === false
                                            ? $tenantCode
                                            : (optional($publication->tenant)->wilayah ?? '-');
                                    @endphp
                                    <span class="d-block fw-bold">{{ $tenantCodeDisplay }}</span>
                                    <small>{{ optional($publication->tenant)->wilayah }}</small>
                                </td>
                                <td class="name-cell">
                                    {{ $publication->nama_publikasi }}
                                    <div class="monitoring-date-chips mt-2">
                                        <span>Penyusunan: {{ $publication->jadwal_mulai_penyusunan ? $publication->jadwal_mulai_penyusunan->format('d/m/y') : '-' }}</span>
                                        <span>Pemeriksaan: {{ $publication->jadwal_mulai_pemeriksaan ? $publication->jadwal_mulai_pemeriksaan->format('d/m/y') : '-' }}</span>
                                        <span>Upload: {{ $publication->jadwal_upload ? $publication->jadwal_upload->format('d/m/y') : '-' }}</span>
                                    </div>
                                </td>
                                <td class="col-category-cell">
                                    <span class="badge {{ $publication->kategori === 'ARC' ? 'bg-primary' : 'bg-secondary' }} compact-badge">
                                        {{ $publication->kategori }}
                                    </span>
                                </td>
                                <td class="col-date-cell">{!! $formatDateStack($publication->jadwal_rilis) !!}</td>
                                <td class="col-status-cell">
                                    <span class="status-chip {{ $publication->status_css_class }}">{{ $publication->status_label }}</span>
                                </td>
                                <td class="monitoring-complete-cell">
                                    <div class="monitoring-complete-head {{ $publication->getAttribute('monitoring_complete') ? 'complete' : 'warning' }}">
                                        <strong>{{ $publication->getAttribute('monitoring_available_total') }}/{{ $publication->getAttribute('monitoring_required_total') }}</strong>
                                        <span>{{ $publication->getAttribute('monitoring_complete') ? 'Lengkap' : 'Belum Lengkap' }}</span>
                                    </div>
                                    @if(!$publication->getAttribute('monitoring_complete'))
                                        <div class="monitoring-missing-list">
                                            @foreach($publication->getAttribute('monitoring_missing_items') as $missingItem)
                                                <span>{{ $missingItem }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada publikasi kabupaten/kota.</td>
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
