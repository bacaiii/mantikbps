@extends('layouts.tenant')

@section('title', 'Assign Tim Publikasi')

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card form-card">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0 fw-bold">Assign Tim Publikasi</h5>
                    <small class="text-muted">Ubah nama tim, anggota, dan tugas pada publikasi yang sudah dialokasikan.</small>
                </div>

                <div class="card-body">
                    <div class="alert alert-light border">
                        <div class="row g-2">
                            <div class="col-md-12">
                                <strong>Nama Tim:</strong><br>
                                {{ $team->name }}
                            </div>
                            <div class="col-md-12">
                                <strong>Judul Publikasi:</strong><br>
                                {{ optional($team->publication)->nama_publikasi }}
                            </div>
                            <div class="col-md-4">
                                <strong>Wilayah:</strong><br>
                                {{ optional(optional($team->publication)->tenant)->wilayah ?? '-' }}
                            </div>
                            <div class="col-md-4">
                                <strong>Kategori:</strong><br>
                                <span class="badge {{ optional($team->publication)->kategori === 'ARC' ? 'bg-primary' : 'bg-secondary' }} compact-badge">
                                    {{ optional($team->publication)->kategori ?? '-' }}
                                </span>
                            </div>
                            <div class="col-md-4">
                                <strong>Status Tim:</strong><br>
                                @if($team->hasCompleteAssignments())
                                    <span class="status-chip success">Lengkap</span>
                                @else
                                    <span class="status-chip warning">Belum Lengkap</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($pegawai->count() === 0)
                        <div class="alert alert-warning">
                            Belum ada akun <strong>Pegawai</strong>. Buat akun pegawai terlebih dahulu pada menu <strong>Kelola Akun Pengguna</strong>.
                        </div>
                    @endif

                    <form id="assignTeamForm" action="{{ route('tenant.team-allocations.assign.update', $team->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Tim</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $team->name) }}" placeholder="Tim Kerja “Nama Publikasi”" required>
                        </div>

                        <datalist id="assignmentEmployeeList">
                            @foreach($pegawai as $user)
                                <option value="{{ $user->name }}"></option>
                            @endforeach
                        </datalist>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle table-clean" id="assignmentTable">
                                <thead>
                                    <tr>
                                        <th style="width: 45%;">Pegawai</th>
                                        <th style="width: 45%;">Tugas</th>
                                        <th style="width: 10%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(old('assignments', $assignmentRows) as $i => $row)
                                        @php
                                            $selectedUserId = (string) ($row['user_id'] ?? '');
                                            $selectedUser = $pegawai->first(fn ($user) => (string) $user->id === $selectedUserId);
                                        @endphp
                                        <tr>
                                            <td>
                                                <input type="hidden" name="assignments[{{ $i }}][user_id]" class="employee-id-input" value="{{ $selectedUserId }}">
                                                <input type="text"
                                                       class="form-control employee-name-input"
                                                       list="assignmentEmployeeList"
                                                       value="{{ optional($selectedUser)->name }}"
                                                       placeholder="Ketik nama pegawai..."
                                                       autocomplete="off"
                                                       required>
                                                </td>
                                            <td>
                                                <select name="assignments[{{ $i }}][assignment_role]" class="form-select assignment-role-select" required>
                                                    <option value="">-- Pilih Tugas --</option>
                                                    @foreach($assignmentRoles as $role => $label)
                                                        <option value="{{ $role }}" {{ ($row['assignment_role'] ?? '') === $role ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm table-action-btn" onclick="removeAssignmentRow(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm mt-3" onclick="addAssignmentRow()">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Baris
                        </button>

                        <div class="mt-3">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan untuk penugasan anggota tim">{{ old('notes') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('tenant.team-allocations.index') }}" class="btn btn-light border">Kembali</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Simpan Tim Kerja
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card table-card mb-4">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0 fw-bold">Tim Aktif Saat Ini</h6>
                </div>
                <div class="card-body">
                    @foreach($assignmentRoles as $role => $label)
                        @php
                            $items = $team->assignments->where('assignment_role', $role);
                        @endphp
                        <div class="mb-3">
                            <div class="fw-semibold">{{ $label }}</div>
                            @if($items->count() > 0)
                                <ul class="mb-0 ps-3">
                                    @foreach($items as $item)
                                        <li>{{ optional($item->user)->name }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <small class="text-muted">Belum diatur</small>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card table-card">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold">Riwayat Perubahan Tim Kerja</h6>
                    </div>
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-clock-history me-1"></i> Assign
                    </span>
                </div>

                <div class="card-body">
                    @php
                        $assignmentHistories = $team->publication->teamAssignmentHistories
                            ->filter(fn ($history) => str_contains(strtolower($history->action ?? ''), 'assign tim kerja'))
                            ->sortByDesc('created_at')
                            ->values();

                        $roleLabels = [
                            'penyusun_naskah' => 'Penyusun Naskah',
                            'ketua_pemeriksa_konten' => 'Ketua Pemeriksa Konten',
                            'anggota_pemeriksa_konten' => 'Anggota Pemeriksa Konten',
                            'ketua_pemeriksa_layout' => 'Ketua Pemeriksa Layout',
                            'anggota_pemeriksa_layout' => 'Anggota Pemeriksa Layout',
                            'operator_infografis' => 'Operator Infografis',
                            'operator_website' => 'Operator Website',
                        ];
                    @endphp

                    @if($assignmentHistories->count() > 0)
                        <div data-assignment-history-slider>
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                <span class="badge bg-light text-primary border" data-assignment-history-counter>1/{{ $assignmentHistories->count() }}</span>
                                @if($assignmentHistories->count() > 1)
                                    <div class="d-flex gap-2">
                                        <button type="button" class="version-nav-btn" data-assignment-history-prev aria-label="Riwayat sebelumnya">
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                        <button type="button" class="version-nav-btn" data-assignment-history-next aria-label="Riwayat berikutnya">
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <div class="version-slide-stage">
                                @foreach($assignmentHistories as $history)
                                    @php
                                        $oldSnapshot = json_decode($history->old_value ?? '[]', true) ?: [];
                                        $newSnapshot = json_decode($history->new_value ?? '[]', true) ?: [];

                                        $changedRoles = collect($roleLabels)->filter(function ($label, $role) use ($oldSnapshot, $newSnapshot) {
                                            $oldNames = collect($oldSnapshot[$role] ?? [])->filter()->values()->implode(', ');
                                            $newNames = collect($newSnapshot[$role] ?? [])->filter()->values()->implode(', ');

                                            return $oldNames !== $newNames;
                                        });
                                    @endphp

                                    <div class="version-slide {{ $loop->first ? 'is-active' : '' }}" data-assignment-history-slide>
                                        <div class="assignment-history-item border rounded-3 p-3 bg-light">
                                            <div class="d-flex justify-content-between gap-3 mb-2">
                                                <div>
                                                    <div class="fw-bold text-dark">
                                                        <i class="bi bi-people-fill text-primary me-1"></i>
                                                        {{ $history->action }}
                                                    </div>
                                                    <small class="text-muted">
                                                        Oleh {{ optional($history->changedBy)->name ?? '-' }}
                                                    </small>
                                                </div>

                                                <small class="text-muted text-end">
                                                    {{ $history->created_at ? $history->created_at->format('d-m-Y H:i') : '-' }}
                                                </small>
                                            </div>

                                            @if($changedRoles->count() > 0)
                                                <div class="mt-2">
                                                    @foreach($changedRoles as $role => $label)
                                                        @php
                                                            $oldNames = collect($oldSnapshot[$role] ?? [])->filter()->values()->implode(', ');
                                                            $newNames = collect($newSnapshot[$role] ?? [])->filter()->values()->implode(', ');
                                                        @endphp

                                                        <div class="border-top pt-2 mt-2">
                                                            <div class="small fw-semibold text-dark mb-1">
                                                                {{ $label }}
                                                            </div>

                                                            <div class="small">
                                                                <span class="text-muted">Sebelumnya:</span>
                                                                <span class="fw-semibold">{{ $oldNames ?: '-' }}</span>
                                                            </div>

                                                            <div class="small">
                                                                <span class="text-muted">Menjadi:</span>
                                                                <span class="fw-semibold text-primary">{{ $newNames ?: '-' }}</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="small text-muted mt-2">
                                                    Tidak ada perubahan anggota yang terdeteksi.
                                                </div>
                                            @endif

                                            @if($history->notes)
                                                <div class="small mt-3 p-2 rounded border bg-white">
                                                    <span class="fw-semibold">Catatan:</span>
                                                    {{ $history->notes }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history fs-3 text-muted"></i>
                            <p class="mb-0 mt-2 text-muted">Belum ada riwayat perubahan assign tim kerja.</p>
                        </div>
                    @endif
                </div>
            </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let assignmentIndex = {{ count(old('assignments', $assignmentRows)) }};
    const pegawaiOptions = @json($pegawai->map(fn($user) => ['id' => $user->id, 'label' => $user->name])->values());
    const roleOptions = @json(collect($assignmentRoles)->map(fn($label, $role) => ['role' => $role, 'label' => $label])->values());

    function addAssignmentRow() {
        const tbody = document.querySelector('#assignmentTable tbody');
        const tr = document.createElement('tr');

        const roleHtml = roleOptions.map(item => `<option value="${item.role}">${item.label}</option>`).join('');

        tr.innerHTML = `
            <td>
                <input type="hidden" name="assignments[${assignmentIndex}][user_id]" class="employee-id-input">
                <input type="text"
                       class="form-control employee-name-input"
                       list="assignmentEmployeeList"
                       placeholder="Ketik nama pegawai..."
                       autocomplete="off"
                       required>
            </td>
            <td>
                <select name="assignments[${assignmentIndex}][assignment_role]" class="form-select assignment-role-select" required>
                    <option value="">-- Pilih Tugas --</option>
                    ${roleHtml}
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm table-action-btn" onclick="removeAssignmentRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        assignmentIndex++;
    }

    function removeAssignmentRow(button) {
        const tbody = document.querySelector('#assignmentTable tbody');

        if (tbody.querySelectorAll('tr').length <= 1) {
            alert('Minimal satu baris assignment harus ada.');
            return;
        }

        button.closest('tr').remove();
    }

    function normalizeEmployeeName(value) {
        return String(value ?? '').trim().toLowerCase();
    }

    const assignmentEmployeeByName = new Map(
        pegawaiOptions.map(item => [normalizeEmployeeName(item.label), String(item.id)])
    );

    function syncAssignmentEmployeeInput(input) {
        const hiddenInput = input.closest('td')?.querySelector('.employee-id-input');

        if (!hiddenInput) {
            return;
        }

        const selectedId = assignmentEmployeeByName.get(normalizeEmployeeName(input.value)) || '';
        hiddenInput.value = selectedId;
        input.classList.toggle('is-invalid', input.value.trim() !== '' && selectedId === '');
    }

    document.addEventListener('input', function (event) {
        if (event.target.classList.contains('employee-name-input')) {
            syncAssignmentEmployeeInput(event.target);
        }
    });

    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('employee-name-input')) {
            syncAssignmentEmployeeInput(event.target);
        }
    });

    document.querySelectorAll('.employee-name-input').forEach(syncAssignmentEmployeeInput);

    document.getElementById('assignTeamForm')?.addEventListener('submit', function (e) {
        let invalidEmployee = false;

        this.querySelectorAll('.employee-name-input').forEach(input => {
            syncAssignmentEmployeeInput(input);
            const hiddenInput = input.closest('td')?.querySelector('.employee-id-input');

            if (!hiddenInput?.value) {
                invalidEmployee = true;
            }
        });

        if (invalidEmployee) {
            e.preventDefault();
            alert('Pilih nama pegawai dari daftar saran yang muncul.');
            return;
        }

        const roleSelects = document.querySelectorAll('.assignment-role-select');

        let penyusunNaskah = 0;
        let pemeriksaKonten = 0;
        let pemeriksaLayout = 0;
        let operatorWebsite = 0;
        let operatorInfografis = 0;

        roleSelects.forEach(select => {
            const role = select.value;

            if (role === 'penyusun_naskah') {
                penyusunNaskah++;
            }

            if (role === 'ketua_pemeriksa_konten' || role === 'anggota_pemeriksa_konten') {
                pemeriksaKonten++;
            }

            if (role === 'ketua_pemeriksa_layout' || role === 'anggota_pemeriksa_layout') {
                pemeriksaLayout++;
            }

            if (role === 'operator_website') {
                operatorWebsite++;
            }

            if (role === 'operator_infografis') {
                operatorInfografis++;
            }
        });

        if (penyusunNaskah > 6) {
            e.preventDefault();
            alert('Maksimal Tim penyusun 6 orang');
            return;
        }

        if (pemeriksaKonten > 3) {
            e.preventDefault();
            alert('Pemeriksa Konten maks 3 Orang');
            return;
        }

        if (pemeriksaLayout > 3) {
            e.preventDefault();
            alert('Pemeriksa Layout maks 3 Orang');
            return;
        }

        if (operatorWebsite > 1) {
            e.preventDefault();
            alert('Operator website maks 1 orang');
            return;
        }

        if (operatorInfografis > 1) {
            e.preventDefault();
            alert('Operator Infografis maks 1 orang');
            return;
        }
    });

    document.querySelectorAll('[data-assignment-history-slider]').forEach(function (slider) {
        const slides = Array.from(slider.querySelectorAll('[data-assignment-history-slide]'));
        const counter = slider.querySelector('[data-assignment-history-counter]');
        const prev = slider.querySelector('[data-assignment-history-prev]');
        const next = slider.querySelector('[data-assignment-history-next]');
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

</script>
@endpush
