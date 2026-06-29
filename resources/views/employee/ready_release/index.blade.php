@extends('layouts.employee')

@section('title', 'Publikasi Selesai Dikelola')

@section('content')
    <div class="card table-card mb-4">
        <div class="card-header bg-white border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-0 fw-bold">Publikasi Selesai Dikelola</h5>
                    <small class="text-muted">Publikasi siap rilis yang pernah Anda kelola sebagai bagian dari tim kerja.</small>
                </div>
            </div>

            <form method="GET" class="row g-2 align-items-end mt-3">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tahun</label>
                    <select name="tahun" class="form-select">
                        @foreach($yearOptions as $year)
                            <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Bulan</label>
                    <select name="bulan" class="form-select">
                        <option value="">Semua Bulan</option>
                        @foreach($monthOptions as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" {{ (string) $selectedMonth === (string) $monthNumber ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Cari Publikasi</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Ketik nama publikasi...">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Kategori</label>
                    <select name="kategori" class="form-select">
                        <option value="">Semua Kategori</option>
                        <option value="ARC" {{ request('kategori') === 'ARC' ? 'selected' : '' }}>ARC</option>
                        <option value="Non-ARC" {{ request('kategori') === 'Non-ARC' ? 'selected' : '' }}>Non-ARC</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Penugasan</label>
                    <select name="penugasan" class="form-select">
                        <option value="">Semua Penugasan</option>
                        @foreach($assignmentRoleOptions as $roleValue => $roleLabel)
                            <option value="{{ $roleValue }}" {{ (string) $selectedAssignment === (string) $roleValue ? 'selected' : '' }}>
                                {{ $roleLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="accordion knowledge-drawer-list" id="employeeReadyReleaseAccordion">
        @forelse($teams as $team)
            @php
                $publication = $team->publication;
                $collapseId = 'employeeReadyReleaseDrawer' . $team->id;
                $modalId = 'employeeReadyReleaseDocumentModal' . $team->id;
                $sprpModalId = 'employeeReadyReleaseSprpModal' . $team->id;
                $sprp = $team->sprp;
                $documentsByType = $team->documents
                    ->sortByDesc('version')
                    ->groupBy('document_type');

                $materialTypes = [
                    'naskah_pdf' => ['icon' => 'bi-file-earmark-pdf', 'label' => 'Naskah PDF'],
                    'naskah_zip' => ['icon' => 'bi-file-earmark-zip', 'label' => 'File Naskah ZIP/RAR'],
                    'infografis' => ['icon' => 'bi-images', 'label' => 'Infografis'],
                    'daftar_tabel_gambar' => ['icon' => 'bi-file-earmark-spreadsheet', 'label' => 'Daftar Tabel & Gambar'],
                    'surat_persetujuan_rilis' => ['icon' => 'bi-file-earmark-check', 'label' => 'Surat Persetujuan Rilis'],
                ];

                $latestDocuments = collect($materialTypes)->mapWithKeys(function ($meta, $type) use ($documentsByType) {
                    return [$type => ($documentsByType->get($type) ?? collect())->sortByDesc('version')->first()];
                });

                $myAssignmentsForCurrentUser = $team->assignments->where('user_id', auth()->id());
                $myRoles = $myAssignmentsForCurrentUser
                    ->pluck('assignment_role_label')
                    ->implode(', ');
                $isOperatorWebsite = $myAssignmentsForCurrentUser
                    ->where('assignment_role', 'operator_website')
                    ->isNotEmpty();
            @endphp

            <div class="accordion-item border-0 bg-transparent mb-3">
                <h2 class="accordion-header" id="heading{{ $collapseId }}">
                    <button class="accordion-button collapsed shadow-sm rounded-4 py-3 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary me-3" style="width: 38px; height: 38px; flex: 0 0 38px;">
                            <i class="bi bi-box-seam"></i>
                        </span>
                        <span class="flex-grow-1">
                            <span class="d-block fw-bold text-dark">{{ $publication->nama_publikasi }}</span>
                            <small class="text-muted">
                                {{ $publication->kategori }} • {{ $publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('j F Y') : '-' }} • {{ $myRoles ?: '-' }}
                            </small>
                        </span>
                    </button>
                </h2>

                <div id="{{ $collapseId }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $collapseId }}" data-bs-parent="#employeeReadyReleaseAccordion">
                    <div class="accordion-body bg-white border rounded-4 mt-2 shadow-sm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <strong>Estimasi Nomor Publikasi</strong><br>
                                <span class="text-muted">{{ $publication->estimasi_nomor_publikasi ?: '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Kategori</strong><br>
                                <span class="badge {{ $publication->kategori === 'ARC' ? 'bg-primary' : 'bg-secondary' }}">{{ $publication->kategori }}</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Periode</strong><br>
                                <span class="text-muted">{{ $publication->periode ?? '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Akurasi Publikasi</strong><br>
                                <span class="text-muted">{{ $publication->akurasi_publikasi ?? '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Jadwal Rilis</strong><br>
                                <span class="text-muted">{{ $publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('j F Y') : '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Wilayah</strong><br>
                                <span class="text-muted">{{ $publication->wilayah ?? optional($publication->tenant)->wilayah ?? '-' }}</span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                <i class="bi bi-folder2-open me-1"></i> Lihat Dokumen
                            </button>
                            <a href="{{ route('employee.ready-release.work-report', $team->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                <i class="bi bi-file-earmark-text me-1"></i> Rekap Hasil
                            </a>
                            @if($isOperatorWebsite)
                                <a href="{{ route('employee.ready-release.download-package', $team->id) }}" class="btn btn-success btn-sm rounded-pill px-3 js-package-download" data-loading-title="Menyiapkan Paket Rilis" data-loading-message="Sistem sedang membungkus dokumen final dan mengambil file asli dari link eksternal yang dapat diakses.">
                                    <i class="bi bi-file-earmark-zip me-1"></i> Download Paket Rilis
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold">Dokumen Publikasi</h5>
                                <small class="text-muted">{{ $publication->nama_publikasi }}</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between align-items-center gap-3 border rounded-3 p-3 bg-light">
                                    <div class="d-flex align-items-start gap-3 min-w-0">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 34px; height: 34px; flex: 0 0 34px;">
                                            <i class="bi bi-ui-checks"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <strong>Form SPRP</strong><br>
                                            @if($sprp)
                                                <small class="text-muted d-block">Diisi {{ optional($sprp->submitted_at)->format('d-m-Y H:i') ?? '-' }}</small>
                                            @else
                                                <small class="text-muted">Belum ada data SPRP</small>
                                            @endif
                                        </div>
                                    </div>

                                    @if($sprp)
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $sprpModalId }}">
                                            <i class="bi bi-eye me-1"></i> Lihat
                                        </button>
                                    @endif
                                </div>

                                @foreach($materialTypes as $type => $meta)
                                    @php($latestDocument = $latestDocuments->get($type))
                                    <div class="d-flex justify-content-between align-items-center gap-3 border rounded-3 p-3 bg-light">
                                        <div class="d-flex align-items-start gap-3 min-w-0">
                                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 34px; height: 34px; flex: 0 0 34px;">
                                                <i class="bi {{ $meta['icon'] }}"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <strong>{{ $meta['label'] }}</strong><br>
                                                @if($latestDocument)
                                                    <small class="text-muted d-block text-truncate" title="{{ $latestDocument->file_original_name }}">
                                                        V{{ $latestDocument->version }} • {{ $latestDocument->file_original_name }}
                                                    </small>
                                                @else
                                                    <small class="text-muted">Belum ada file</small>
                                                @endif
                                            </div>
                                        </div>

                                        @if($latestDocument)
                                            <a href="{{ route('employee.tasks.download-document', $latestDocument->id) }}" class="btn btn-outline-primary btn-sm" target="{{ $latestDocument->is_link ? '_blank' : '_self' }}">
                                                <i class="bi {{ $latestDocument->is_link ? 'bi-box-arrow-up-right' : 'bi-download' }} me-1"></i> {{ $latestDocument->is_link ? 'Buka Link' : 'Download' }}
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('shared._sprp_view_modal', [
                'sprp' => $sprp,
                'modalId' => $sprpModalId,
            ])
        @empty
            <div class="text-center py-5 bg-white border rounded-4 shadow-sm">
                <i class="bi bi-inbox fs-2 text-muted"></i>
                <div class="fw-bold mt-2">Belum ada publikasi siap rilis</div>
                <div class="text-muted small">Publikasi akan tampil setelah status publikasi menjadi siap rilis.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-4 d-flex justify-content-end">
        {{ $teams->links('pagination::bootstrap-5') }}
    </div>

    @include('shared._package_download_loader')
@endsection
