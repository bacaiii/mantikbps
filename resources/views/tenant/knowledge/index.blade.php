@extends('layouts.tenant')

@section('title', 'Knowledge')

@section('content')
    <div class="knowledge-page">
        <div class="knowledge-hero mb-4">
            <div>
                <span class="knowledge-eyebrow"><i class="bi bi-link-45deg me-1"></i> Pusat Knowledge</span>
                <h4 class="fw-bold mb-1">Knowledge Pemeriksaan dan Penyusunan</h4>
                <p class="mb-0 text-muted">Kelola link edukasi seperti YouTube, Google Drive, dokumen pembelajaran, dan template pendukung publikasi.</p>
            </div>
            <a href="{{ route('tenant.knowledge.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Tambah Link
            </a>
        </div>

        <div class="alert alert-info mb-3" style="background: rgba(13, 202, 240, 0.18); border: 1px solid rgba(13, 202, 240, 0.45);">
            <strong>Keterangan fitur:</strong>
            <div class="mt-2">
                <i class="bi bi-plus-circle text-primary me-1"></i>
                <strong>Tambah Link</strong> digunakan untuk menambahkan tautan knowledge seperti pedoman, referensi, template, atau materi pendukung publikasi.
            </div>
            <div class="mt-1">
                <i class="bi bi-pencil-square text-warning me-1"></i>
                <strong>Edit</strong> digunakan untuk mengubah judul, deskripsi, kategori, URL, atau status link knowledge.
            </div>
            <div class="mt-1">
                <i class="bi bi-trash text-danger me-1"></i>
                <strong>Delete</strong> digunakan untuk menghapus link knowledge yang sudah tidak diperlukan.
            </div>
        </div>

        <div class="knowledge-card-grid">
            @forelse($knowledgeLinks as $knowledgeLink)
                @php
                    $category = $knowledgeLink->category ?: 'Umum';
                    $icon = str_contains(strtolower($category), 'video') || str_contains(strtolower($knowledgeLink->url), 'youtube') ? 'bi-play-btn'
                        : (str_contains(strtolower($category), 'dokumen') || str_contains(strtolower($knowledgeLink->url), 'drive') ? 'bi-file-earmark-text'
                        : (str_contains(strtolower($category), 'template') ? 'bi-layout-text-window-reverse'
                        : 'bi-link-45deg'));
                @endphp

                <div class="knowledge-card {{ !$knowledgeLink->is_active ? 'is-muted' : '' }}">
                    <div class="knowledge-card-top">
                        <span class="knowledge-icon"><i class="bi {{ $icon }}"></i></span>
                        <div class="knowledge-badges">
                            <span class="knowledge-category">{{ $category }}</span>
                            <span class="badge {{ $knowledgeLink->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                {{ $knowledgeLink->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    </div>

                    <div class="knowledge-card-body">
                        <h5>{{ $knowledgeLink->title }}</h5>
                        <p>{{ $knowledgeLink->description ?: 'Belum ada deskripsi untuk materi ini.' }}</p>
                    </div>

                    <div class="knowledge-link-preview">
                        <i class="bi bi-globe2"></i>
                        <span>{{ \Illuminate\Support\Str::limit($knowledgeLink->url, 58) }}</span>
                    </div>

                    <div class="knowledge-card-actions">
                        <a href="{{ $knowledgeLink->url }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Materi
                        </a>
                        <div class="d-flex gap-2">
                            <a href="{{ route('tenant.knowledge.edit', $knowledgeLink->id) }}" class="btn btn-warning btn-sm table-action-btn" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('tenant.knowledge.destroy', $knowledgeLink->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus link knowledge ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm table-action-btn" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="knowledge-empty">
                    <i class="bi bi-inbox"></i>
                    <strong>Belum ada link knowledge</strong>
                    <span>Tambahkan link edukasi agar pegawai memiliki pusat referensi yang rapi.</span>
                </div>
            @endforelse
        </div>

        <div class="mt-4 d-flex justify-content-end">
            {{ $knowledgeLinks->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
