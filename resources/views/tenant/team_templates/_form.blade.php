<div class="row g-4">
    <div class="col-12">
        <div class="card form-card">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0 fw-bold">{{ $formTitle }}</h5>
                <small class="text-muted">{{ $formSubtitle }}</small>
            </div>

            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <strong>Periksa kembali input Anda.</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ $action }}" method="POST" id="teamTemplateForm">
                    @csrf
                    @if($method !== 'POST')
                        @method($method)
                    @endif

                <div class="alert alert-info mb-3" style="background: rgba(13, 202, 240, 0.18); border: 1px solid rgba(13, 202, 240, 0.45);">
                    <strong>Catatan penggunaan:</strong>
                    <div class="mt-2"><i class="bi bi-check2-circle text-success me-1"></i>Tim dibuat sesuai bidang kerja yang biasa digunakan BPS.</div>
                    <div class="mt-1"><i class="bi bi-check2-circle text-success me-1"></i>Tim aktif dapat dipilih pada menu Tim Kerja Publikasi melalui tombol Alokasi Tim.</div>
                    <div class="mt-1"><i class="bi bi-check2-circle text-success me-1"></i>Setelah dialokasikan ke publikasi, anggota masih dapat disesuaikan melalui tombol Assign Tim.</div>
                    <div class="mt-1"><i class="bi bi-info-circle text-warning me-1"></i>Template boleh belum lengkap, tetapi validasi penugasan publikasi tetap mengikuti aturan sistem.</div>
                </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Tim</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" placeholder="Tim Kerja “Nama Publikasi”" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ (string) old('is_active', $template->is_active ? '1' : '0') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ (string) old('is_active', $template->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Template untuk publikasi bidang sosial, ekonomi, atau diseminasi.">{{ old('notes', $template->notes) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <div>
                            <label class="form-label fw-semibold mb-0">Anggota dan Tugas Default</label>
                            <div class="small text-muted">Pegawai dapat dicari berdasarkan nama. Pemeriksa Layout, Operator Infografis, dan Operator Website boleh diisi orang yang sama.</div>
                        </div>
                    </div>

                    <datalist id="templateEmployeeList">
                        @foreach($pegawai as $user)
                            <option value="{{ $user->name }}"></option>
                        @endforeach
                    </datalist>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle table-clean" id="templateMemberTable">
                            <thead>
                                <tr>
                                    <th style="width: 45%;">Pegawai</th>
                                    <th style="width: 45%;">Tugas Default</th>
                                    <th style="width: 10%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(old('members', $memberRows) as $i => $row)
                                    @php
                                        $selectedUserId = (string) ($row['user_id'] ?? '');
                                        $selectedUser = $pegawai->first(fn ($user) => (string) $user->id === $selectedUserId);
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="hidden" name="members[{{ $i }}][user_id]" class="employee-id-input" value="{{ $selectedUserId }}">
                                            <input type="text"
                                                   class="form-control employee-name-input"
                                                   list="templateEmployeeList"
                                                   value="{{ optional($selectedUser)->name }}"
                                                   placeholder="Ketik nama pegawai..."
                                                   autocomplete="off"
                                                   required>
                                        </td>
                                        <td>
                                            <select name="members[{{ $i }}][assignment_role]" class="form-select" required>
                                                <option value="">-- Pilih Tugas --</option>
                                                @foreach($assignmentRoles as $role => $label)
                                                    <option value="{{ $role }}" {{ ($row['assignment_role'] ?? '') === $role ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm table-action-btn" onclick="removeTemplateMemberRow(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                        <button type="button" class="btn btn-outline-primary btn-sm mt-3" onclick="addTemplateMemberRow()">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Baris
                        </button>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('tenant.team-templates.index') }}" class="btn btn-light border">Kembali</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> {{ $submitLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let templateMemberIndex = {{ count(old('members', $memberRows)) }};
    const templatePegawaiOptions = @json($pegawai->map(fn($user) => ['id' => $user->id, 'label' => $user->name])->values());
    const templateRoleOptions = @json(collect($assignmentRoles)->map(fn($label, $role) => ['role' => $role, 'label' => $label])->values());

    function addTemplateMemberRow() {
        const tbody = document.querySelector('#templateMemberTable tbody');
        const tr = document.createElement('tr');
        const roleHtml = templateRoleOptions.map(item => `<option value="${item.role}">${escapeHtml(item.label)}</option>`).join('');

        tr.innerHTML = `
            <td>
                <input type="hidden" name="members[${templateMemberIndex}][user_id]" class="employee-id-input">
                <input type="text"
                       class="form-control employee-name-input"
                       list="templateEmployeeList"
                       placeholder="Ketik nama pegawai..."
                       autocomplete="off"
                       required>
            </td>
            <td>
                <select name="members[${templateMemberIndex}][assignment_role]" class="form-select" required>
                    <option value="">-- Pilih Tugas --</option>
                    ${roleHtml}
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm table-action-btn" onclick="removeTemplateMemberRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        templateMemberIndex++;
    }

    function removeTemplateMemberRow(button) {
        const tbody = document.querySelector('#templateMemberTable tbody');
        if (tbody.querySelectorAll('tr').length <= 1) {
            alert('Minimal satu baris anggota tim harus ada.');
            return;
        }
        button.closest('tr').remove();
    }


    function normalizeEmployeeName(value) {
        return String(value ?? '').trim().toLowerCase();
    }

    const templateEmployeeByName = new Map(
        templatePegawaiOptions.map(item => [normalizeEmployeeName(item.label), String(item.id)])
    );

    function syncTemplateEmployeeInput(input) {
        const hiddenInput = input.closest('td')?.querySelector('.employee-id-input');

        if (!hiddenInput) {
            return;
        }

        const selectedId = templateEmployeeByName.get(normalizeEmployeeName(input.value)) || '';
        hiddenInput.value = selectedId;
        input.classList.toggle('is-invalid', input.value.trim() !== '' && selectedId === '');
    }

    document.addEventListener('input', function (event) {
        if (event.target.classList.contains('employee-name-input')) {
            syncTemplateEmployeeInput(event.target);
        }
    });

    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('employee-name-input')) {
            syncTemplateEmployeeInput(event.target);
        }
    });

    document.getElementById('teamTemplateForm')?.addEventListener('submit', function (event) {
        let invalid = false;

        this.querySelectorAll('.employee-name-input').forEach(input => {
            syncTemplateEmployeeInput(input);
            const hiddenInput = input.closest('td')?.querySelector('.employee-id-input');

            if (!hiddenInput?.value) {
                invalid = true;
            }
        });

        if (invalid) {
            event.preventDefault();
            alert('Pilih nama pegawai dari daftar saran yang muncul.');
        }
    });

    document.querySelectorAll('.employee-name-input').forEach(syncTemplateEmployeeInput);

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
@endpush
