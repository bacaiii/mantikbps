@extends('layouts.tenant')

@section('title', 'Detail Progres Publikasi')

@section('content')
    @php
        $documentGroups = $publication->documents->sortByDesc('uploaded_at')->groupBy('document_type');

        $documentOrder = [
            'naskah_pdf' => 'Naskah Publikasi PDF',
            'naskah_zip' => 'Naskah Publikasi RAR/ZIP',
            'infografis' => 'Infografis',
            'daftar_tabel_gambar' => 'Daftar Tabel & Gambar',
            'surat_persetujuan_rilis' => 'Surat Persetujuan Rilis',
        ];

        $sprp = $publication->sprp;
    @endphp

    <div class="card table-card mb-4">
        <div class="card-header bg-white border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="mb-0 fw-bold">{{ $publication->nama_publikasi }}</h5>
                </div>

                <span class="status-chip {{ $publication->status_css_class }}">{{ $publication->status_label }}</span>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl">
                    <strong>Kategori</strong><br>
                    {{ $publication->kategori }}
                </div>

                <div class="col-md-6 col-xl">
                    <strong>Akurasi</strong><br>
                    {{ $publication->akurasi_publikasi ?? '-' }}
                </div>

                <div class="col-md-6 col-xl">
                    <strong>Periode</strong><br>
                    {{ $publication->periode ?? '-' }}
                </div>

                <div class="col-md-6 col-xl">
                    <strong>Jadwal Rilis</strong><br>
                    {{ $publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('j F Y') : '-' }}
                </div>

                <div class="col-md-6 col-xl">
                    <strong>Tim Kerja</strong><br>
                    {{ optional($publication->team)->name ?? '-' }}
                </div>
            </div>

            <div class="alert alert-info mb-4"
                 style="background: rgba(13, 202, 240, 0.18); border: 1px solid rgba(13, 202, 240, 0.45); border-radius: 16px;">
                <strong>Keterangan:</strong>
                <div class="mt-2">
                    <i class="bi bi-folder2-open text-primary me-1"></i>
                    Halaman ini menampilkan seluruh dokumen yang diunggah serta form SPRP yang diisi oleh Tim Penyusun. Jika terdapat beberapa versi file, gunakan tombol panah kanan/kiri pada card untuk melihat setiap versi.
                </div>
            </div>

            <div class="row g-3">
                @foreach($documentOrder as $type => $label)
                    @php
                        $items = $documentGroups->get($type, collect())->values();
                    @endphp

                    <div class="col-lg-6">
                        <div class="author-upload-card h-100 document-version-slider-card" data-slider-card>
                            <div class="author-upload-head">
                                <div>
                                    <h6>{{ $label }}</h6>
                                    <small>{{ $items->count() }} versi/file tersimpan</small>
                                </div>

                                <div class="document-head-actions">
                                    <span class="upload-status-pill {{ $items->isNotEmpty() ? 'done' : 'waiting' }}">
                                        <i class="bi {{ $items->isNotEmpty() ? 'bi-check-circle' : 'bi-hourglass-split' }}"></i>
                                        {{ $items->isNotEmpty() ? 'Ada' : 'Kosong' }}
                                    </span>

                                    @if($items->count() > 1)
                                        <span class="version-counter-chip" data-slider-counter>1 / {{ $items->count() }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="author-upload-body">
                                @if($items->isNotEmpty())
                                    <div class="version-slider-shell">
                                        @if($items->count() > 1)
                                            <button type="button" class="version-nav-btn version-nav-prev" data-slider-prev aria-label="Versi sebelumnya">
                                                <i class="bi bi-chevron-left"></i>
                                            </button>
                                        @endif

                                        <div class="version-slide-stage">
                                            @foreach($items as $index => $document)
                                                <div class="version-slide {{ $index === 0 ? 'is-active' : '' }}" data-version-slide>
                                                    <div class="author-file-item mb-0">
                                                        <div>
                                                            <div class="file-name">V{{ $document->version }} - {{ $document->file_original_name }}</div>
                                                            <div class="file-meta">
                                                                {{ $document->readable_size }} • {{ optional($document->uploader)->name ?? '-' }} • {{ optional($document->uploaded_at)->format('d-m-Y H:i') }}
                                                            </div>
                                                        </div>

                                                        <div class="d-flex gap-2 flex-wrap justify-content-end">
                                                            @if($document->is_image)
                                                            <a href="{{ route('tenant.publication-progress.preview-document', $document->id) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm" title="Buka Pratinjau">
                                                                <i class="bi bi-box-arrow-up-right me-1"></i> Buka Pratinjau
                                                            </a>                                                                
                                                            @endif

                                                            <a href="{{ route('tenant.publication-progress.download-document', $document->id) }}" class="btn btn-outline-primary btn-sm" target="{{ $document->is_link ? '_blank' : '_self' }}" title="{{ $document->is_link ? 'Buka Link' : 'Download' }}">
                                                                <i class="bi {{ $document->is_link ? 'bi-box-arrow-up-right' : 'bi-download' }} me-1"></i> {{ $document->is_link ? 'Buka Link' : 'Download' }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($items->count() > 1)
                                            <button type="button" class="version-nav-btn version-nav-next" data-slider-next aria-label="Versi berikutnya">
                                                <i class="bi bi-chevron-right"></i>
                                            </button>
                                        @endif
                                    </div>

                                @else
                                    <small class="text-muted">Belum ada file yang diunggah.</small>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="col-lg-6">
                    @include('shared._sprp_document_box', [
                        'sprp' => $sprp,
                        'modalId' => 'tenantProgressSprpModal',
                    ])
                </div>
            </div>
        </div>
    </div>

    @include('shared._sprp_view_modal', [
        'sprp' => $sprp,
        'modalId' => 'tenantProgressSprpModal',
    ])

    <div class="card table-card mb-4">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Form SPRP</h5>
            <small class="text-muted">Data Surat Permintaan/Pengesahan Rancangan Publikasi yang diisi oleh Tim Penyusun.</small>
        </div>

        <div class="card-body">
            @if($sprp)
                <div class="sprp-detail-grid">
                    <div class="sprp-detail-item"><small>Bidang/Bagian</small><strong>{{ $sprp->bidang_bagian ?? '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>Rancangan Perwajahan</small><strong>{{ $sprp->rancangan_perwajahan ?? '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>Judul Publikasi</small><strong>{{ $sprp->judul_publikasi ?? '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>Publikasi Baru</small><strong>{{ $sprp->publikasi_baru ? 'Ya' : 'Tidak' }}</strong></div>
                    <div class="sprp-detail-item"><small>Ukuran</small><strong>{{ $sprp->ukuran ?? '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>Orientasi</small><strong>{{ $sprp->orientasi ?? '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>Frekuensi Terbit</small><strong>{{ $sprp->frekuensi_terbit ?? '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>Terbitan Ke</small><strong>{{ $sprp->terbitan_ke ?? '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>Tahun Pertama Terbit</small><strong>{{ $sprp->tahun_pertama_terbit ?? '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>Diterbitkan Untuk</small><strong>{{ $sprp->diterbitkan_untuk ?? '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>ARC/Non-ARC</small><strong>{{ $sprp->kategori_rilis ?? '-' }}, {{ $sprp->tanggal_rilis ? $sprp->tanggal_rilis->translatedFormat('j F Y') : '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>Jumlah Halaman</small><strong>Romawi: {{ $sprp->jumlah_halaman_romawi ?? '-' }} | Arab: {{ $sprp->jumlah_halaman_arab ?? '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>Kerja Sama Luar BPS</small><strong>{{ $sprp->kerja_sama_luar_bps ? 'Ya' : 'Tidak' }}</strong></div>
                    <div class="sprp-detail-item"><small>Bahasa</small><strong>{{ implode(', ', $sprp->bahasa ?? []) }}</strong></div>
                    <div class="sprp-detail-item"><small>Diisi Oleh</small><strong>{{ optional($sprp->submittedBy)->name ?? '-' }}</strong></div>
                    <div class="sprp-detail-item"><small>Waktu Simpan</small><strong>{{ optional($sprp->submitted_at)->format('d-m-Y H:i') }}</strong></div>
                </div>
            @else
                <p class="text-muted mb-0">Form SPRP belum diisi oleh Tim Penyusun.</p>
            @endif
        </div>
    </div>

    <a href="{{ route('tenant.publication-progress.index') }}" class="btn btn-light border">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-slider-card]').forEach(function (card) {
                const slides = Array.from(card.querySelectorAll('[data-version-slide]'));
                const prev = card.querySelector('[data-slider-prev]');
                const next = card.querySelector('[data-slider-next]');
                const counter = card.querySelector('[data-slider-counter]');
                let activeIndex = 0;

                if (!slides.length) return;

                function setActive(index) {
                    activeIndex = (index + slides.length) % slides.length;
                    slides.forEach(function (slide, slideIndex) {
                        slide.classList.toggle('is-active', slideIndex === activeIndex);
                    });
                    if (counter) {
                        counter.textContent = (activeIndex + 1) + ' / ' + slides.length;
                    }
                }

                if (prev) {
                    prev.addEventListener('click', function () {
                        setActive(activeIndex - 1);
                    });
                }

                if (next) {
                    next.addEventListener('click', function () {
                        setActive(activeIndex + 1);
                    });
                }
            });
        });
    </script>
@endpush
