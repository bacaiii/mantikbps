@extends('layouts.tenant')

@section('title', 'Kelola Pedoman Pemeriksaan')

@section('content')
    @php
        $typeLabels = [
            'konten' => 'Pemeriksaan Konten',
            'layout' => 'Pemeriksaan Layout',
            'template' => 'Template Surat',
        ];

        $typeDescriptions = [
            'konten' => 'Pedoman pemeriksaan konten digunakan oleh pemeriksa konten pada bagian Isi publikasi.',
            'layout' => 'Pedoman pemeriksaan layout digunakan oleh pemeriksa layout pada bagian depan, pendahuluan, dan penutup publikasi.',
            'template' => 'Template surat rilis dikelola admin dan akan digunakan oleh pegawai sesuai tugasnya.',
        ];

        $typeIcons = [
            'konten' => 'bi-file-earmark-text',
            'layout' => 'bi-columns-gap',
            'template' => 'bi-folder-symlink',
        ];

        $activeGrouped = $activeType === 'template'
            ? collect()
            : ($groupedGuidelines[$activeType] ?? collect());

        $renderedKeys = [];
        $availableTemplates = collect($templateTypes)->keys()->filter(fn($key) => $documentTemplates->has($key))->count();
    @endphp

    <div class="guideline-page">
        <div class="guideline-hero mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="guideline-eyebrow">
                        <i class="bi bi-journal-check me-1"></i>
                        Master Pedoman Pemeriksaan
                    </div>
                    <h4 class="fw-bold mb-1">Kelola Pedoman Pemeriksaan Publikasi</h4>
                    <p class="mb-0 text-muted">
                        Admin dapat mengelola pedoman pemeriksaan konten, pedoman pemeriksaan layout, dan template dokumen rilis.
                    </p>
                </div>

                @if($activeType !== 'template')
                    <a href="{{ route('tenant.inspection-guidelines.create', ['type' => $activeType]) }}" class="btn btn-primary guideline-add-btn">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Anatomi
                    </a>
                @endif
            </div>

            <div class="guideline-type-tabs mt-4">
                @foreach(['konten', 'layout', 'template'] as $type)
                    @php
                        $smallText = match($type) {
                            'template' => count($templateTypes) . ' template • ' . $availableTemplates . ' tersedia',
                            default => $stats[$type]['cards'] . ' card • ' . $stats[$type]['items'] . ' rincian',
                        };
                    @endphp

                    <a href="{{ route('tenant.inspection-guidelines.index', ['type' => $type]) }}"
                       class="guideline-type-tab {{ $activeType === $type ? 'active' : '' }}">
                        <span class="tab-icon"><i class="bi {{ $typeIcons[$type] }}"></i></span>
                        <span>
                            <strong>{{ $typeLabels[$type] }}</strong>
                            <small>{{ $smallText }}</small>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        @if($activeType === 'template')
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="guideline-stat-card">
                        <span class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-folder-symlink"></i></span>
                        <div>
                            <small>Jenis Kelola</small>
                            <h6>Template Surat</h6>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="guideline-stat-card">
                        <span class="stat-icon bg-success-subtle text-success"><i class="bi bi-cloud-check"></i></span>
                        <div>
                            <small>Template Tersedia</small>
                            <h6>{{ $availableTemplates }} File</h6>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="guideline-stat-card">
                        <span class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-file-earmark-arrow-up"></i></span>
                        <div>
                            <small>Total Template</small>
                            <h6>{{ count($templateTypes) }} Template</h6>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="guideline-stat-card">
                        <span class="stat-icon bg-primary-subtle text-primary"><i class="bi {{ $typeIcons[$activeType] }}"></i></span>
                        <div>
                            <small>Jenis Pedoman</small>
                            <h6>{{ $typeLabels[$activeType] }}</h6>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="guideline-stat-card">
                        <span class="stat-icon bg-success-subtle text-success"><i class="bi bi-card-checklist"></i></span>
                        <div>
                            <small>Total Card</small>
                            <h6>{{ $stats[$activeType]['cards'] }} Bagian</h6>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="guideline-stat-card">
                        <span class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-check2-square"></i></span>
                        <div>
                            <small>Rincian Aktif</small>
                            <h6>{{ $stats[$activeType]['active'] }} Item</h6>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="alert guideline-info-alert mb-4">
            <div class="d-flex gap-3">
                <i class="bi bi-info-circle-fill fs-4 text-primary"></i>
                <div>
                    <strong>{{ $typeLabels[$activeType] }}</strong><br>
                    {{ $typeDescriptions[$activeType] }}
                </div>
            </div>
        </div>

        @if($activeType === 'template')
            <div class="guideline-section-block mb-5">
                <div class="guideline-section-head">
                    <div>
                        <span class="section-label">Template Dokumen Rilis</span>
                        <h5 class="fw-bold mb-0">Template Dokumen Rilis</h5>
                    </div>
                    <span class="section-count">{{ count($templateTypes) }} template</span>
                </div>

                <div class="template-form-grid">
                    @foreach($templateTypes as $templateType => $templateInfo)
                        @php
                            $template = $documentTemplates->get($templateType);
                        @endphp

                        <div class="template-form-card">
                            <div class="template-form-head">
                                <span class="template-form-icon {{ $templateInfo['color'] ?? 'primary' }}">
                                    <i class="bi {{ $templateInfo['icon'] }}"></i>
                                </span>

                                <div>
                                    <h6>{{ $templateInfo['title'] }}</h6>
                                    <p>{{ $templateInfo['description'] }}</p>
                                </div>
                            </div>

                            <div class="template-form-body">
                                @if($template)
                                    <div class="template-current-file compact">
                                        <div>
                                            <strong>{{ $template->file_original_name }}</strong>
                                            <small>
                                                Diunggah oleh {{ optional($template->uploader)->name ?? '-' }} •
                                                {{ optional($template->updated_at)->format('d-m-Y H:i') }}
                                            </small>
                                        </div>

                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ $template->file_url }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-download me-1"></i> Download
                                            </a>

                                            <form action="{{ route('tenant.inspection-guidelines.templates.destroy', $template->id) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Yakin ingin menghapus template ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-trash me-1"></i> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <div class="template-current-file compact empty">
                                        <div>
                                            <strong>Template belum tersedia</strong>
                                            <small>Silakan unggah template agar dapat digunakan oleh pegawai.</small>
                                        </div>
                                    </div>
                                @endif

                                <form action="{{ route('tenant.inspection-guidelines.templates.store') }}"
                                      method="POST"
                                      enctype="multipart/form-data"
                                      class="template-upload-form">
                                    @csrf
                                    <input type="hidden" name="template_type" value="{{ $templateType }}">

                                    <label class="form-label fw-semibold">{{ $template ? 'Ganti Template' : 'Upload Template' }}</label>
                                    <div class="input-group">
                                        <input type="file"
                                               name="template_file"
                                               class="form-control"
                                               accept=".pdf,.doc,.docx,.xls,.xlsx"
                                               required>
                                        <button class="btn btn-primary">
                                            <i class="bi bi-cloud-arrow-up me-1"></i> Simpan
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1">Format: PDF, DOC, DOCX, XLS, XLSX. Maksimal 10MB.</small>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            @foreach($structures[$activeType] as $anatomy => $sections)
                @php
                    $carouselId = 'guidelineCarousel' . \Illuminate\Support\Str::slug($activeType . '-' . $anatomy);
                    $sectionCount = collect($sections)->sum(function ($section) use ($activeGrouped, $anatomy) {
                        return optional(optional($activeGrouped->get($anatomy))->get($section))->count() ?? 0;
                    });
                @endphp

                <div class="guideline-section-block mb-5">
                    <div class="guideline-section-head">
                        <div>
                            <span class="section-label">Anatomi Publikasi</span>
                            <h5 class="fw-bold mb-0">{{ $anatomy }}</h5>
                        </div>
                        <span class="section-count">{{ $sectionCount }} rincian</span>
                    </div>

                    <div id="{{ $carouselId }}" class="carousel slide guideline-carousel" data-bs-interval="false">
                        <div class="carousel-indicators guideline-indicators">
                            @foreach($sections as $section)
                                <button type="button"
                                        data-bs-target="#{{ $carouselId }}"
                                        data-bs-slide-to="{{ $loop->index }}"
                                        class="{{ $loop->first ? 'active' : '' }}"
                                        aria-current="{{ $loop->first ? 'true' : 'false' }}"
                                        aria-label="{{ $section }}">
                                </button>
                            @endforeach
                        </div>

                        <div class="carousel-inner">
                            @foreach($sections as $section)
                                @php
                                    $items = optional($activeGrouped->get($anatomy))->get($section) ?? collect();
                                    $renderedKeys[] = $anatomy . '|' . $section;
                                @endphp

                                <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                    <div class="guideline-slide-card">
                                        <div class="guideline-card-header">
                                            <div>
                                                <small>{{ $anatomy }}</small>
                                                <h5>{{ $section }}</h5>
                                            </div>
                                            <div class="d-flex gap-2 align-items-center">
                                                <span class="guideline-pill">{{ $items->count() }} rincian</span>
                                                <a href="{{ route('tenant.inspection-guidelines.create', ['type' => $activeType, 'anatomy_section' => $anatomy, 'inspection_item' => $section]) }}"
                                                   class="btn btn-sm btn-primary guideline-icon-btn"
                                                   title="Tambah rincian pada {{ $section }}">
                                                    <i class="bi bi-plus-lg"></i>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="guideline-card-body">
                                            @forelse($items as $guideline)
                                                <div class="guideline-detail-item {{ !$guideline->is_active ? 'is-muted' : '' }}">
                                                    <div class="detail-number">{{ $loop->iteration }}</div>
                                                    <div class="detail-content">
                                                        <div class="detail-text">{!! nl2br(e($guideline->requirement_detail)) !!}</div>

                                                        <div class="detail-meta mt-2">
                                                            @if($guideline->is_active)
                                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                                            @else
                                                                <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                                            @endif

                                                            @if(is_null($guideline->tenant_id))
                                                                <span class="badge bg-primary-subtle text-primary">Default Sistem</span>
                                                            @else
                                                                <span class="badge bg-warning-subtle text-warning">Tambahan Wilayah Kerja</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="detail-actions">
                                                        <a href="{{ route('tenant.inspection-guidelines.edit', $guideline->id) }}"
                                                           class="btn btn-warning btn-sm guideline-icon-btn"
                                                           title="Edit rincian">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <form action="{{ route('tenant.inspection-guidelines.destroy', $guideline->id) }}"
                                                              method="POST"
                                                              onsubmit="return confirm('Yakin ingin menghapus rincian pedoman ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-danger btn-sm guideline-icon-btn" title="Hapus rincian">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="guideline-empty-card">
                                                    <i class="bi bi-inbox"></i>
                                                    <strong>Belum ada rincian</strong>
                                                    <span>Tambahkan rincian pemeriksaan untuk bagian ini.</span>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if(count($sections) > 1)
                            <button class="carousel-control-prev guideline-control" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="prev">
                                <i class="bi bi-chevron-left"></i>
                                <span class="visually-hidden">Sebelumnya</span>
                            </button>
                            <button class="carousel-control-next guideline-control" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="next">
                                <i class="bi bi-chevron-right"></i>
                                <span class="visually-hidden">Berikutnya</span>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach

            @php
                $customCards = collect();
                foreach($activeGrouped as $anatomy => $sectionGroups) {
                    foreach($sectionGroups as $section => $items) {
                        if(!in_array($anatomy . '|' . $section, $renderedKeys, true)) {
                            $customCards->push([
                                'anatomy' => $anatomy,
                                'section' => $section,
                                'items' => $items,
                            ]);
                        }
                    }
                }
            @endphp

            @if($customCards->isNotEmpty())
                <div class="guideline-section-block mb-4">
                    <div class="guideline-section-head">
                        <div>
                            <span class="section-label">Tambahan Admin</span>
                            <h5 class="fw-bold mb-0">Anatomi Tambahan</h5>
                        </div>
                        <span class="section-count">{{ $customCards->count() }} card</span>
                    </div>

                    <div class="row g-3">
                        @foreach($customCards as $card)
                            <div class="col-lg-6">
                                <div class="guideline-slide-card h-100">
                                    <div class="guideline-card-header custom-guideline-header">
                                        <div>
                                            <small>{{ $card['anatomy'] }}</small>
                                            <h5>{{ $card['section'] }}</h5>
                                        </div>
                                        <div class="custom-guideline-actions">
                                            <span class="guideline-pill">{{ $card['items']->count() }} rincian</span>
                                            <a href="{{ route('tenant.inspection-guidelines.create', ['type' => $activeType, 'anatomy_section' => $card['anatomy'], 'inspection_item' => $card['section']]) }}"
                                               class="btn btn-sm btn-primary guideline-icon-btn"
                                               title="Tambah rincian pada sub-anatomi ini">
                                                <i class="bi bi-plus-lg"></i>
                                            </a>
                                            <form action="{{ route('tenant.inspection-guidelines.custom-section.destroy') }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Yakin ingin menghapus sub-anatomi tambahan ini beserta seluruh rinciannya?')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="type" value="{{ $activeType }}">
                                                <input type="hidden" name="anatomy_section" value="{{ $card['anatomy'] }}">
                                                <input type="hidden" name="inspection_item" value="{{ $card['section'] }}">
                                                <button class="btn btn-sm btn-danger guideline-icon-btn" title="Hapus sub-anatomi tambahan">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="guideline-card-body small-card-body">
                                        @foreach($card['items'] as $guideline)
                                            <div class="guideline-detail-item">
                                                <div class="detail-number">{{ $loop->iteration }}</div>
                                                <div class="detail-content">
                                                    <div class="detail-text">{!! nl2br(e($guideline->requirement_detail)) !!}</div>
                                                </div>
                                                <div class="detail-actions">
                                                    <a href="{{ route('tenant.inspection-guidelines.edit', $guideline->id) }}" class="btn btn-warning btn-sm guideline-icon-btn">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <form action="{{ route('tenant.inspection-guidelines.destroy', $guideline->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus rincian pedoman ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm guideline-icon-btn">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection
