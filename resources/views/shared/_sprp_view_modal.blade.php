@if($sprp)
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold">Form SPRP</h5>
                        <small class="text-muted">{{ $sprp->judul_publikasi ?? optional($sprp->publication)->nama_publikasi }}</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="sprp-detail-grid">
                        <div class="sprp-detail-item"><small>Bidang/Bagian</small><strong>{{ $sprp->bidang_bagian ?? '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>Rancangan Perwajahan</small><strong>{{ $sprp->rancangan_perwajahan ?? '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>Judul Publikasi</small><strong>{{ $sprp->judul_publikasi ?? '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>Publikasi Baru</small><strong>{{ $sprp->publikasi_baru === null ? '-' : ($sprp->publikasi_baru ? 'Ya' : 'Tidak') }}</strong></div>
                        <div class="sprp-detail-item"><small>Ukuran</small><strong>{{ $sprp->ukuran ?? '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>Orientasi</small><strong>{{ $sprp->orientasi ?? '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>Frekuensi Terbit</small><strong>{{ $sprp->frekuensi_terbit ?? '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>Terbitan Ke</small><strong>{{ $sprp->terbitan_ke ?? '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>Tahun Pertama Terbit</small><strong>{{ $sprp->tahun_pertama_terbit ?? '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>Diterbitkan Untuk</small><strong>{{ $sprp->diterbitkan_untuk ?? '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>ARC/Non-ARC</small><strong>{{ $sprp->kategori_rilis ?? '-' }}, {{ $sprp->tanggal_rilis ? $sprp->tanggal_rilis->translatedFormat('j F Y') : '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>Jumlah Halaman</small><strong>Romawi: {{ $sprp->jumlah_halaman_romawi ?? '-' }} | Arab: {{ $sprp->jumlah_halaman_arab ?? '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>Kerja Sama Luar BPS</small><strong>{{ $sprp->kerja_sama_luar_bps === null ? '-' : ($sprp->kerja_sama_luar_bps ? 'Ya' : 'Tidak') }}</strong></div>
                        <div class="sprp-detail-item"><small>Bahasa</small><strong>{{ implode(', ', $sprp->bahasa ?? []) }}</strong></div>
                        <div class="sprp-detail-item"><small>Diisi Oleh</small><strong>{{ optional($sprp->submittedBy)->name ?? '-' }}</strong></div>
                        <div class="sprp-detail-item"><small>Waktu Simpan</small><strong>{{ optional($sprp->submitted_at)->format('d-m-Y H:i') ?? '-' }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
