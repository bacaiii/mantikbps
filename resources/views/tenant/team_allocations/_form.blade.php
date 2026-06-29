<form id="teamAllocationForm" action="{{ $formAction }}" method="POST">
    @csrf

    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label fw-semibold">Judul Publikasi</label>
            <select name="publication_id" class="form-select" required>
                <option value="">-- Pilih Judul Publikasi --</option>
                @foreach($publications as $publication)
                    <option value="{{ $publication->id }}" {{ (string) old('publication_id', $team->publication_id) === (string) $publication->id ? 'selected' : '' }}>
                        {{ $publication->nama_publikasi }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12">
            <label class="form-label fw-semibold">Template Tim Kerja</label>
            <select name="team_template_id" class="form-select" required>
                <option value="">-- Pilih Template Tim Kerja --</option>
                @foreach($teamTemplates as $template)
                    <option value="{{ $template->id }}" {{ (string) old('team_template_id') === (string) $template->id ? 'selected' : '' }}>
                        {{ $template->name }} - {{ $template->members->count() }} anggota
                    </option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">
                Sistem akan menerapkan anggota dan tugas default dari template tim ke publikasi yang dipilih.
            </small>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('tenant.team-allocations.index') }}" class="btn btn-light border">Kembali</a>
            <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        </div>
    </div>
</form>

<div id="teamAllocationLoading" class="team-allocation-loading d-none" aria-hidden="true">
    <div class="team-allocation-loading-card">
        <div class="team-allocation-loading-icon" aria-hidden="true">
            <i class="bi bi-diagram-3-fill"></i>
            <span></span>
        </div>
        <div class="team-allocation-loading-content">
            <strong>Menyimpan alokasi tim...</strong>
            <small>Template tim kerja sedang diterapkan ke publikasi. Mohon tunggu sebentar.</small>
            <div class="team-allocation-loading-bar"><span></span></div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .team-allocation-loading {
        position: fixed;
        inset: 0;
        z-index: 2050;
        display: flex;
        align-items: center;
        justify-content: center;
        background: radial-gradient(circle at top, rgba(59, 130, 246, .22), transparent 36%), rgba(15, 23, 42, 0.54);
        backdrop-filter: blur(6px);
    }

    .team-allocation-loading.d-none {
        display: none !important;
    }

    .team-allocation-loading-card {
        width: min(460px, calc(100vw - 32px));
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 24px 26px;
        border-radius: 24px;
        background: linear-gradient(135deg, rgba(255,255,255,.98), rgba(239, 246, 255, .96));
        color: #0f172a;
        border: 1px solid rgba(147, 197, 253, .55);
        box-shadow: 0 28px 80px rgba(15, 23, 42, 0.30);
    }

    .team-allocation-loading-icon {
        position: relative;
        width: 58px;
        height: 58px;
        flex: 0 0 58px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 20px;
        color: #fff;
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        box-shadow: 0 14px 30px rgba(37, 99, 235, .28);
    }

    .team-allocation-loading-icon i {
        position: relative;
        z-index: 2;
        font-size: 25px;
    }

    .team-allocation-loading-icon span {
        position: absolute;
        inset: -7px;
        border-radius: 24px;
        border: 2px solid rgba(37, 99, 235, .22);
        animation: allocationPulse 1.2s ease-in-out infinite;
    }

    .team-allocation-loading-content {
        min-width: 0;
        flex: 1;
    }

    .team-allocation-loading-card strong {
        display: block;
        font-size: 16px;
        font-weight: 900;
        letter-spacing: -.2px;
    }

    .team-allocation-loading-card small {
        display: block;
        margin-top: 5px;
        color: #64748b;
        font-weight: 700;
        line-height: 1.35;
    }

    .team-allocation-loading-bar {
        height: 7px;
        margin-top: 14px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(148, 163, 184, .22);
    }

    .team-allocation-loading-bar span {
        display: block;
        width: 42%;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #2563eb, #38bdf8, #2563eb);
        animation: allocationBar 1.15s ease-in-out infinite;
    }

    @keyframes allocationPulse {
        0%, 100% { transform: scale(.96); opacity: .55; }
        50% { transform: scale(1.08); opacity: 1; }
    }

    @keyframes allocationBar {
        0% { transform: translateX(-110%); }
        100% { transform: translateX(250%); }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('teamAllocationForm');
        const loading = document.getElementById('teamAllocationLoading');

        if (!form || !loading) {
            return;
        }

        form.addEventListener('submit', function () {
            loading.classList.remove('d-none');
            loading.setAttribute('aria-hidden', 'false');

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
            }
        });
    });
</script>
@endpush
