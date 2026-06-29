@extends('layouts.employee')

@section('title', 'Tugas Saya')

@section('content')
    @php
        $roleLabel = function ($role) {
            return match ($role) {
                'penyusun_naskah' => 'Penyusun Naskah',
                'ketua_pemeriksa_konten' => 'Ketua Pemeriksa Konten',
                'anggota_pemeriksa_konten' => 'Anggota Pemeriksa Konten',
                'ketua_pemeriksa_layout' => 'Ketua Pemeriksa Layout',
                'anggota_pemeriksa_layout' => 'Anggota Pemeriksa Layout',
                'operator_website' => 'Operator Website',
                'operator_infografis' => 'Operator Infografis',
                default => '-',
            };
        };

        $currentSort = request('sort_by');
        $currentDirection = request('sort_dir', 'desc');
        $makeSortUrl = function ($column) use ($currentSort, $currentDirection) {
            $newDir = ($currentSort === $column && $currentDirection === 'asc') ? 'desc' : 'asc';

            return route('employee.tasks.index', array_merge(request()->query(), [
                'sort_by' => $column,
                'sort_dir' => $newDir,
                'page' => 1,
            ]));
        };
        $sortIcon = function ($column) use ($currentSort, $currentDirection) {
            if ($currentSort !== $column) {
                return '';
            }

            return $currentDirection === 'asc'
                ? '<i class="bi bi-caret-up-fill sort-icon"></i>'
                : '<i class="bi bi-caret-down-fill sort-icon"></i>';
        };

        $sortThClass = fn ($column) => $currentSort === $column ? 'sort-active' : '';
        $sortLinkClass = fn ($column) => $currentSort === $column ? 'sort-link active' : 'sort-link';
        $formatScheduleDate = fn ($date) => $date ? $date->format('d/m/y') : '-';
        $remainingDays = fn ($date) => $date ? max(0, now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false)) : 0;
        $formatReleaseDateStack = function ($date) use ($remainingDays) {
            if (!$date) {
                return '<span class="text-muted">-</span>';
            }

            return '<div class="date-stack employee-release-date-stack">'
                . '<span class="date-main">' . e($date->translatedFormat('j F')) . '</span>'
                . '<span class="date-year">' . e($date->translatedFormat('Y')) . '</span>'
                . '<span class="remaining-days-chip">Sisa ' . e($remainingDays($date)) . ' hari</span>'
                . '</div>';
        };
        $scheduleChipStyle = 'display:inline-flex;align-items:center;justify-content:center;padding:3px 7px;border-radius:999px;background:rgba(100,116,139,0.10);border:1px solid rgba(100,116,139,0.22);color:#475569;font-size:9.5px;font-weight:700;line-height:1.15;white-space:normal;text-align:center;';
        $shortTeamName = function ($name) {
            $name = trim((string) ($name ?: '-'));

            return mb_strlen($name) > 26 ? mb_substr($name, 0, 26) . '...' : $name;
        };

        $selectedTaskRoleMap = [
            'penyusun' => ['penyusun_naskah'],
            'konten' => ['ketua_pemeriksa_konten', 'anggota_pemeriksa_konten'],
            'layout' => ['ketua_pemeriksa_layout', 'anggota_pemeriksa_layout'],
            'infografis' => ['operator_infografis'],
            'website' => ['operator_website'],
        ];

        $visibleAssignmentsForTeam = function ($team) use ($selectedTask, $selectedTaskRoleMap) {
            $assignments = $team->assignments->where('user_id', auth()->id())->values();

            if ($selectedTask && isset($selectedTaskRoleMap[$selectedTask])) {
                return $assignments
                    ->filter(fn ($assignment) => in_array($assignment->assignment_role, $selectedTaskRoleMap[$selectedTask], true))
                    ->values();
            }

            return $assignments;
        };

        $hasAnyRole = fn ($roles, $targets) => $roles->intersect($targets)->isNotEmpty();
        $isProvinsiPublication = fn ($team) => optional(optional($team->publication)->tenant)->type === 'provinsi';
        $hasInfographicReviewDocuments = fn ($team) => $team->documents
            ->whereIn('document_type', ['infografis', 'daftar_tabel_gambar'])
            ->isNotEmpty();
        $buttonStyle = [
            'redWait' => 'background: rgba(220, 53, 69, 0.5); border-color: rgba(220, 53, 69, 0.5); box-shadow: 0 0 12px rgba(220, 53, 69, 0.35); color: #fff;',
            'orangeDark' => 'background: #9a4d00; border-color: #9a4d00; color: #fff;',
        ];

        $revisionButtonLabel = function ($stage) {
            return match ($stage) {
                'pemeriksaan_layout' => 'Revisi Layout',
                'pemeriksaan_infografis' => 'Revisi Infografis',
                'persetujuan_pimpinan' => 'Revisi Pimpinan',
                default => 'Revisi Konten',
            };
        };

        $actionForTask = function ($team, $rawRoles) use ($currentUnlockedAuthorTeamId, $buttonStyle, $hasAnyRole, $revisionButtonLabel, $isProvinsiPublication, $hasInfographicReviewDocuments) {
            $publication = $team->publication;
            $status = optional($publication)->status;
            $revisionStage = optional($publication)->revision_return_stage;
            $hasSubmitted = !empty(optional($publication)->draft_submitted_at);

            $isAuthor = $rawRoles->contains('penyusun_naskah');
            $isContentReviewer = $hasAnyRole($rawRoles, ['ketua_pemeriksa_konten', 'anggota_pemeriksa_konten']);
            $isLayoutReviewer = $hasAnyRole($rawRoles, ['ketua_pemeriksa_layout', 'anggota_pemeriksa_layout']);
            $isInfographic = $rawRoles->contains('operator_infografis');
            $isWebsite = $rawRoles->contains('operator_website');

            $result = [
                'label' => 'Buka Tugas',
                'class' => 'btn btn-primary btn-sm',
                'style' => '',
                'disabled' => false,
                'note' => null,
            ];

            if ($isAuthor) {
                $isLockedAuthorTask = $status === 'penyusunan'
                    && !$hasSubmitted
                    && !empty($currentUnlockedAuthorTeamId)
                    && (int) $currentUnlockedAuthorTeamId !== (int) $team->id;

                if ($isLockedAuthorTask) {
                    return [
                        'label' => 'Terkunci',
                        'class' => 'btn btn-outline-secondary btn-sm',
                        'style' => '',
                        'disabled' => true,
                        'note' => 'Penyusunan sebelumnya belum selesai',
                    ];
                }

                if ($status === 'penyusunan' && !empty($revisionStage)) {
                    return [
                        'label' => $revisionButtonLabel($revisionStage),
                        'class' => 'btn btn-sm',
                        'style' => $buttonStyle['orangeDark'],
                        'disabled' => false,
                        'note' => null,
                    ];
                }

                if (in_array($status, ['pemeriksaan_konten', 'pemeriksaan_layout', 'pemeriksaan_infografis', 'persetujuan_pimpinan'], true)) {
                    return [
                        'label' => 'Menunggu',
                        'class' => 'btn btn-sm',
                        'style' => $buttonStyle['redWait'],
                        'disabled' => false,
                        'note' => null,
                    ];
                }

                if (in_array($status, ['operator_website', 'siap_rilis', 'pengajuan_rilis', 'rilis_selesai'], true)) {
                    return [
                        'label' => 'Selesai',
                        'class' => 'btn btn-success btn-sm',
                        'style' => '',
                        'disabled' => false,
                        'note' => null,
                    ];
                }

                return $result;
            }

            if ($isContentReviewer) {
                if (!$hasSubmitted) {
                    return ['label' => 'Menunggu Penyusunan', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                if ($status === 'pemeriksaan_konten') {
                    return $result;
                }

                if ($status === 'penyusunan' && $revisionStage === 'pemeriksaan_konten') {
                    return ['label' => 'Menunggu Revisi', 'class' => 'btn btn-sm', 'style' => $buttonStyle['redWait'], 'disabled' => false, 'note' => null];
                }

                if (in_array($status, ['pemeriksaan_layout', 'pemeriksaan_infografis', 'persetujuan_pimpinan', 'operator_website', 'siap_rilis', 'pengajuan_rilis', 'rilis_selesai'], true)
                    || ($status === 'penyusunan' && !empty($revisionStage) && $revisionStage !== 'pemeriksaan_konten')) {
                    return ['label' => 'Selesai', 'class' => 'btn btn-success btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                return ['label' => 'Menunggu Penyusunan', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
            }

            if ($isLayoutReviewer) {
                if (!$hasSubmitted) {
                    return ['label' => 'Menunggu Penyusunan', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                if ($status === 'pemeriksaan_konten') {
                    return ['label' => 'Menunggu Periksa Konten', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                if ($status === 'penyusunan' && $revisionStage === 'pemeriksaan_konten') {
                    return ['label' => 'Menunggu Penyusunan', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                if ($status === 'pemeriksaan_layout') {
                    return $result;
                }

                if ($status === 'penyusunan' && $revisionStage === 'pemeriksaan_layout') {
                    return ['label' => 'Menunggu Revisi', 'class' => 'btn btn-sm', 'style' => $buttonStyle['redWait'], 'disabled' => false, 'note' => null];
                }

                if (in_array($status, ['pemeriksaan_infografis', 'persetujuan_pimpinan', 'operator_website', 'siap_rilis', 'pengajuan_rilis', 'rilis_selesai'], true)
                    || ($status === 'penyusunan' && !empty($revisionStage) && !in_array($revisionStage, ['pemeriksaan_konten', 'pemeriksaan_layout'], true))) {
                    return ['label' => 'Selesai', 'class' => 'btn btn-success btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                return ['label' => 'Menunggu Penyusunan', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
            }

            if ($isInfographic) {
                $isKabKotaWithoutInfographicDocuments = !$isProvinsiPublication($team) && !$hasInfographicReviewDocuments($team);

                if (!$hasSubmitted || ($status === 'penyusunan' && in_array($revisionStage, [null, 'pemeriksaan_konten', 'pemeriksaan_layout'], true))) {
                    return ['label' => 'Menunggu Penyusunan', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                if ($status === 'pemeriksaan_konten') {
                    return ['label' => 'Menunggu Periksa Konten', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                if ($status === 'pemeriksaan_layout') {
                    return ['label' => 'Menunggu Periksa Layout', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                if ($isKabKotaWithoutInfographicDocuments && in_array($status, ['pemeriksaan_infografis', 'persetujuan_pimpinan', 'operator_website', 'siap_rilis', 'pengajuan_rilis', 'rilis_selesai'], true)) {
                    return ['label' => 'Selesai', 'class' => 'btn btn-success btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                if ($status === 'pemeriksaan_infografis') {
                    return $result;
                }

                if ($status === 'penyusunan' && $revisionStage === 'pemeriksaan_infografis') {
                    return ['label' => 'Menunggu Revisi', 'class' => 'btn btn-sm', 'style' => $buttonStyle['redWait'], 'disabled' => false, 'note' => null];
                }

                if (in_array($status, ['persetujuan_pimpinan', 'operator_website', 'siap_rilis', 'pengajuan_rilis', 'rilis_selesai'], true)
                    || ($status === 'penyusunan' && $revisionStage === 'persetujuan_pimpinan')) {
                    return ['label' => 'Selesai', 'class' => 'btn btn-success btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                return ['label' => 'Menunggu Penyusunan', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
            }

            if ($isWebsite) {
                if (in_array($status, ['penyusunan', 'pemeriksaan_konten', 'pemeriksaan_layout', 'pemeriksaan_infografis'], true)) {
                    return ['label' => 'Menunggu Proses', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                if ($status === 'persetujuan_pimpinan') {
                    return ['label' => 'Menunggu Persetujuan', 'class' => 'btn btn-sm', 'style' => $buttonStyle['redWait'], 'disabled' => false, 'note' => null];
                }

                if ($status === 'operator_website') {
                    return ['label' => 'Buka Finalisasi', 'class' => 'btn btn-primary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                if (in_array($status, ['siap_rilis', 'pengajuan_rilis', 'rilis_selesai'], true)) {
                    return ['label' => 'Selesai', 'class' => 'btn btn-success btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
                }

                return ['label' => 'Menunggu Proses', 'class' => 'btn btn-secondary btn-sm', 'style' => '', 'disabled' => false, 'note' => null];
            }

            return $result;
        };
    @endphp

    <div class="card table-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Daftar Tugas Saya</h5>
            <small class="text-muted">Tugas Anda akan berbeda sesuai alokasi tim kerja pada setiap publikasi.</small>
        </div>

        <div class="card-body">
            <div class="alert alert-info mb-3" style="background: rgba(13, 202, 240, 0.18); border: 1px solid rgba(13, 202, 240, 0.45);">
                <strong>Keterangan fitur:</strong>
                <div class="mt-2"><i class="bi bi-folder2-open text-primary me-1"></i><strong>Buka Tugas</strong> digunakan untuk membuka tugas yang sedang aktif dikerjakan.</div>
                <div class="mt-1"><i class="bi bi-pencil-square me-1" style="color:#9a4d00;"></i><strong>Revisi Konten/Layout/Infografis/Pimpinan</strong> muncul pada tim penyusun sesuai tahap yang meminta perbaikan.</div>
                <div class="mt-1"><i class="bi bi-hourglass-split text-secondary me-1"></i><strong>Menunggu Penyusunan/Proses</strong> muncul ketika tugas masih menunggu tahapan sebelumnya.</div>
                <div class="mt-1"><i class="bi bi-hourglass-split text-danger me-1"></i><strong>Menunggu Revisi</strong> muncul pada pemeriksa/operator yang sedang menunggu perbaikan dari penyusun.</div>
                <div class="mt-1"><i class="bi bi-check-circle text-success me-1"></i><strong>Selesai</strong> muncul ketika tugas pada tahap tersebut sudah selesai.</div>
                <div class="mt-1"><i class="bi bi-lock-fill text-danger me-1"></i><strong>Terkunci</strong> muncul jika penyusunan publikasi sebelumnya belum pernah disubmit.</div>
            </div>

            <form method="GET" class="row g-2 mb-3">
                @if(request('sort_by'))
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                @endif
                @if(request('sort_dir'))
                    <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
                @endif

                <div class="col-md-2">
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        @foreach($yearOptions as $year)
                            <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="bulan" class="form-select">
                        <option value="">Semua Bulan</option>
                        @foreach($monthOptions as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" {{ (string) $selectedMonth === (string) $monthNumber ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="task" class="form-select">
                        <option value="">Semua Penugasan</option>
                        @foreach($taskOptions as $taskValue => $taskLabel)
                            <option value="{{ $taskValue }}" {{ (string) $selectedTask === (string) $taskValue ? 'selected' : '' }}>{{ $taskLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        @foreach($statusOptions as $statusValue => $statusLabel)
                            <option value="{{ $statusValue }}" {{ (string) $selectedStatus === (string) $statusValue ? 'selected' : '' }}>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Cari nama publikasi...">
                </div>

                <div class="col-md-1">
                    <button class="btn btn-primary w-100 employee-task-search-btn" title="Filter">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <div class="table-fit-wrapper employee-task-table-wrapper">
                <table class="table table-bordered table-clean align-middle employee-task-table" style="table-layout:fixed;">
                    <colgroup>
                        <col style="width:12%;">
                        <col style="width:39%;">
                        <col style="width:112px;">
                        <col style="width:14%;">
                        <col style="width:12%;">
                        <col style="width:108px;">
                    </colgroup>
                <thead>
                    <tr>
                        <th class="{{ $sortThClass('tim') }}">
                            <a href="{{ $makeSortUrl('tim') }}" class="{{ $sortLinkClass('tim') }}">
                                <span>Tim Kerja</span>{!! $sortIcon('tim') !!}
                            </a>
                        </th>
                        <th class="{{ $sortThClass('publikasi') }}">
                            <a href="{{ $makeSortUrl('publikasi') }}" class="{{ $sortLinkClass('publikasi') }}">
                                <span>Nama Publikasi</span>{!! $sortIcon('publikasi') !!}
                            </a>
                        </th>
                        <th class="text-center {{ $sortThClass('tanggal_rilis') }}">
                            <a href="{{ $makeSortUrl('tanggal_rilis') }}" class="{{ $sortLinkClass('tanggal_rilis') }} justify-content-center">
                                <span>Tanggal Rilis</span>{!! $sortIcon('tanggal_rilis') !!}
                            </a>
                        </th>
                        <th class="{{ $sortThClass('tugas') }}">
                            <a href="{{ $makeSortUrl('tugas') }}" class="{{ $sortLinkClass('tugas') }}">
                                <span>Tugas Saya</span>{!! $sortIcon('tugas') !!}
                            </a>
                        </th>
                        <th class="text-center {{ $sortThClass('status') }}">
                            <a href="{{ $makeSortUrl('status') }}" class="{{ $sortLinkClass('status') }} justify-content-center">
                                <span>Status Publikasi</span>{!! $sortIcon('status') !!}
                            </a>
                        </th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($teams as $team)
                        @php
                            $visibleAssignments = $visibleAssignmentsForTeam($team);
                        @endphp

                        @foreach($visibleAssignments as $assignment)
                            @php
                                $rawRoles = collect([$assignment->assignment_role]);
                                $myRoles = $roleLabel($assignment->assignment_role);
                                $taskAction = $actionForTask($team, $rawRoles);
                                $teamName = trim((string) ($team->name ?? $team->nama_tim ?? '-'));
                                $isLongTeamName = mb_strlen($teamName) > 26;
                                $rowKey = $team->id . '-' . ($assignment->id ?? $assignment->assignment_role);
                                $teamToggleId = 'employee-team-name-' . $rowKey;
                            @endphp

                            <tr>
                                <td class="employee-task-team-cell">
                                    <span class="employee-task-team-preview" title="{{ $teamName }}">{{ $shortTeamName($teamName) }}</span>
                                    @if($isLongTeamName)
                                        <button type="button" class="employee-team-more-btn" data-team-target="{{ $teamToggleId }}" aria-expanded="false">
                                            Lihat Selengkapnya
                                        </button>
                                        <div id="{{ $teamToggleId }}" class="employee-task-team-full" hidden>
                                            {{ $teamName }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark" style="font-size:15px; line-height:1.3;">
                                        {{ optional($team->publication)->nama_publikasi }}
                                    </div>
                                    <div class="employee-task-schedule-chips mt-2">
                                        <span style="{{ $scheduleChipStyle }}" title="Penyusunan">Penyusunan {{ $formatScheduleDate(optional($team->publication)->jadwal_mulai_penyusunan) }}</span>
                                        <span style="{{ $scheduleChipStyle }}" title="Pemeriksaan">Pemeriksaan {{ $formatScheduleDate(optional($team->publication)->jadwal_mulai_pemeriksaan) }}</span>
                                        <span style="{{ $scheduleChipStyle }}" title="Upload">Upload {{ $formatScheduleDate(optional($team->publication)->jadwal_upload) }}</span>
                                    </div>
                                </td>
                                <td class="text-center">{!! $formatReleaseDateStack(optional($team->publication)->jadwal_rilis) !!}</td>
                                <td>{{ $myRoles }}</td>
                                <td class="text-center">
                                    <span class="status-chip {{ optional($team->publication)->status_css_class ?? 'secondary' }}">
                                        {{ optional($team->publication)->status_label }}
                                    </span>
                                </td>
                                <td class="text-center employee-task-action-cell">
                                    @if($taskAction['disabled'])
                                        <button type="button" class="{{ $taskAction['class'] }}" style="{{ $taskAction['style'] }}" disabled title="{{ $taskAction['note'] }}">
                                            <i class="bi bi-lock-fill me-1"></i> {{ $taskAction['label'] }}
                                        </button>
                                    @else
                                        <a href="{{ route('employee.tasks.show', ['publicationTeam' => $team->id, 'role' => $assignment->assignment_role]) }}" class="{{ $taskAction['class'] }}" style="{{ $taskAction['style'] }}">
                                            {{ $taskAction['label'] }}
                                        </a>
                                    @endif

                                    @if($taskAction['note'])
                                        <div class="text-danger mt-1" style="font-size:9px; line-height:1.1;">{{ $taskAction['note'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Belum ada tugas untuk Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $teams->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-team-target]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const target = document.getElementById(button.getAttribute('data-team-target'));
                    if (!target) {
                        return;
                    }

                    const willOpen = target.hasAttribute('hidden');
                    if (willOpen) {
                        target.removeAttribute('hidden');
                        button.textContent = 'Tutup';
                    } else {
                        target.setAttribute('hidden', 'hidden');
                        button.textContent = 'Lihat Selengkapnya';
                    }

                    button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });
            });
        });
    </script>
@endpush

@endsection
