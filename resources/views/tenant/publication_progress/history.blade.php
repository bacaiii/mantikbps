@extends('layouts.tenant')

@section('title', 'Log History Publikasi')

@section('content')
    <div class="history-hero-card mb-4">
        <div>
            <span class="history-eyebrow"><i class="bi bi-clock-history me-1"></i> Riwayat Aktivitas</span>
            <h4 class="fw-bold mb-1">{{ $publication->nama_publikasi }}</h4>
            <p class="mb-0 text-muted">Seluruh aktivitas penugasan, upload dokumen, pemeriksaan, dan finalisasi publikasi tersusun berdasarkan waktu terbaru.</p>
        </div>
        <span class="status-chip {{ $publication->status_css_class }}">{{ $publication->status_label }}</span>
    </div>

    <div class="card table-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Log History</h5>
            <small class="text-muted">Jejak proses publikasi untuk memudahkan verifikasi dan penelusuran.</small>
        </div>
        <div class="card-body">
            @if($activities->isNotEmpty())
                <div class="activity-timeline">
                    @foreach($activities as $activity)
                        @php
                            $title = strtolower($activity['title'] ?? '');
                            $icon = str_contains($title, 'upload') ? 'bi-cloud-arrow-up'
                                : (str_contains($title, 'pemeriksaan') ? 'bi-clipboard-check'
                                : (str_contains($title, 'sprp') ? 'bi-file-earmark-text'
                                : (str_contains($title, 'persetujuan') ? 'bi-patch-check'
                                : 'bi-arrow-repeat')));
                        @endphp
                        <div class="timeline-entry">
                            <div class="timeline-marker">
                                <i class="bi {{ $icon }}"></i>
                            </div>
                            <div class="timeline-card">
                                <div class="timeline-card-head">
                                    <div>
                                        <h6>{{ $activity['title'] }}</h6>
                                        <small>
                                            {{ optional($activity['time'])->format('d-m-Y H:i') ?: '-' }}
                                            @if($activity['actor']) • Oleh {{ $activity['actor'] }} @endif
                                        </small>
                                    </div>
                                </div>
                                @if($activity['description'])
                                    <div class="timeline-description">
                                        {{ $activity['description'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="guideline-empty-card">
                    <i class="bi bi-inbox"></i>
                    <strong>Belum ada log aktivitas</strong>
                    <span>Aktivitas akan muncul setelah ada penugasan, upload dokumen, atau pemeriksaan.</span>
                </div>
            @endif
        </div>
    </div>

    <a href="{{ route('tenant.publication-progress.index') }}" class="btn btn-light border mt-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
@endsection
