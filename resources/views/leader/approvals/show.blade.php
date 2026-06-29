@extends('layouts.leader')

@section('title', 'Detail Persetujuan')

@section('content')
    @php
        $team = $publication->team;
        $sprp = $publication->sprp;
        $allReviews = $publication->drafts
            ->flatMap(fn($draft) => $draft->reviews)
            ->sortByDesc('created_at')
            ->values();

        $teamRoleLabels = [
            'penyusun_naskah' => 'Penyusun Naskah',
            'ketua_pemeriksa_konten' => 'Ketua Pemeriksa Konten',
            'anggota_pemeriksa_konten' => 'Anggota Pemeriksa Konten',
            'ketua_pemeriksa_layout' => 'Ketua Pemeriksa Layout',
            'anggota_pemeriksa_layout' => 'Anggota Pemeriksa Layout',
            'operator_infografis' => 'Operator Infografis',
            'operator_website' => 'Operator Website',
        ];

        $documentTypeOrder = ['naskah_pdf', 'naskah_zip', 'infografis', 'daftar_tabel_gambar', 'surat_persetujuan_rilis'];
        $documentGroups = $publication->documents
            ->whereIn('document_type', $documentTypeOrder)
            ->sortByDesc('version')
            ->groupBy('document_type')
            ->sortBy(function ($items, $type) use ($documentTypeOrder) {
                $index = array_search($type, $documentTypeOrder, true);

                return $index === false ? 999 : $index;
            });

        $revisionNumbers = $allReviews
            ->where('result', 'revisi')
            ->groupBy('review_type')
            ->flatMap(function ($items) {
                return $items
                    ->sortBy(fn($review) => optional($review->reviewed_at ?? $review->created_at)->timestamp ?? 0)
                    ->values()
                    ->mapWithKeys(fn($review, $index) => [$review->id => $index + 1]);
            });
    @endphp

    <div class="row g-4">
        <div class="col-12">
            <div class="workflow-card mb-4">
                <div class="workflow-card-head">
                    <div>
                        <span class="workflow-eyebrow"><i class="bi bi-journal-check me-1"></i> Ringkasan Publikasi</span>
                        <h4 class="fw-bold mb-1">{{ $publication->nama_publikasi }}</h4>
                        <small class="text-muted">{{ optional($team)->name ?? '-' }}</small>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#leaderActiveTeamModal">
                        <i class="bi bi-people me-1"></i> Lihat Anggota Tim
                    </button>
                </div>

                <div class="row g-3 align-items-start mt-2">
                    <div class="col-md-4">
                        <strong>Kategori</strong><br>
                        <span class="badge {{ $publication->kategori === 'ARC' ? 'bg-primary' : 'bg-secondary' }}">{{ $publication->kategori }}</span>
                    </div>

                    <div class="col-md-4">
                        <strong>Status Publikasi</strong><br>
                        <span class="status-chip {{ $publication->status_css_class }}">{{ $publication->status_label }}</span>
                    </div>

                    <div class="col-md-4">
                        <strong>Jadwal Rilis</strong><br>
                        {{ $publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('j F Y') : '-' }}
                    </div>

                    <div class="col-md-4">
                        <strong>Periode</strong><br>
                        {{ $publication->periode ?? '-' }}
                    </div>

                    <div class="col-md-4">
                        <strong>Akurasi</strong><br>
                        {{ $publication->akurasi_publikasi ?? '-' }}
                    </div>

                    <div class="col-md-4">
                        <strong>Nomor Estimasi Publikasi</strong><br>
                        {{ $publication->estimasi_nomor_publikasi ?: 'Belum diisi' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card table-card mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold">Riwayat Pemeriksaan</h6>
                        <small class="text-muted">Riwayat pemeriksaan ditampilkan dalam area scroll.</small>
                    </div>
                </div>

                <div class="card-body" style="max-height: 660px; overflow-y: auto; padding-right: 8px;">
                    @forelse($allReviews as $review)
                        @php
                            $revisionSlides = data_get($review->checklist, 'slides', []);
                            $revisionDocuments = collect(data_get($review->checklist, 'revision_documents', []));
                            $reviewTypeLabel = $review->review_type_label;
                            $revisionNumber = $review->result === 'revisi' ? ($revisionNumbers[$review->id] ?? 1) : null;
                            $reviewTitle = $review->result === 'revisi'
                                ? $reviewTypeLabel . ' - Revisi ' . $revisionNumber
                                : $reviewTypeLabel . ' - ' . $review->result_label;
                            $draftVersion = data_get($review->checklist, 'draft_version', optional($review->draft)->version);
                            $modalId = 'leader-revision-modal-' . $review->id;
                            $hasRevisionDetail = $review->result === 'revisi'
                                && (count($revisionSlides) > 0 || $review->review_type === 'infografis' || $review->review_type === 'pimpinan');
                        @endphp

                        <div class="review-history-item">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong>{{ $reviewTitle }}</strong>
                                    @if($draftVersion)
                                        <span class="badge bg-light text-dark border ms-1">Draft V{{ $draftVersion }}</span>
                                    @endif
                                    <br>
                                    <small class="text-muted">
                                        Oleh {{ optional($review->reviewer)->name ?? '-' }}<br>
                                        {{ optional($review->reviewed_at)->format('d-m-Y H:i') }}
                                    </small>
                                </div>

                                @if($hasRevisionDetail)
                                    <button type="button" class="btn btn-outline-danger btn-sm revision-detail-btn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                        <i class="bi bi-eye me-1"></i> Lihat Revisi
                                    </button>
                                @endif
                            </div>
                            <div class="small mt-2 text-muted">{{ $review->notes }}</div>
                        </div>

                        @if($hasRevisionDetail)
                            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content revision-modal-content">
                                        <div class="modal-header align-items-start">
                                            <div>
                                                <h5 class="modal-title fw-bold">Detail {{ $reviewTitle }}</h5>
                                                @if(in_array($review->review_type, ['konten', 'layout'], true))
                                                    <small class="text-muted">Yang ditampilkan hanya rincian yang dipilih Tidak oleh pemeriksa.</small>
                                                @elseif($review->review_type === 'infografis')
                                                    <small class="text-muted">Menampilkan catatan revisi serta file hasil pemeriksaan infografis.</small>
                                                @else
                                                    <small class="text-muted">Menampilkan catatan revisi dari pimpinan.</small>
                                                @endif
                                            </div>
                                            <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="revision-final-note mb-3">
                                                <strong>Catatan keputusan akhir:</strong><br>
                                                {{ data_get($review->checklist, 'final_notes', $review->notes) }}
                                            </div>

                                            @if(in_array($review->review_type, ['konten', 'layout'], true))
                                                @foreach($revisionSlides as $slide)
                                                    <div class="revision-slide-card">
                                                        <div class="revision-slide-head">
                                                            <div>
                                                                <span>Anatomi</span>
                                                                <h6>{{ data_get($slide, 'anatomy_section', '-') }}</h6>
                                                                <small class="revision-sub-anatomy">Sub-anatomi: {{ data_get($slide, 'sub_anatomy', '-') }}</small>
                                                            </div>
                                                            <small>{{ data_get($slide, 'reviewer_role', 'Pemeriksa') }}: {{ data_get($slide, 'reviewer_name', '-') }}</small>
                                                        </div>

                                                        <div class="revision-failed-list">
                                                            <strong>Rincian yang perlu direvisi:</strong>
                                                            <ul>
                                                                @foreach(data_get($slide, 'failed_items', []) as $failedItem)
                                                                    <li>
                                                                        <span>{{ data_get($failedItem, 'requirement_detail', '-') }}</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>

                                                        <div class="revision-note-box">
                                                            <strong>Catatan slide:</strong><br>
                                                            {{ data_get($slide, 'notes') ?: 'Tidak ada catatan tambahan.' }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @elseif($review->review_type === 'infografis')
                                                <div class="revision-slide-card">
                                                    <div class="revision-slide-head">
                                                        <div>
                                                            <span>File hasil pemeriksaan</span>
                                                            <h6>Infografis dan Daftar Tabel/Gambar</h6>
                                                            <small class="revision-sub-anatomy">File diunggah oleh operator infografis sebagai referensi revisi.</small>
                                                        </div>
                                                    </div>

                                                    @if($revisionDocuments->count() > 0)
                                                        <div class="d-flex flex-column gap-2">
                                                            @foreach($revisionDocuments as $document)
                                                                <div class="d-flex justify-content-between align-items-center gap-2 border rounded-3 p-2 bg-light">
                                                                    <div class="small">
                                                                        <strong>{{ data_get($document, 'label', '-') }}</strong><br>
                                                                        <span class="text-muted">V{{ data_get($document, 'version', '-') }} • {{ data_get($document, 'file_original_name', '-') }}</span>
                                                                    </div>
                                                                    @if(data_get($document, 'id'))
                                                                        <a href="{{ route('leader.approvals.download-document', data_get($document, 'id')) }}" class="btn btn-outline-primary btn-sm" target="{{ data_get($document, 'is_link') ? '_blank' : '_self' }}">
                                                                            <i class="bi {{ data_get($document, 'is_link') ? 'bi-box-arrow-up-right' : 'bi-download' }} me-1"></i> {{ data_get($document, 'is_link') ? 'Buka Link' : 'Download' }}
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <small class="text-muted">Tidak ada file hasil pemeriksaan yang diunggah.</small>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <small class="text-muted">Belum ada riwayat pemeriksaan.</small>
                    @endforelse
                </div>
            </div>

            @if($publication->status === 'persetujuan_pimpinan')
                <div class="workflow-card mb-4">
                    <div class="workflow-card-head">
                        <div>
                            <span class="workflow-eyebrow"><i class="bi bi-patch-check me-1"></i> Keputusan Pimpinan</span>
                            <h5 class="mb-1 fw-bold">Beri Persetujuan atau Kembalikan Revisi</h5>
                            <small class="text-muted">Jika disetujui, publikasi masuk ke operator website untuk dibungkus menjadi siap rilis.</small>
                        </div>
                    </div>

                    <form action="{{ route('leader.approvals.decide', $publication->id) }}" method="POST" class="mt-3">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Keputusan</label>
                                <select name="result" class="form-select" required>
                                    <option value="">-- Pilih Keputusan --</option>
                                    <option value="disetujui" {{ old('result') === 'disetujui' ? 'selected' : '' }}>Setujui, lanjut Operator Website</option>
                                    <option value="revisi" {{ old('result') === 'revisi' ? 'selected' : '' }}>Revisi, kembalikan ke Tim Penyusun</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Catatan Keputusan</label>
                                <textarea name="notes" class="form-control" rows="3" required placeholder="Tulis catatan persetujuan atau arahan revisi...">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Keputusan</button>
                        </div>
                    </form>
                </div>
            @else
                <div class="alert alert-info">
                    Publikasi ini tidak sedang menunggu keputusan pimpinan. Status saat ini: <strong>{{ $publication->status_label }}</strong>.
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card table-card mb-4">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0 fw-bold">Dokumen Publikasi</h6>
                </div>

                <div class="card-body employee-document-slider-list">
                    @forelse($documentGroups as $documentType => $items)
                        @php
                            $items = $items->sortByDesc('version')->values();
                            $latestDocument = $items->first();
                            $totalVersions = $items->count();
                        @endphp

                        <div class="employee-document-version-card" data-version-slider>
                            <div class="employee-document-version-head">
                                <div>
                                    <strong>{{ optional($latestDocument)->document_type_label }}</strong>
                                    <small>{{ $totalVersions }} versi dokumen</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary-subtle text-primary">Ada</span>
                                    @if($totalVersions > 1)
                                        <div class="mt-1">
                                            <span class="badge bg-light text-primary border" data-version-counter>1/{{ $totalVersions }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="version-slider-shell employee-version-slider-shell {{ $totalVersions <= 1 ? 'single-version' : '' }}">
                                @if($totalVersions > 1)
                                    <button type="button" class="version-nav-btn" data-version-prev aria-label="Versi sebelumnya">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                @endif

                                <div class="version-slide-stage">
                                    @foreach($items as $document)
                                        <div class="version-slide {{ $loop->first ? 'is-active' : '' }}" data-version-slide>
                                            <div class="employee-document-version-body">
                                                <div class="employee-document-file-info">
                                                    <span>Versi {{ $document->version }}</span>
                                                    <strong title="{{ $document->file_original_name }}">{{ $document->file_original_name }}</strong>
                                                    <small>
                                                        Oleh {{ optional($document->uploader)->name ?? '-' }}<br>
                                                        {{ optional($document->uploaded_at)->format('d-m-Y H:i') }}
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="employee-document-version-actions">
                                                <a href="{{ route('leader.approvals.download-document', $document->id) }}" class="btn btn-outline-primary btn-sm" target="{{ $document->is_link ? '_blank' : '_self' }}">
                                                    <i class="bi {{ $document->is_link ? 'bi-box-arrow-up-right' : 'bi-download' }} me-1"></i> {{ $document->is_link ? 'Buka Link' : 'Download' }}
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if($totalVersions > 1)
                                    <button type="button" class="version-nav-btn" data-version-next aria-label="Versi berikutnya">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <small class="text-muted">Belum ada dokumen penyusun.</small>
                    @endforelse

                    @include('shared._sprp_document_box', [
                        'sprp' => $sprp,
                        'modalId' => 'leaderSprpModal',
                    ])
                </div>
            </div>
        </div>

    </div>

    @include('shared._sprp_view_modal', [
        'sprp' => $sprp,
        'modalId' => 'leaderSprpModal',
    ])

    <div class="modal fade" id="leaderActiveTeamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold">Tim Aktif Saat Ini</h5>
                        <small class="text-muted">{{ optional($team)->name ?? '-' }}</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        @foreach($teamRoleLabels as $role => $label)
                            @php
                                $teamMembers = optional($team)->assignments?->where('assignment_role', $role) ?? collect();
                            @endphp
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="fw-semibold mb-2">{{ $label }}</div>
                                    @if($teamMembers->count() > 0)
                                        <ul class="mb-0 ps-3">
                                            @foreach($teamMembers as $member)
                                                <li>{{ optional($member->user)->name ?? '-' }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <small class="text-muted">Belum diatur</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-version-slider]').forEach(function (slider) {
            const slides = Array.from(slider.querySelectorAll('[data-version-slide]'));
            const counter = slider.querySelector('[data-version-counter]');
            const prev = slider.querySelector('[data-version-prev]');
            const next = slider.querySelector('[data-version-next]');
            let index = 0;

            function render() {
                slides.forEach(function (slide, slideIndex) {
                    slide.classList.toggle('is-active', slideIndex === index);
                });

                if (counter) {
                    counter.textContent = (index + 1) + '/' + slides.length;
                }

                if (prev) {
                    prev.disabled = index === 0;
                }

                if (next) {
                    next.disabled = index === slides.length - 1;
                }
            }

            if (prev) {
                prev.addEventListener('click', function () {
                    index = Math.max(index - 1, 0);
                    render();
                });
            }

            if (next) {
                next.addEventListener('click', function () {
                    index = Math.min(index + 1, slides.length - 1);
                    render();
                });
            }

            render();
        });
    });
</script>
@endpush
