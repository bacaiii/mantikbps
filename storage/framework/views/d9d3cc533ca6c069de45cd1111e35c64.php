<?php $__env->startSection('title', 'Detail Tugas'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $publication = $publicationTeam->publication;

        $isPenyusun = in_array('penyusun_naskah', $myRoles);

        $isKetuaKonten = in_array('ketua_pemeriksa_konten', $myRoles);
        $isAnggotaKonten = in_array('anggota_pemeriksa_konten', $myRoles);
        $canReviewKonten = $isKetuaKonten || $isAnggotaKonten;

        $isKetuaLayout = in_array('ketua_pemeriksa_layout', $myRoles);
        $isAnggotaLayout = in_array('anggota_pemeriksa_layout', $myRoles);
        $canReviewLayout = $isKetuaLayout || $isAnggotaLayout;

        $isOperatorInfografis = in_array('operator_infografis', $myRoles);
        $isOperatorWebsite = in_array('operator_website', $myRoles);
        $isProvinsiTenant = optional($publication->tenant)->type === 'provinsi';
        $hasInfographicReviewDocuments = $documentsByType->has('infografis') || $documentsByType->has('daftar_tabel_gambar');

        $authorWorkUnlocked = $authorWorkUnlocked ?? true;
        $blockingAuthorTitle = optional(optional($blockingAuthorTeam ?? null)->publication)->nama_publikasi;
        $canEditAuthorDocuments = $isPenyusun && $publication->status === 'penyusunan' && $authorWorkUnlocked;
        $canOpenKontenForm = $canReviewKonten && $publication->status === 'pemeriksaan_konten';
        $canOpenLayoutForm = $canReviewLayout && $publication->status === 'pemeriksaan_layout';
        $canOpenInfographicForm = $isOperatorInfografis && $publication->status === 'pemeriksaan_infografis' && ($isProvinsiTenant || $hasInfographicReviewDocuments);
        $canOpenWebsiteForm = $isOperatorWebsite && $publication->status === 'operator_website';

        $completionLabels = [
            'naskah_pdf' => 'Naskah PDF',
            'naskah_zip' => 'Naskah RAR/ZIP',
            'sprp' => 'Form SPRP',
        ];

        if ($isProvinsiTenant) {
            $completionLabels['infografis'] = 'Infografis';
            $completionLabels['daftar_tabel_gambar'] = 'Daftar Tabel & Gambar';
        }

        $allComplete = !in_array(false, $completion, true);

        $documentConfigs = [
            'naskah_pdf' => [
                'title' => 'Upload Naskah Publikasi PDF',
                'subtitle' => 'Naskah utama untuk pemeriksa konten dan layout.',
                'icon' => 'bi-file-earmark-pdf',
                'accept' => '.pdf',
                'help' => 'Format PDF. Maksimal 20MB.',
                'multiple' => false,
            ],
            'naskah_zip' => [
                'title' => 'Upload Naskah Publikasi RAR/ZIP',
                'subtitle' => 'File sumber naskah yang dapat diunduh pemeriksa.',
                'icon' => 'bi-file-earmark-zip',
                'accept' => '.zip,.rar',
                'help' => 'Format ZIP atau RAR. Maksimal 50MB.',
                'multiple' => false,
            ],
            'infografis' => [
                'title' => 'Upload File Infografis',
                'subtitle' => $isProvinsiTenant ? 'Nanti akan diperiksa oleh Operator Infografis.' : 'Opsional untuk BPS kabupaten/kota.',
                'icon' => 'bi-image',
                'accept' => '.jpg,.jpeg',
                'help' => 'Format JPG/JPEG. Maksimal 500KB per file.',
                'multiple' => true,
            ],
            'daftar_tabel_gambar' => [
                'title' => 'Upload Daftar Tabel & Gambar',
                'subtitle' => $isProvinsiTenant ? 'Daftar nama tabel dan gambar dalam format Excel.' : 'Opsional untuk BPS kabupaten/kota.',
                'icon' => 'bi-file-earmark-spreadsheet',
                'accept' => '.xls,.xlsx,.csv',
                'help' => 'Format XLS, XLSX, atau CSV. Maksimal 10MB.',
                'multiple' => false,
            ],
        ];

        $teamRoleLabels = [
            'penyusun_naskah' => 'Penyusun Naskah',
            'ketua_pemeriksa_konten' => 'Ketua Pemeriksa Konten',
            'anggota_pemeriksa_konten' => 'Anggota Pemeriksa Konten',
            'ketua_pemeriksa_layout' => 'Ketua Pemeriksa Layout',
            'anggota_pemeriksa_layout' => 'Anggota Pemeriksa Layout',
            'operator_infografis' => 'Operator Infografis',
            'operator_website' => 'Operator Website',
        ];

        $sprpOldBool = function ($field, $default = null) use ($sprp) {
            $value = old($field, optional($sprp)->{$field} ?? $default);
            if ($value === null) return '';
            return (string) ((int) (bool) $value);
        };

        $sprpBahasa = old('bahasa', optional($sprp)->bahasa ?? ['Indonesia']);
        $ukuranOptions = ['B5 ISO', 'B5 JIS', 'A5', 'A4', 'Lainnya'];
        $currentUkuran = old('ukuran', optional($sprp)->ukuran);

    ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card form-card mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="mb-0 fw-bold"><?php echo e($publication->nama_publikasi); ?></h5>
                        <small class="text-muted"><?php echo e($publicationTeam->name ?? '-'); ?></small>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if($latestDocuments->get('naskah_pdf') && ($isPenyusun || $canReviewKonten || $canReviewLayout)): ?>
                            <a href="<?php echo e(route('employee.tasks.document-review', $publicationTeam->id)); ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil-square me-1"></i> Review Dokumen
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#activeTeamModal">
                            <i class="bi bi-people me-1"></i> Lihat Anggota Tim
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-4">
                            <strong>Kategori</strong><br>
                            <span class="badge <?php echo e($publication->kategori === 'ARC' ? 'bg-primary' : 'bg-secondary'); ?>"><?php echo e($publication->kategori); ?></span>
                        </div>

                        <div class="col-md-4">
                            <strong>Status Publikasi</strong><br>
                            <span class="status-chip <?php echo e($publication->status_css_class); ?>"><?php echo e($publication->status_label); ?></span>
                        </div>

                        <div class="col-md-4">
                            <strong>Jadwal Rilis</strong><br>
                            <?php echo e($publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('j F Y') : '-'); ?>

                        </div>

                        <div class="col-md-4">
                            <strong>Periode</strong><br>
                            <?php echo e($publication->periode ?? '-'); ?>

                        </div>

                        <div class="col-md-4">
                            <strong>Akurasi</strong><br>
                            <?php echo e($publication->akurasi_publikasi ?? '-'); ?>

                        </div>

                        <div class="col-md-4">
                            <strong>Tugas Saya</strong><br>
                            <?php $__currentLoopData = $myRoles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="badge bg-primary-subtle text-primary me-1 mt-1">
                                    <?php echo e(match($role) {
                                        'penyusun_naskah' => 'Tim Penyusun',
                                        'ketua_pemeriksa_konten' => 'Ketua Pemeriksa Konten',
                                        'anggota_pemeriksa_konten' => 'Anggota Pemeriksa Konten',
                                        'ketua_pemeriksa_layout' => 'Ketua Pemeriksa Layout',
                                        'anggota_pemeriksa_layout' => 'Anggota Pemeriksa Layout',
                                        'operator_website' => 'Operator Website',
                                        'operator_infografis' => 'Operator Infografis',
                                        default => $role,
                                    }); ?>

                                </span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if($isPenyusun): ?>
                <div class="completion-panel mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
                        <div>
                            <h5 class="mb-0 fw-bold">Menu Tim Penyusun</h5>
                            <small class="text-muted">
                                <?php if($isRevisionReturn): ?>
                                    Lengkapi perbaikan, lalu submit ulang ke <strong><?php echo e($authorSubmitTargetLabel); ?></strong>.
                                <?php else: ?>
                                    Lengkapi dokumen dan SPRP, lalu tekan tombol Submit.
                                <?php endif; ?>
                            </small>
                        </div>

                        <form action="<?php echo e(route('employee.tasks.submit-author-work', $publicationTeam->id)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <button class="btn btn-primary" <?php echo e((!$canEditAuthorDocuments || !$allComplete) ? 'disabled' : ''); ?>>
                                <i class="bi bi-send-check me-1"></i>
                                <?php echo e($isRevisionReturn ? 'Submit Revisi ke ' . $authorSubmitTargetLabel : 'Submit ke Pemeriksaan Konten'); ?>

                            </button>
                        </form>
                    </div>

                    <div class="row g-2 mt-1">
                        <?php $__currentLoopData = $completionLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col-md-6">
                                <div class="completion-item">
                                    <span><?php echo e($label); ?></span>
                                    <?php if($completion[$key] ?? false): ?>
                                        <span class="completion-icon done"><i class="bi bi-check-lg"></i></span>
                                    <?php else: ?>
                                        <span class="completion-icon waiting"><i class="bi bi-hourglass-split"></i></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <?php if($isPenyusun && !$authorWorkUnlocked): ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>Pengelolaan penyusunan belum dapat dilakukan.</strong><br>
                            Selesaikan dan submit penyusunan publikasi
                            <?php if($blockingAuthorTitle): ?>
                                <strong>"<?php echo e($blockingAuthorTitle); ?>"</strong>
                            <?php else: ?>
                                sebelumnya
                            <?php endif; ?>
                            terlebih dahulu sebelum mengelola publikasi ini.
                        </div>
                    <?php elseif(!$canEditAuthorDocuments): ?>
                        <div class="alert alert-info mt-3 mb-0">
                            Dokumen penyusunan hanya dapat diubah pada tahap <strong>Penyusunan/Revisi</strong>.
                            Saat ini publikasi berada pada tahap <strong><?php echo e($publication->status_label); ?></strong>.
                        </div>
                    <?php elseif(!$allComplete): ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            Tombol <strong>Submit</strong> akan aktif setelah seluruh dokumen utama dan form SPRP lengkap.
                        </div>
                    <?php elseif($isRevisionReturn): ?>
                        <div class="alert alert-info mt-3 mb-0">
                            Revisi ini berasal dari <strong><?php echo e($authorSubmitTargetLabel); ?></strong>, sehingga setelah Submit ulang publikasi akan langsung kembali ke tahap tersebut.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="author-input-box mb-4">
                    <div class="author-input-head">
                        <div>
                            <h5 class="mb-0 fw-bold">Upload Dokumen Tim Penyusun</h5>
                            <small class="text-muted">Gunakan input box berikut agar dokumen penyusunan tersimpan per jenis file.</small>
                        </div>
                    </div>

                    <div class="author-input-body author-input-card-list">
                        <?php $__currentLoopData = $documentConfigs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $config): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $items = $documentsByType->get($type, collect())->sortByDesc('version');
                                $latestItem = $latestDocuments->get($type);
                                $isDone = $items->isNotEmpty();
                                $isOptionalDocument = !$isProvinsiTenant && in_array($type, ['infografis', 'daftar_tabel_gambar'], true);
                            ?>

                            <div class="author-upload-card">
                                <div class="author-upload-status-corner">
                                    <?php if($isDone): ?>
                                        <span class="upload-status-pill done"><i class="bi bi-check-circle"></i> Lengkap</span>
                                    <?php elseif($isOptionalDocument): ?>
                                        <span class="upload-status-pill optional"><i class="bi bi-info-circle"></i> Opsional</span>
                                    <?php else: ?>
                                        <span class="upload-status-pill waiting"><i class="bi bi-hourglass-split"></i> Belum</span>
                                    <?php endif; ?>
                                </div>

                                <div class="author-upload-title-line">
                                    <span class="author-input-icon"><i class="bi <?php echo e($config['icon']); ?>"></i></span>
                                    <div class="author-upload-title-text">
                                        <strong><?php echo e($config['title']); ?></strong>
                                    </div>
                                </div>

                                <div class="author-upload-content-grid">
                                    <div class="author-upload-left">
                                        <?php if($canEditAuthorDocuments): ?>
                                            <form action="<?php echo e(route('employee.tasks.upload-document', $publicationTeam->id)); ?>"
                                                  method="POST"
                                                  enctype="multipart/form-data"
                                                  class="author-card-upload-form">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="document_type" value="<?php echo e($type); ?>">

                                                <div class="author-upload-input-line">
                                                    <div class="author-file-link-box">
                                                        <label class="author-file-button mb-0">
                                                            Pilih File
                                                            <?php if($config['multiple']): ?>
                                                                <input type="file"
                                                                       name="files[]"
                                                                       class="author-file-native infographic-input"
                                                                       accept="<?php echo e($config['accept']); ?>"
                                                                       multiple>
                                                            <?php else: ?>
                                                                <input type="file"
                                                                       name="file"
                                                                       class="author-file-native"
                                                                       accept="<?php echo e($config['accept']); ?>">
                                                            <?php endif; ?>
                                                        </label>

                                                        <input type="url"
                                                               name="external_url"
                                                               class="author-link-inline-input"
                                                               placeholder="atau tempel link dokumen eksternal"
                                                               inputmode="url">
                                                    </div>

                                                    <button class="btn btn-primary btn-sm author-upload-btn">
                                                        <i class="bi bi-cloud-arrow-up me-1"></i> Simpan
                                                    </button>
                                                </div>

                                                <?php if($config['multiple']): ?>
                                                    <div class="infographic-preview-grid mt-2 selected-preview"></div>
                                                <?php endif; ?>
                                            </form>
                                        <?php else: ?>
                                            <div class="author-locked-upload">
                                                <?php if($isPenyusun && !$authorWorkUnlocked): ?>
                                                    Input dikunci sampai penyusunan publikasi sebelumnya disubmit.
                                                <?php else: ?>
                                                    Input dikunci karena publikasi tidak berada pada tahap penyusunan/revisi.
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <small class="author-upload-help"><?php echo e($config['help']); ?></small>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <div class="sprp-manual-box mb-4">
                    <div class="sprp-manual-title">
                        <h5>SURAT PERMINTAAN / PENGESAHAN RANCANGAN PUBLIKASI (SPRP)</h5>
                        <small>Isi mengikuti format SPRP manual BPS. Bagian default otomatis mengikuti data tim kerja dan publikasi.</small>
                    </div>

                    <form action="<?php echo e(route('employee.tasks.save-sprp', $publicationTeam->id)); ?>" method="POST">
                        <?php echo csrf_field(); ?>

                        <div class="sprp-manual-table">
                            <div class="sprp-line full">
                                <span class="sprp-no">1.</span>
                                <label>Bidang/Bagian</label>
                                <div class="sprp-field readonly-field"><?php echo e($publicationTeam->name); ?></div>
                            </div>

                            <div class="sprp-line full align-top">
                                <span class="sprp-no">2.</span>
                                <label>Rancangan Perwajahan</label>
                                <div class="sprp-field">
                                    <select name="rancangan_perwajahan" class="form-select form-select-sm" required <?php echo e(!$canEditAuthorDocuments ? 'disabled' : ''); ?>>
                                        <option value="">-- Pilih --</option>
                                        <?php $__currentLoopData = ['Seksi Diseminasi dan Layanan Statistik', 'subject matter']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($option); ?>" <?php echo e(old('rancangan_perwajahan', optional($sprp)->rancangan_perwajahan) === $option ? 'selected' : ''); ?>><?php echo e($option); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <small class="text-muted d-block mt-1">
                                        Jika dikerjakan subject matter, sertakan rancangan perwajahan dan deskripsi warna/gambar pada dokumen pendukung.
                                    </small>
                                </div>
                            </div>

                            <div class="sprp-line full">
                                <span class="sprp-no">3.</span>
                                <label>Judul Publikasi</label>
                                <div class="sprp-field readonly-field"><?php echo e($publication->nama_publikasi); ?></div>
                            </div>

                            <div class="sprp-line">
                                <span class="sprp-no">4.</span>
                                <label>Apakah Publikasi Baru</label>
                                <div class="sprp-field">
                                    <select name="publikasi_baru" class="form-select form-select-sm" required <?php echo e(!$canEditAuthorDocuments ? 'disabled' : ''); ?>>
                                        <option value="">-- Pilih --</option>
                                        <option value="1" <?php echo e($sprpOldBool('publikasi_baru') === '1' ? 'selected' : ''); ?>>Ya</option>
                                        <option value="0" <?php echo e($sprpOldBool('publikasi_baru') === '0' ? 'selected' : ''); ?>>Tidak</option>
                                    </select>
                                </div>
                            </div>

                            <div class="sprp-line">
                                <span class="sprp-no">5.</span>
                                <label>Ukuran</label>
                                <div class="sprp-field">
                                    <input type="text"
                                           name="ukuran"
                                           class="form-control form-control-sm"
                                           list="ukuranOptionsList"
                                           value="<?php echo e($currentUkuran); ?>"
                                           placeholder="Pilih atau ketik ukuran publikasi"
                                           required
                                           <?php echo e(!$canEditAuthorDocuments ? 'readonly' : ''); ?>>
                                    <datalist id="ukuranOptionsList">
                                        <?php $__currentLoopData = $ukuranOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($option); ?>"></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </datalist>
                                </div>
                            </div>

                            <div class="sprp-line">
                                <span class="sprp-no">6.</span>
                                <label>Bentuk Publikasi (Orientasi)</label>
                                <div class="sprp-field">
                                    <select name="orientasi" class="form-select form-select-sm" required <?php echo e(!$canEditAuthorDocuments ? 'disabled' : ''); ?>>
                                        <option value="">-- Pilih --</option>
                                        <?php $__currentLoopData = ['Portrait', 'Landscape']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($option); ?>" <?php echo e(old('orientasi', optional($sprp)->orientasi) === $option ? 'selected' : ''); ?>><?php echo e($option); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            </div>

                            <div class="sprp-line">
                                <span class="sprp-no">7.</span>
                                <label>Frekuensi Terbit</label>
                                <div class="sprp-field readonly-field"><?php echo e($publication->periode ?? '-'); ?></div>
                            </div>

                            <div class="sprp-line">
                                <span class="sprp-no">8.</span>
                                <label>Terbitan yang ke</label>
                                <div class="sprp-field">
                                    <input type="text" name="terbitan_ke" class="form-control form-control-sm" value="<?php echo e(old('terbitan_ke', optional($sprp)->terbitan_ke)); ?>" placeholder="Contoh: 1" required <?php echo e(!$canEditAuthorDocuments ? 'readonly' : ''); ?>>
                                </div>
                            </div>

                            <div class="sprp-line">
                                <span class="sprp-no">9.</span>
                                <label>Tahun Pertama Kali Terbit</label>
                                <div class="sprp-field">
                                    <select name="tahun_pertama_terbit" class="form-select form-select-sm" required <?php echo e(!$canEditAuthorDocuments ? 'disabled' : ''); ?>>
                                        <option value="">-- Pilih Tahun --</option>
                                        <?php $__currentLoopData = $yearOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($year); ?>" <?php echo e((string) old('tahun_pertama_terbit', optional($sprp)->tahun_pertama_terbit) === (string) $year ? 'selected' : ''); ?>><?php echo e($year); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            </div>

                            <div class="sprp-line">
                                <span class="sprp-no">10.</span>
                                <label>Diterbitkan Untuk</label>
                                <div class="sprp-field">
                                    <select name="diterbitkan_untuk" class="form-select form-select-sm" required <?php echo e(!$canEditAuthorDocuments ? 'disabled' : ''); ?>>
                                        <option value="">-- Pilih --</option>
                                        <?php $__currentLoopData = ['Eksternal', 'Internal']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($option); ?>" <?php echo e(old('diterbitkan_untuk', optional($sprp)->diterbitkan_untuk) === $option ? 'selected' : ''); ?>><?php echo e($option); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            </div>

                            <div class="sprp-line full">
                                <span class="sprp-no">11.</span>
                                <label>Publikasi dan Tanggal ARC/Non-ARC</label>
                                <div class="sprp-field readonly-field">
                                    <?php echo e($publication->kategori); ?>, <?php echo e($publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('j F Y') : '-'); ?>

                                </div>
                            </div>
                        </div>

                        <div class="sprp-summary-table mt-3">
                            <div class="sprp-summary-label">Keterangan publikasi yang akan dicetak</div>
                            <div class="sprp-summary-cell">
                                <label>Romawi</label>
                                <input type="text" name="jumlah_halaman_romawi" class="form-control form-control-sm" value="<?php echo e(old('jumlah_halaman_romawi', optional($sprp)->jumlah_halaman_romawi)); ?>" inputmode="text" pattern="[A-Za-z]+" data-alpha-only required <?php echo e(!$canEditAuthorDocuments ? 'readonly' : ''); ?>>
                            </div>
                            <div class="sprp-summary-cell">
                                <label>Arab</label>
                                <input type="text" name="jumlah_halaman_arab" class="form-control form-control-sm" value="<?php echo e(old('jumlah_halaman_arab', optional($sprp)->jumlah_halaman_arab)); ?>" inputmode="numeric" pattern="[0-9]+" data-digit-only required <?php echo e(!$canEditAuthorDocuments ? 'readonly' : ''); ?>>
                            </div>
                            <div class="sprp-summary-cell wide">
                                <label>Kerja Sama dengan Instansi di Luar BPS</label>
                                <select name="kerja_sama_luar_bps" class="form-select form-select-sm" required <?php echo e(!$canEditAuthorDocuments ? 'disabled' : ''); ?>>
                                    <option value="">-- Pilih --</option>
                                    <option value="1" <?php echo e($sprpOldBool('kerja_sama_luar_bps') === '1' ? 'selected' : ''); ?>>Ya</option>
                                    <option value="0" <?php echo e($sprpOldBool('kerja_sama_luar_bps') === '0' ? 'selected' : ''); ?>>Tidak</option>
                                </select>
                            </div>
                            <div class="sprp-summary-cell wide">
                                <label>Bahasa</label>
                                <div class="d-flex gap-3 flex-wrap pt-1">
                                    <?php $__currentLoopData = ['Indonesia', 'Inggris']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <label class="form-check-label small">
                                            <input type="checkbox" name="bahasa[]" value="<?php echo e($language); ?>" class="form-check-input me-1" <?php echo e(in_array($language, $sprpBahasa ?? [], true) ? 'checked' : ''); ?> <?php echo e(!$canEditAuthorDocuments ? 'disabled' : ''); ?>>
                                            <?php echo e($language); ?>

                                        </label>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        </div>

                        <?php if($canEditAuthorDocuments): ?>
                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Simpan SPRP
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>

            <?php if($canOpenKontenForm): ?>
                <?php echo $__env->make('employee.tasks._review_workspace', [
                    'type' => 'konten',
                    'slides' => $contentGuidelineSlides,
                    'savedSlides' => $contentSavedSlides,
                    'isKetua' => $isKetuaKonten,
                    'section' => 'summary',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php elseif($canReviewKonten): ?>
                <div class="alert alert-warning">
                    <strong>Pemeriksaan konten belum aktif.</strong><br>
                    Pemeriksa konten harus menunggu Tim Penyusun menekan tombol Submit.
                </div>
            <?php endif; ?>

            <?php if($canOpenLayoutForm): ?>
                <?php echo $__env->make('employee.tasks._review_workspace', [
                    'type' => 'layout',
                    'slides' => $layoutGuidelineSlides,
                    'savedSlides' => $layoutSavedSlides,
                    'isKetua' => $isKetuaLayout,
                    'section' => 'summary',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php elseif($canReviewLayout): ?>
                <div class="alert alert-warning">
                    <strong>Pemeriksaan layout belum aktif.</strong><br>
                    Pemeriksa layout harus menunggu konten disetujui oleh Ketua Pemeriksa Konten.
                </div>
            <?php endif; ?>

            <?php if($canOpenInfographicForm): ?>
                <?php echo $__env->make('employee.tasks._infographic_workspace', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php elseif($isOperatorInfografis): ?>
                <?php if(!$isProvinsiTenant && !$hasInfographicReviewDocuments): ?>
                    <div class="alert alert-info">
                        <strong>Pemeriksaan infografis tidak diperlukan.</strong><br>
                        Dokumen infografis/daftar tabel-gambar tidak tersedia untuk publikasi kabupaten/kota ini.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <strong>Pemeriksaan infografis belum aktif.</strong><br>
                        Operator infografis harus menunggu pemeriksaan layout disetujui.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if($isOperatorWebsite): ?>
                <?php echo $__env->make('employee.tasks._review_history_card', ['historyCardClass' => 'mb-4'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>

            <?php if($canOpenWebsiteForm): ?>
                <?php echo $__env->make('employee.tasks._website_workspace', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php elseif($isOperatorWebsite): ?>
                <div class="alert alert-info">
                    <strong>Finalisasi rilis belum aktif.</strong><br>
                    Operator website harus menunggu persetujuan pimpinan sebelum mengunggah Surat Persetujuan Rilis.
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <?php if($isPenyusun || $isOperatorWebsite): ?>
                <div class="card table-card mb-4">
                    <div class="card-header bg-white border-0">
                        <h6 class="mb-0 fw-bold">Dokumen Publikasi</h6>
                    </div>

                    <div class="card-body employee-document-slider-list">
                        <?php
                            $documentTypeOrder = ['naskah_pdf', 'naskah_zip', 'infografis', 'daftar_tabel_gambar', 'surat_persetujuan_rilis'];
                            $documentGroups = $publicationTeam->documents
                                ->whereIn('document_type', $documentTypeOrder)
                                ->sortByDesc('version')
                                ->groupBy('document_type')
                                ->sortBy(function ($items, $type) use ($documentTypeOrder) {
                                    $index = array_search($type, $documentTypeOrder, true);

                                    return $index === false ? 999 : $index;
                                });
                        ?>

                        <?php $__empty_1 = true; $__currentLoopData = $documentGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $documentType => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $items = $items->sortByDesc('version')->values();
                                $latestDocument = $items->first();
                                $totalVersions = $items->count();
                            ?>

                            <div class="employee-document-version-card" data-version-slider>
                                <div class="employee-document-version-head">
                                    <div>
                                        <strong><?php echo e(optional($latestDocument)->document_type_label); ?></strong>
                                        <small><?php echo e($totalVersions); ?> versi dokumen</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary-subtle text-primary">Ada</span>
                                        <?php if($totalVersions > 1): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-light text-primary border" data-version-counter>1/<?php echo e($totalVersions); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="version-slider-shell employee-version-slider-shell <?php echo e($totalVersions <= 1 ? 'single-version' : ''); ?>">
                                    <?php if($totalVersions > 1): ?>
                                        <button type="button" class="version-nav-btn" data-version-prev aria-label="Versi sebelumnya">
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                    <?php endif; ?>

                                    <div class="version-slide-stage">
                                        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="version-slide <?php echo e($loop->first ? 'is-active' : ''); ?>" data-version-slide>
                                                <div class="employee-document-version-body">
                                                    <div class="employee-document-file-info">
                                                        <span>Versi <?php echo e($document->version); ?></span>
                                                        <strong title="<?php echo e($document->file_original_name); ?>"><?php echo e($document->file_original_name); ?></strong>
                                                        <small>
                                                            Oleh <?php echo e(optional($document->uploader)->name ?? '-'); ?><br>
                                                            <?php echo e(optional($document->uploaded_at)->format('d-m-Y H:i')); ?>

                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="employee-document-version-actions">
                                                    <a href="<?php echo e(route('employee.tasks.download-document', $document->id)); ?>" class="btn btn-outline-primary btn-sm" target="<?php echo e($document->is_link ? '_blank' : '_self'); ?>">
                                                        <i class="bi <?php echo e($document->is_link ? 'bi-box-arrow-up-right' : 'bi-download'); ?> me-1"></i> <?php echo e($document->is_link ? 'Buka Link' : 'Download'); ?>

                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>

                                    <?php if($totalVersions > 1): ?>
                                        <button type="button" class="version-nav-btn" data-version-next aria-label="Versi berikutnya">
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <small class="text-muted">Belum ada dokumen penyusun.</small>
                        <?php endif; ?>

                        <?php echo $__env->make('shared._sprp_document_box', [
                            'sprp' => $sprp,
                            'modalId' => 'employeeSprpModal',
                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (! ($isOperatorWebsite)): ?>
                <?php echo $__env->make('employee.tasks._review_history_card', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if($canOpenKontenForm): ?>
        <?php echo $__env->make('employee.tasks._review_workspace', [
            'type' => 'konten',
            'slides' => $contentGuidelineSlides,
            'savedSlides' => $contentSavedSlides,
            'isKetua' => $isKetuaKonten,
            'section' => 'details',
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>

    <?php if($canOpenLayoutForm): ?>
        <?php echo $__env->make('employee.tasks._review_workspace', [
            'type' => 'layout',
            'slides' => $layoutGuidelineSlides,
            'savedSlides' => $layoutSavedSlides,
            'isKetua' => $isKetuaLayout,
            'section' => 'details',
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>

    <?php echo $__env->make('shared._sprp_view_modal', [
        'sprp' => $sprp,
        'modalId' => 'employeeSprpModal',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="modal fade" id="activeTeamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold">Tim Aktif Saat Ini</h5>
                        <small class="text-muted"><?php echo e($publicationTeam->name ?? '-'); ?></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <?php $__currentLoopData = $teamRoleLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $teamMembers = $publicationTeam->assignments->where('assignment_role', $role);
                            ?>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="fw-semibold mb-2"><?php echo e($label); ?></div>
                                    <?php if($teamMembers->count() > 0): ?>
                                        <ul class="mb-0 ps-3">
                                            <?php $__currentLoopData = $teamMembers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <li><?php echo e(optional($member->user)->name ?? '-'); ?></li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </ul>
                                    <?php else: ?>
                                        <small class="text-muted">Belum diatur</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>


<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const defaultDocumentLinkPlaceholder = 'atau tempel link dokumen eksternal';

        function updateDocumentLinkPlaceholder(box, fileNames) {
            const linkInput = box ? box.querySelector('.author-link-inline-input') : null;

            if (!linkInput) {
                return;
            }

            if (fileNames.length > 0) {
                const selectedName = fileNames.join(', ');
                linkInput.value = '';
                linkInput.placeholder = selectedName;
                linkInput.title = selectedName;
                linkInput.classList.add('has-selected-file-placeholder');
                linkInput.classList.remove('is-invalid');
            } else {
                linkInput.placeholder = defaultDocumentLinkPlaceholder;
                linkInput.removeAttribute('title');
                linkInput.classList.remove('has-selected-file-placeholder');
            }
        }

        document.querySelectorAll('.author-file-native').forEach(function (input) {
            input.addEventListener('change', function () {
                const box = input.closest('.author-file-link-box');
                const linkInput = box ? box.querySelector('.author-link-inline-input') : null;
                const fileNames = Array.from(input.files || []).map(function (file) {
                    return file.name;
                });

                updateDocumentLinkPlaceholder(box, fileNames);
            });
        });

        document.querySelectorAll('.author-link-inline-input').forEach(function (input) {
            input.addEventListener('input', function () {
                const box = input.closest('.author-file-link-box');
                const fileInput = box ? box.querySelector('.author-file-native') : null;

                if (fileInput && input.value.trim() !== '') {
                    fileInput.value = '';
                    updateDocumentLinkPlaceholder(box, []);
                }

                input.classList.toggle('is-invalid', input.value.trim() !== '' && !input.checkValidity());
            });
        });

        document.querySelectorAll('[data-digit-only]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = input.value.replace(/[^0-9]/g, '');
            });
        });

        document.querySelectorAll('[data-alpha-only]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = input.value.replace(/[^A-Za-z]/g, '');
            });
        });

        document.querySelectorAll('.infographic-input').forEach(function (input) {
            input.addEventListener('change', function () {
                const preview = input.closest('form').querySelector('.selected-preview');

                if (!preview) {
                    return;
                }

                preview.innerHTML = '';

                Array.from(input.files || []).forEach(function (file) {
                    if (!file.type.startsWith('image/')) {
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (event) {
                        const img = document.createElement('img');
                        img.src = event.target.result;
                        img.className = 'infographic-thumb';
                        preview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            });
        });


        document.querySelectorAll('[data-review-workspace]').forEach(function (workspace) {
            const tabs = Array.from(workspace.querySelectorAll('[data-review-target]'));
            const slides = Array.from(workspace.querySelectorAll('[data-review-slide]'));

            function activate(targetId) {
                tabs.forEach(function (tab) {
                    tab.classList.toggle('active', tab.dataset.reviewTarget === targetId);
                });

                slides.forEach(function (slide) {
                    slide.classList.toggle('active', slide.id === targetId);
                });
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    activate(tab.dataset.reviewTarget);
                });
            });

            workspace.querySelectorAll('.review-next-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const current = workspace.querySelector('.review-slide.active');
                    const index = slides.indexOf(current);
                    const next = slides[Math.min(index + 1, slides.length - 1)];
                    if (next) activate(next.id);
                });
            });

            workspace.querySelectorAll('.review-prev-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const current = workspace.querySelector('.review-slide.active');
                    const index = slides.indexOf(current);
                    const previous = slides[Math.max(index - 1, 0)];
                    if (previous) activate(previous.id);
                });
            });
        });

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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.employee', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/employee/tasks/show.blade.php ENDPATH**/ ?>