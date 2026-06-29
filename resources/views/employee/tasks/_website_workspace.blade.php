<div class="workflow-card website-package-card mb-4">
    <div class="workflow-card-head">
        <div>
            <span class="workflow-eyebrow"><i class="bi bi-box-seam me-1"></i> Operator Website</span>
            <h5 class="mb-1 fw-bold">Finalisasi Rilis</h5>
            <small class="text-muted">Unduh template dari admin, lengkapi nomor estimasi publikasi, lampirkan surat persetujuan rilis melalui file atau link, lalu selesaikan publikasi menjadi siap rilis.</small>
        </div>
        <span class="status-chip warning">Akhir</span>
    </div>

    @php
        $template = $documentTemplates->get('surat_persetujuan_rilis');
        $templateReady = $template && $template->file_path && (
            \Illuminate\Support\Facades\File::exists(storage_path('app/public/' . ltrim($template->file_path, '/'))) ||
            \Illuminate\Support\Facades\File::exists(public_path('storage/' . ltrim($template->file_path, '/')))
        );
        $latestApprovalLetter = $latestDocuments->get('surat_persetujuan_rilis');
    @endphp

    <div class="release-package-grid mt-3">
        <div class="release-package-step">
            <span>1</span>
            <div>
                <strong>Download template surat</strong>
                <small>Template disediakan admin melalui menu pedoman/template dokumen.</small>
                @if($templateReady)
                    <a href="{{ route('employee.tasks.download-template', $template->id) }}" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="bi bi-download me-1"></i> Template Surat Persetujuan Rilis
                    </a>
                @elseif($template)
                    <div class="text-warning small mt-2"><i class="bi bi-exclamation-triangle me-1"></i> Template terdaftar, tetapi file tidak ditemukan. Admin perlu upload ulang.</div>
                @else
                    <div class="text-muted small mt-2">Template Surat Persetujuan Rilis belum tersedia dari admin.</div>
                @endif
            </div>
        </div>

        <div class="release-package-step">
            <span>2</span>
            <div>
                <strong>Lengkapi nomor estimasi</strong>
                <small>Nomor estimasi dipakai bila publikasi belum memiliki nomor final.</small>
                <div class="mt-2">
                    <span class="badge bg-primary-subtle text-primary">Saat ini: {{ $publication->estimasi_nomor_publikasi ?: 'Belum diisi' }}</span>
                </div>
            </div>
        </div>

        <div class="release-package-step">
            <span>3</span>
            <div>
                <strong>Unggah surat persetujuan</strong>
                <small>Setelah diselesaikan, status publikasi menjadi Siap Rilis.</small>
                @if($latestApprovalLetter)
                    <a href="{{ route('employee.tasks.download-document', $latestApprovalLetter->id) }}" class="btn btn-outline-primary btn-sm mt-2" target="{{ $latestApprovalLetter->is_link ? '_blank' : '_self' }}">
                        <i class="bi {{ $latestApprovalLetter->is_link ? 'bi-box-arrow-up-right' : 'bi-download' }} me-1"></i> {{ $latestApprovalLetter->is_link ? 'Buka Link' : 'Download' }} Surat Terakhir V{{ $latestApprovalLetter->version }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <form action="{{ route('employee.tasks.complete-website-release', $publicationTeam->id) }}" method="POST" enctype="multipart/form-data" class="mt-3 release-package-form">
        @csrf
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Nomor Estimasi Publikasi</label>
                <input type="text" name="estimasi_nomor_publikasi" class="form-control" value="{{ old('estimasi_nomor_publikasi', $publication->estimasi_nomor_publikasi) }}" placeholder="Contoh: 19000.25001" required>
            </div>

            <div class="col-md-7">
                <label class="form-label fw-semibold">Surat Persetujuan Rilis</label>
                <div class="author-file-link-box website-release-file-link-box">
                    <label class="author-file-button mb-0">
                        Pilih File
                        <input type="file"
                               name="file"
                               class="author-file-native"
                               accept=".pdf,.doc,.docx">
                    </label>

                    <input type="url"
                           name="external_url"
                           class="author-link-inline-input"
                           value="{{ old('external_url') }}"
                           placeholder="atau tempel link surat persetujuan rilis"
                           inputmode="url">
                </div>
                <small class="text-muted">Isi salah satu: upload PDF/DOC/DOCX maksimal 10MB atau tempel link dokumen.</small>
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">Catatan Akhir</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan opsional untuk finalisasi siap rilis...">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> Selesaikan
            </button>
        </div>
    </form>
</div>
