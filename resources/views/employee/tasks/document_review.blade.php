@php
    $gDriveId = null;
    $gDriveUrl = null;
    $isGDriveLink = false;

    if ($pdfDocument && $pdfDocument->is_link) {
        $link = $pdfDocument->external_url; // the URL is stored in external_url
        if (preg_match('/(?:https?:\/\/)?(?:drive\.google\.com\/(?:file\/d\/|open\?id=)|docs\.google\.com\/.*?id=)([-\w]{25,})/', $link, $matches)) {
            $isGDriveLink = true;
            $gDriveId = $matches[1];
            $gDriveUrl = "https://drive.google.com/file/d/{$gDriveId}/preview";
        }
    }
@endphp
@extends('layouts.employee')

@section('title', 'Review Dokumen')

@push('styles')
<style>
    /* ─── Layout ─── */
    .review-container {
        display: flex;
        gap: 20px;
        height: calc(100vh - 140px);
        min-height: 500px;
    }

    .pdf-panel {
        flex: 1 1 60%;
        display: flex;
        flex-direction: column;
        background: #1e293b;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(15, 23, 42, 0.12);
    }

    .notes-panel {
        flex: 0 0 380px;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    /* ─── PDF Toolbar ─── */
    .pdf-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        background: #0f172a;
        gap: 8px;
        flex-wrap: wrap;
    }

    .pdf-toolbar .page-controls {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pdf-toolbar .page-controls .btn {
        width: 34px;
        height: 34px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.15);
        background: rgba(255,255,255,0.06);
        color: #e2e8f0;
        font-size: 14px;
        transition: all 0.15s;
    }

    .pdf-toolbar .page-controls .btn:hover {
        background: rgba(255,255,255,0.14);
        color: #fff;
    }

    .pdf-toolbar .page-input {
        width: 52px;
        height: 34px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.18);
        border-radius: 8px;
        color: #fff;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
    }

    .pdf-toolbar .page-input:focus {
        outline: none;
        border-color: #60a5fa;
        background: rgba(255,255,255,0.12);
    }

    .pdf-toolbar .page-total {
        color: #94a3b8;
        font-size: 13px;
        font-weight: 500;
    }

    .pdf-toolbar .zoom-controls {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pdf-toolbar .zoom-label {
        color: #94a3b8;
        font-size: 12px;
        font-weight: 600;
        min-width: 40px;
        text-align: center;
    }

    .pdf-toolbar .file-name {
        color: #64748b;
        font-size: 11px;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ─── PDF Canvas ─── */
    .pdf-viewport {
        flex: 1;
        overflow: auto;
        display: flex;
        justify-content: center;
        padding: 16px;
        background: #334155;
    }

    .pdf-viewport canvas {
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        background: #fff;
    }

    .pdf-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #94a3b8;
        gap: 12px;
    }

    .pdf-loading .spinner-border {
        width: 2.5rem;
        height: 2.5rem;
    }

    .pdf-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #94a3b8;
        gap: 12px;
        text-align: center;
        padding: 24px;
    }

    .pdf-empty i {
        font-size: 3rem;
        opacity: 0.4;
    }

    /* ─── Notes Panel ─── */
    .notes-header {
        padding: 16px 18px 12px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .notes-header h6 {
        font-weight: 700;
        font-size: 15px;
        margin-bottom: 2px;
        color: #0f172a;
    }

    .notes-header small {
        color: #64748b;
        font-size: 12px;
    }

    .notes-stats {
        display: flex;
        gap: 6px;
        margin-top: 10px;
        flex-wrap: wrap;
    }

    .notes-stats .stat-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
    }

    .notes-stats .stat-badge.danger {
        background: rgba(239, 68, 68, 0.12);
        color: #dc2626;
    }

    .notes-stats .stat-badge.warning {
        background: rgba(245, 158, 11, 0.12);
        color: #d97706;
    }

    .notes-stats .stat-badge.success {
        background: rgba(34, 197, 94, 0.12);
        color: #16a34a;
    }

    .notes-filter-bar {
        padding: 8px 18px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }

    .notes-filter-bar .filter-btn {
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s;
    }

    .notes-filter-bar .filter-btn:hover {
        background: #f1f5f9;
    }

    .notes-filter-bar .filter-btn.active {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
    }

    .notes-list {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
    }

    .notes-list::-webkit-scrollbar {
        width: 5px;
    }

    .notes-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    /* ─── Note Card ─── */
    .note-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .note-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 2px 12px rgba(59, 130, 246, 0.12);
        transform: translateY(-1px);
    }

    .note-card.active {
        border-color: #3b82f6;
        background: #eff6ff;
        box-shadow: 0 2px 12px rgba(59, 130, 246, 0.15);
    }

    .note-card .note-page-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        background: #0f172a;
        color: #fff;
    }

    .note-card .note-section {
        font-weight: 700;
        font-size: 13px;
        color: #1e293b;
        margin-top: 6px;
        line-height: 1.3;
    }

    .note-card .note-content {
        font-size: 12.5px;
        color: #475569;
        margin-top: 4px;
        line-height: 1.45;
    }

    .note-card .note-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 8px;
        gap: 6px;
        flex-wrap: wrap;
    }

    .note-card .note-meta-left {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .note-card .note-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 7px;
        border-radius: 5px;
        font-size: 10.5px;
        font-weight: 700;
    }

    .note-type-badge.revisi {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }

    .note-type-badge.saran {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }

    .note-type-badge.catatan {
        background: rgba(59, 130, 246, 0.1);
        color: #2563eb;
    }

    .note-card .note-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 7px;
        border-radius: 5px;
        font-size: 10.5px;
        font-weight: 700;
    }

    .note-status-badge.belum_diperbaiki {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }

    .note-status-badge.sudah_diperbaiki {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }

    .note-status-badge.diverifikasi {
        background: rgba(34, 197, 94, 0.1);
        color: #16a34a;
    }

    .note-card .note-reviewer {
        font-size: 10.5px;
        color: #94a3b8;
    }

    .note-card .note-time {
        font-size: 10px;
        color: #94a3b8;
    }

    .note-card .note-actions {
        display: flex;
        gap: 4px;
        margin-top: 8px;
    }

    .note-card .note-actions .btn {
        font-size: 10.5px;
        padding: 2px 8px;
        border-radius: 6px;
    }

    /* ─── Add Note Button & Form ─── */
    .notes-footer {
        padding: 12px 18px;
        border-top: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .btn-add-note {
        width: 100%;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    /* ─── Note Form Modal ─── */
    .note-form-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.35);
        backdrop-filter: blur(2px);
        z-index: 1050;
        align-items: center;
        justify-content: center;
    }

    .note-form-overlay.show {
        display: flex;
    }

    .note-form-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        width: 460px;
        max-width: 95vw;
        max-height: 90vh;
        overflow-y: auto;
        animation: slideUp 0.25s ease;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .note-form-card .form-header {
        padding: 18px 22px 12px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .note-form-card .form-header h6 {
        font-weight: 700;
        margin: 0;
        font-size: 15px;
    }

    .note-form-card .form-body {
        padding: 18px 22px;
    }

    .note-form-card .form-body .form-label {
        font-weight: 600;
        font-size: 12.5px;
        color: #374151;
        margin-bottom: 4px;
    }

    .note-form-card .form-body .form-control,
    .note-form-card .form-body .form-select {
        border-radius: 10px;
        font-size: 13px;
        border: 1.5px solid #d1d5db;
    }

    .note-form-card .form-body .form-control:focus,
    .note-form-card .form-body .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .note-form-card .form-footer {
        padding: 12px 22px 18px;
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    .note-form-card .form-footer .btn {
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 20px;
    }

    /* ─── Status Update Dropdown ─── */
    .status-dropdown {
        position: relative;
        display: inline-block;
    }

    .status-dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        bottom: 100%;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        z-index: 100;
        min-width: 180px;
        padding: 4px;
        margin-bottom: 4px;
    }

    .status-dropdown-menu.show {
        display: block;
        animation: slideUp 0.15s ease;
    }

    .status-dropdown-menu button {
        display: flex;
        align-items: center;
        gap: 6px;
        width: 100%;
        padding: 8px 12px;
        border: none;
        background: none;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.15s;
    }

    .status-dropdown-menu button:hover {
        background: #f1f5f9;
    }

    /* ─── Empty State ─── */
    .notes-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        text-align: center;
        color: #94a3b8;
        height: 100%;
    }

    .notes-empty i {
        font-size: 2.5rem;
        opacity: 0.35;
        margin-bottom: 8px;
    }

    .notes-empty p {
        font-size: 13px;
        margin: 0;
    }

    /* ─── Back Header ─── */
    .review-back-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        gap: 12px;
        flex-wrap: wrap;
    }

    .review-back-header .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        transition: color 0.15s;
    }

    .review-back-header .back-link:hover {
        color: #0f172a;
    }

    .review-back-header .pub-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
    }

    .review-back-header .pub-status {
        font-size: 11.5px;
    }

    /* ─── Responsive ─── */
    @media (max-width: 992px) {
        .review-container {
            flex-direction: column;
            height: auto;
        }

        .pdf-panel {
            height: 55vh;
            min-height: 400px;
        }

        .notes-panel {
            flex: none;
            height: 50vh;
            min-height: 350px;
        }
    }
