@extends('layouts.tenant')

@section('title', 'Atur Tim Kerja')

@section('content')
    <div class="card table-card">
        <div class="card-header bg-white border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Atur Tim Kerja</h5>
                    <small class="text-muted">Kelola template tim tetap beserta anggota dan tugas defaultnya.</small>
                </div>
                <a href="{{ route('tenant.team-templates.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Buat Template Tim
                </a>
            </div>

            <form method="GET" class="row g-2 mt-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Cari nama tim kerja...">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="alert alert-info mb-3" style="background: rgba(13, 202, 240, 0.18); border: 1px solid rgba(13, 202, 240, 0.45);">
                <strong>Keterangan fitur:</strong>
                <div class="mt-2"><i class="bi bi-plus-circle text-primary me-1"></i><strong>Buat Template Tim</strong> digunakan untuk membuat tim tetap beserta anggota dan tugas default.</div>
                <div class="mt-1"><i class="bi bi-person-check text-success me-1"></i><strong>Assign Tim</strong> digunakan untuk membuat/mengedit nama tim, anggota, dan tugas default.</div>
                <div class="mt-1"><i class="bi bi-trash text-danger me-1"></i><strong>Delete</strong> digunakan untuk menghapus template tim kerja.</div>
                <div class="mt-1"><i class="bi bi-diagram-3 text-primary me-1"></i><strong>Alokasi Tim</strong> dilakukan di menu Tim Kerja Publikasi untuk mengaitkan template tim ke judul publikasi.</div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle table-clean team-template-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>Nama Tim</th>
                            <th style="width: 260px;">Ringkasan Anggota</th>
                            <th style="width: 130px;">Status Tim</th>
                            <th style="width: 110px;">Aktif</th>
                            <th style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $index => $template)
                            <tr>
                                <td>{{ $templates->firstItem() + $index }}</td>
                                <td class="name-cell">
                                    <div class="fw-bold">{{ $template->name }}</div>
                                    @if($template->notes)
                                        <small class="text-muted d-block mt-1">{{ \Illuminate\Support\Str::limit($template->notes, 95) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $template->members_count }} anggota</div>
                                    <div class="small text-muted mt-1">
                                        {{ $template->members->pluck('user.name')->filter()->take(3)->implode(', ') ?: '-' }}
                                        @if($template->members_count > 3)
                                            dan {{ $template->members_count - 3 }} lainnya
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($template->hasCompleteAssignments())
                                        <span class="status-chip success">Lengkap</span>
                                    @else
                                        <span class="status-chip warning">Belum Lengkap</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($template->is_active)
                                        <span class="status-chip success">Aktif</span>
                                    @else
                                        <span class="status-chip secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="template-action-buttons">
                                        <a href="{{ route('tenant.team-templates.edit', $template->id) }}" class="btn btn-success btn-sm" title="Assign Tim">
                                            <i class="bi bi-person-check me-1"></i> Assign Tim
                                        </a>
                                        <form action="{{ route('tenant.team-templates.destroy', $template->id) }}" method="POST" onsubmit="return confirm('Hapus template tim kerja ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm table-action-btn" title="Hapus Tim">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada template tim kerja.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-end">
                {{ $templates->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
