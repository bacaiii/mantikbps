@extends('layouts.tenant')

@section('title', 'Rekap Laporan Publikasi')

@section('content')
    @php
        $isKabKotaTenant = in_array(auth()->user()->tenant?->type, ['kabupaten', 'kota'], true);
        $documentLabels = [
            'sprp' => 'SPRP',
            'naskah_pdf' => 'PDF',
            'naskah_zip' => 'ZIP/RAR',
            'infografis' => 'Infografis',
            'daftar_tabel_gambar' => 'Daftar Tabel & Gambar',
            'surat_persetujuan_rilis' => 'Surat Persetujuan',
        ];

        if ($isKabKotaTenant) {
            unset($documentLabels['infografis'], $documentLabels['daftar_tabel_gambar']);
        }

        $monthLabel = $selectedMonth ? ($monthOptions[(int) $selectedMonth] ?? '-') : 'Semua Bulan';
        $reportScheduleChipStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:0;padding:3px 7px;border-radius:999px;background:rgba(100,116,139,0.10);border:1px solid rgba(100,116,139,0.22);color:#475569;font-size:10px;font-weight:700;line-height:1.15;white-space:nowrap;';
        $formatReportScheduleDate = fn ($date) => $date ? $date->format('d/m/y') : '-';
    @endphp

    <div class="card table-card mb-4">
        <div class="card-header bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-0 fw-bold">Rekap Laporan Publikasi</h5>
                <small class="text-muted">Rincian publikasi berdasarkan periode {{ $monthLabel }} {{ $selectedYear }}.</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('tenant.publications.index', ['tahun' => $selectedYear, 'bulan' => $selectedMonth]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <a href="{{ route('tenant.publications.monthly-report.pdf', request()->query()) }}" class="btn btn-primary">
                    <i class="bi bi-file-earmark-excel me-1"></i> Download Rekap Excel
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tahun</label>
                    <select name="tahun" class="form-select">
                        @foreach($yearOptions as $year)
                            <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Bulan</label>
                    <select name="bulan" class="form-select">
                        <option value="">Semua Bulan</option>
                        @foreach($monthOptions as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" {{ (string) $selectedMonth === (string) $monthNumber ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Tampilkan
                    </button>
                </div>
            </form>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="monitoring-summary-card h-100">
                        <span class="monitoring-summary-icon bg-primary-subtle text-primary"><i class="bi bi-journal-text"></i></span>
                        <div>
                            <small>Total Publikasi</small>
                            <strong>{{ $summary['total'] }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="monitoring-summary-card h-100">
                        <span class="monitoring-summary-icon bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></span>
                        <div>
                            <small>Siap Rilis</small>
                            <strong>{{ $summary['siap_rilis'] }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="monitoring-summary-card h-100">
                        <span class="monitoring-summary-icon bg-info-subtle text-info"><i class="bi bi-folder-check"></i></span>
                        <div>
                            <small>Dokumen Lengkap</small>
                            <strong>{{ $summary['lengkap'] }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="monitoring-summary-card h-100">
                        <span class="monitoring-summary-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></span>
                        <div>
                            <small>Belum Lengkap</small>
                            <strong>{{ $summary['belum_lengkap'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-clean align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Publikasi</th>
                            <th>Kategori</th>
                            <th>Periode</th>
                            <th>Akurasi</th>
                            <th>Tanggal Rilis</th>
                            <th>Status</th>
                            <th>Kelengkapan Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($publications as $publication)
                            @php
                                $types = $publication->documents->pluck('document_type')->unique();
                                $checks = [
                                    'sprp' => $publication->sprp !== null,
                                    'naskah_pdf' => $types->contains('naskah_pdf'),
                                    'naskah_zip' => $types->contains('naskah_zip'),
                                    'infografis' => $types->contains('infografis'),
                                    'daftar_tabel_gambar' => $types->contains('daftar_tabel_gambar'),
                                    'surat_persetujuan_rilis' => $types->contains('surat_persetujuan_rilis'),
                                ];

                                if ($isKabKotaTenant) {
                                    unset($checks['infografis'], $checks['daftar_tabel_gambar']);
                                }
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-bold">{{ $publication->nama_publikasi }}</div>
                                    <small class="text-muted">Estimasi: {{ $publication->estimasi_nomor_publikasi ?: '-' }}</small>
                                    <div class="mt-2 d-flex flex-wrap gap-1">
                                        <span style="{{ $reportScheduleChipStyle }}" title="Penyusunan">Penyusunan {{ $formatReportScheduleDate($publication->jadwal_mulai_penyusunan) }}</span>
                                        <span style="{{ $reportScheduleChipStyle }}" title="Pemeriksaan">Pemeriksaan {{ $formatReportScheduleDate($publication->jadwal_mulai_pemeriksaan) }}</span>
                                        <span style="{{ $reportScheduleChipStyle }}" title="Upload">Upload {{ $formatReportScheduleDate($publication->jadwal_upload) }}</span>
                                    </div>
                                </td>
                                <td>{{ $publication->kategori }}</td>
                                <td>{{ $publication->periode ?? '-' }}</td>
                                <td>{{ $publication->akurasi_publikasi ?? '-' }}</td>
                                <td class="text-center fw-semibold" style="font-size:12px;">
                                    {{ $publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('d M Y') : '-' }}
                                </td>
                                <td><span class="status-chip {{ $publication->status_css_class }}">{{ $publication->status_label }}</span></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($checks as $key => $available)
                                            <span class="badge {{ $available ? 'bg-success' : 'bg-danger' }}" style="font-size: 10px;">
                                                {{ $documentLabels[$key] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Tidak ada publikasi pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