</style>
@endpush

@section('content')
    <div class="review-back-header">
        <div>
            <a href="{{ route('employee.tasks.show', $publicationTeam->id) }}" class="back-link">
                <i class="bi bi-arrow-left"></i> Kembali ke Detail Tugas
            </a>
            <div class="pub-title mt-1">{{ $publication->nama_publikasi }}</div>
            <small class="text-muted">{{ $publicationTeam->name ?? '-' }}</small>
        </div>
        <div>
            <span class="status-chip {{ $publication->status_css_class }}">{{ $publication->status_label }}</span>
        </div>
    </div>

    <div class="review-container">
        {{-- ══════ PDF PANEL ══════ --}}
        <div class="pdf-panel">
            @if($isGDriveLink)
                <div class="pdf-toolbar">
                    <div class="file-name" title="Dokumen Google Drive">
                        <i class="bi bi-google me-1"></i> Dokumen Google Drive
                    </div>
                    <div>
                        <a href="{{ $pdfDocument->external_url }}" target="_blank" class="btn btn-sm" style="background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); font-size: 12px;">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Google Drive
                        </a>
                    </div>
                </div>
                <div class="pdf-viewport p-0" style="background: #e2e8f0;">
                    <iframe src="{{ $gDriveUrl }}" style="width: 100%; height: 100%; border: none;" allow="autoplay"></iframe>
                </div>
            @elseif($pdfDocument && !$pdfDocument->is_link)
                <div class="pdf-toolbar">
                    <div class="page-controls">
                        <button type="button" class="btn" id="prevPage" title="Halaman Sebelumnya">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <input type="number" class="page-input" id="pageInput" min="1" value="1">
                        <span class="page-total">/ <span id="totalPages">-</span></span>
                        <button type="button" class="btn" id="nextPage" title="Halaman Berikutnya">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>

                    <div class="zoom-controls">
                        <button type="button" class="btn" id="zoomOut" title="Perkecil">
                            <i class="bi bi-dash-lg"></i>
                        </button>
                        <span class="zoom-label" id="zoomLabel">100%</span>
                        <button type="button" class="btn" id="zoomIn" title="Perbesar">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                        <button type="button" class="btn" id="zoomFit" title="Sesuaikan Lebar">
                            <i class="bi bi-arrows-angle-expand"></i>
                        </button>
                    </div>

                    <div class="file-name" title="{{ $pdfDocument->file_original_name }}">
                        <i class="bi bi-file-earmark-pdf me-1"></i>{{ $pdfDocument->file_original_name }}
                    </div>
                </div>

                <div class="pdf-viewport" id="pdfViewport">
                    <div class="pdf-loading" id="pdfLoading">
                        <div class="spinner-border text-light" role="status"></div>
                        <span>Memuat dokumen PDF...</span>
                    </div>
                    <canvas id="pdfCanvas" style="display: none;"></canvas>
                </div>
            @elseif($pdfDocument && $pdfDocument->is_link)
                <div class="pdf-empty">
                    <i class="bi bi-link-45deg"></i>
                    <div>
                        <p class="fw-semibold mb-1">Dokumen Menggunakan Tautan Eksternal</p>
                        <p>Tautan tidak menggunakan Google Drive atau format tidak dikenali untuk fitur preview. Silakan buka tautan secara manual.</p>
                        <a href="{{ $pdfDocument->external_url }}" target="_blank" class="btn btn-outline-primary mt-2">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Tautan
                        </a>
                    </div>
                </div>
            @else
                <div class="pdf-empty">
                    <i class="bi bi-file-earmark-x"></i>
                    <div>
                        <p class="fw-semibold mb-1">Belum Ada Naskah PDF</p>
                        <p>Tim penyusun belum mengunggah naskah PDF untuk publikasi ini.</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- ══════ NOTES PANEL ══════ --}}
        <div class="notes-panel">
            <div class="notes-header">
                <h6><i class="bi bi-pencil-square me-1"></i> Catatan Pemeriksaan</h6>
                <small>Klik catatan untuk navigasi ke halaman terkait</small>

                <div class="notes-stats">
                    <span class="stat-badge danger">
                        <i class="bi bi-circle-fill" style="font-size:6px"></i>
                        {{ $noteStats['belum_diperbaiki'] }} Belum
                    </span>
                    <span class="stat-badge warning">
                        <i class="bi bi-circle-fill" style="font-size:6px"></i>
                        {{ $noteStats['sudah_diperbaiki'] }} Diperbaiki
                    </span>
                    <span class="stat-badge success">
                        <i class="bi bi-circle-fill" style="font-size:6px"></i>
                        {{ $noteStats['diverifikasi'] }} Diverifikasi
                    </span>
                </div>
            </div>

            {{-- Filter bar --}}
            <div class="notes-filter-bar">
                <button type="button" class="filter-btn active" data-filter="all">Semua ({{ $noteStats['total'] }})</button>
                <button type="button" class="filter-btn" data-filter="belum_diperbaiki">Belum ({{ $noteStats['belum_diperbaiki'] }})</button>
                <button type="button" class="filter-btn" data-filter="sudah_diperbaiki">Diperbaiki ({{ $noteStats['sudah_diperbaiki'] }})</button>
                <button type="button" class="filter-btn" data-filter="diverifikasi">Diverifikasi ({{ $noteStats['diverifikasi'] }})</button>
            </div>

            {{-- Notes list --}}
            <div class="notes-list" id="notesList">
                @forelse($reviewNotes as $note)
                    <div class="note-card" data-page="{{ $note->page_number }}" data-status="{{ $note->status }}">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div>
                                <span class="note-page-badge">
                                    <i class="bi bi-file-earmark"></i> Hal. {{ $note->page_number }}
                                </span>
                            </div>
                            <span class="note-status-badge {{ $note->status }}">
                                @if($note->status === 'belum_diperbaiki')
                                    <i class="bi bi-circle-fill" style="font-size:5px"></i>
                                @elseif($note->status === 'sudah_diperbaiki')
                                    <i class="bi bi-check" style="font-size:10px"></i>
                                @else
                                    <i class="bi bi-check-all" style="font-size:10px"></i>
                                @endif
                                {{ $note->status_label }}
                            </span>
                        </div>

                        <div class="note-section">{{ $note->section_name }}</div>
                        <div class="note-content">{{ $note->note }}</div>

                        <div class="note-meta">
                            <div class="note-meta-left">
                                <span class="note-type-badge {{ $note->note_type }}">
                                    <i class="bi {{ $note->note_type_icon }}" style="font-size:9px"></i>
                                    {{ $note->note_type_label }}
                                </span>
                                <span class="note-reviewer">
                                    <i class="bi bi-person-fill"></i> {{ $note->reviewer->name ?? '-' }}
                                </span>
                            </div>
                            <span class="note-time">{{ $note->created_at->diffForHumans() }}</span>
                        </div>

                        {{-- Action buttons --}}
                        <div class="note-actions" onclick="event.stopPropagation()">
                            @if($canMarkFixed && $note->status === 'belum_diperbaiki')
                                <form action="{{ route('employee.tasks.document-review.update-note', $note->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="sudah_diperbaiki">
                                    <button type="submit" class="btn btn-outline-warning btn-sm" title="Tandai sudah diperbaiki">
                                        <i class="bi bi-check me-1"></i>Sudah Diperbaiki
                                    </button>
                                </form>
                            @endif

                            @if($canVerify && $note->status === 'sudah_diperbaiki')
                                <form action="{{ route('employee.tasks.document-review.update-note', $note->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="diverifikasi">
                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Verifikasi perbaikan">
                                        <i class="bi bi-check-all me-1"></i>Verifikasi
                                    </button>
                                </form>
                            @endif

                            @if($canVerify && $note->status === 'diverifikasi')
                                <form action="{{ route('employee.tasks.document-review.update-note', $note->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="belum_diperbaiki">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Batal verifikasi">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Batal Verifikasi
                                    </button>
                                </form>
                            @endif

                            @if((int) $note->reviewer_id === (int) auth()->id())
                                <form action="{{ route('employee.tasks.document-review.destroy-note', $note->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus catatan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus catatan">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="notes-empty">
                        <i class="bi bi-chat-left-dots"></i>
                        <p>Belum ada catatan pemeriksaan.<br>
                        @if($canAddNote)
                            Klik tombol di bawah untuk menambahkan.
                        @else
                            Pemeriksa belum memberikan catatan.
                        @endif
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Add Note Footer --}}
            @if($canAddNote)
                <div class="notes-footer">
                    <button type="button" class="btn btn-primary btn-add-note" id="btnOpenNoteForm">
                        <i class="bi bi-plus-lg"></i> Tambah Catatan
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════ ADD NOTE FORM OVERLAY ══════ --}}
    @if($canAddNote)
    <div class="note-form-overlay" id="noteFormOverlay">
        <div class="note-form-card">
            <div class="form-header">
                <h6><i class="bi bi-pencil-square me-1"></i> Tambah Catatan Revisi</h6>
                <button type="button" class="btn-close" id="btnCloseNoteForm"></button>
            </div>

            <form action="{{ route('employee.tasks.document-review.store-note', $publicationTeam->id) }}" method="POST">
                @csrf
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Halaman PDF <span class="text-danger">*</span></label>
                            <input type="number" name="page_number" class="form-control" id="formPageNumber"
                                   min="1" value="{{ old('page_number', 1) }}" required>
                            @error('page_number')
                                <div class="text-danger mt-1" style="font-size:11px">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6">
                            <label class="form-label">Jenis <span class="text-danger">*</span></label>
                            <select name="note_type" class="form-select" required>
                                <option value="revisi" {{ old('note_type') === 'revisi' ? 'selected' : '' }}>Revisi</option>
                                <option value="saran" {{ old('note_type') === 'saran' ? 'selected' : '' }}>Saran</option>
                                <option value="catatan" {{ old('note_type') === 'catatan' ? 'selected' : '' }}>Catatan</option>
                            </select>
                            @error('note_type')
                                <div class="text-danger mt-1" style="font-size:11px">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Bagian / Judul <span class="text-danger">*</span></label>
                            <input type="text" name="section_name" class="form-control"
                                   placeholder="Misal: Tabel 3.2, Pendahuluan, Grafik, dll."
                                   value="{{ old('section_name') }}" required>
                            @error('section_name')
                                <div class="text-danger mt-1" style="font-size:11px">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Isi Catatan <span class="text-danger">*</span></label>
                            <textarea name="note" class="form-control" rows="4"
                                      placeholder="Tuliskan catatan revisi atau saran perbaikan..."
                                      required>{{ old('note') }}</textarea>
                            @error('note')
                                <div class="text-danger mt-1" style="font-size:11px">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-footer">
                    <button type="button" class="btn btn-light" id="btnCancelNoteForm">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
{{-- PDF.js from CDN --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── PDF.js Setup ──
    @if($pdfDocument && !$pdfDocument->is_link)
    const pdfUrl = @json(route('employee.tasks.preview-pdf', $pdfDocument->id));

    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    let pdfDoc = null;
    let currentPage = 1;
    let totalPages = 0;
    let scale = 1.0;
    let rendering = false;
    let pendingPage = null;

    const canvas = document.getElementById('pdfCanvas');
    const ctx = canvas.getContext('2d');
    const viewport = document.getElementById('pdfViewport');
    const loadingEl = document.getElementById('pdfLoading');
    const pageInput = document.getElementById('pageInput');
    const totalPagesEl = document.getElementById('totalPages');
    const zoomLabel = document.getElementById('zoomLabel');

    function renderPage(num) {
        if (rendering) {
            pendingPage = num;
            return;
        }
        rendering = true;

        pdfDoc.getPage(num).then(function (page) {
            const pdfViewport = page.getViewport({ scale: scale });
            const outputScale = window.devicePixelRatio || 1;

            canvas.width = Math.floor(pdfViewport.width * outputScale);
            canvas.height = Math.floor(pdfViewport.height * outputScale);
            canvas.style.width = Math.floor(pdfViewport.width) + "px";
            canvas.style.height = Math.floor(pdfViewport.height) + "px";
            canvas.style.display = 'block';

            const transform = outputScale !== 1
                ? [outputScale, 0, 0, outputScale, 0, 0]
                : null;

            const renderContext = {
                canvasContext: ctx,
                transform: transform,
                viewport: pdfViewport
            };

            page.render(renderContext).promise.then(function () {
                rendering = false;
                if (pendingPage !== null) {
                    const p = pendingPage;
                    pendingPage = null;
                    renderPage(p);
                }
            });
        });

        pageInput.value = num;
        currentPage = num;

        // Highlight active note
        document.querySelectorAll('.note-card').forEach(c => c.classList.remove('active'));
    }

    function goToPage(num) {
        if (num < 1 || num > totalPages || !pdfDoc) return;
        renderPage(num);
    }

    function fitWidth() {
        if (!pdfDoc) return;
        pdfDoc.getPage(currentPage).then(function (page) {
            const containerWidth = viewport.clientWidth - 32;
            const defaultViewport = page.getViewport({ scale: 1.0 });
            scale = containerWidth / defaultViewport.width;
            zoomLabel.textContent = Math.round(scale * 100) + '%';
            renderPage(currentPage);
        });
    }

    // Load PDF
    pdfjsLib.getDocument(pdfUrl).promise.then(function (pdf) {
        pdfDoc = pdf;
        totalPages = pdf.numPages;
        totalPagesEl.textContent = totalPages;
        loadingEl.style.display = 'none';

        // Initial fit-width render
        fitWidth();
    }).catch(function (err) {
        loadingEl.innerHTML = '<i class="bi bi-exclamation-triangle" style="font-size:2rem;color:#ef4444"></i>' +
            '<span style="color:#ef4444">Gagal memuat PDF. Pastikan file PDF valid.</span>';
        console.error('PDF load error:', err);
    });

    // Page navigation
    document.getElementById('prevPage').addEventListener('click', () => goToPage(currentPage - 1));
    document.getElementById('nextPage').addEventListener('click', () => goToPage(currentPage + 1));
    pageInput.addEventListener('change', () => goToPage(parseInt(pageInput.value) || 1));
    pageInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            goToPage(parseInt(pageInput.value) || 1);
        }
    });

    // Zoom controls
    document.getElementById('zoomIn').addEventListener('click', () => {
        scale = Math.min(scale + 0.2, 3.0);
        zoomLabel.textContent = Math.round(scale * 100) + '%';
        renderPage(currentPage);
    });

    document.getElementById('zoomOut').addEventListener('click', () => {
        scale = Math.max(scale - 0.2, 0.3);
        zoomLabel.textContent = Math.round(scale * 100) + '%';
        renderPage(currentPage);
    });

    document.getElementById('zoomFit').addEventListener('click', fitWidth);

    // ── Note cards: click to navigate ──
    window.goToPage = goToPage;
    @endif

    document.querySelectorAll('.note-card').forEach(card => {
        card.addEventListener('click', function () {
            const page = parseInt(this.dataset.page);
            if (page && typeof goToPage === 'function') {
                goToPage(page);

                // Highlight this card
                document.querySelectorAll('.note-card').forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                // Scroll the card into view in the panel
                this.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });

    // ── Filter buttons ──
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filter = this.dataset.filter;
            document.querySelectorAll('.note-card').forEach(card => {
                if (filter === 'all' || card.dataset.status === filter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // ── Note form overlay ──
    const formOverlay = document.getElementById('noteFormOverlay');
    const btnOpen = document.getElementById('btnOpenNoteForm');
    const btnClose = document.getElementById('btnCloseNoteForm');
    const btnCancel = document.getElementById('btnCancelNoteForm');
    const formPageInput = document.getElementById('formPageNumber');

    if (btnOpen) {
        btnOpen.addEventListener('click', () => {
            // Auto-fill current page
            if (typeof currentPage !== 'undefined' && formPageInput) {
                formPageInput.value = currentPage;
            }
            formOverlay.classList.add('show');
        });
    }

    if (btnClose) btnClose.addEventListener('click', () => formOverlay.classList.remove('show'));
    if (btnCancel) btnCancel.addEventListener('click', () => formOverlay.classList.remove('show'));

    if (formOverlay) {
        formOverlay.addEventListener('click', function (e) {
            if (e.target === formOverlay) formOverlay.classList.remove('show');
        });
    }

    // ── Show form if there are validation errors ──
    @if($errors->any())
        if (formOverlay) formOverlay.classList.add('show');
    @endif
});
</script>
@endpush
