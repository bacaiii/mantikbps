<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MANTIK - Login</title>
    <link rel="icon" href="{{ asset('images/mantik-favicon.svg') }}" type="image/svg+xml">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(13, 110, 253, .25), transparent 32%),
                linear-gradient(135deg, #eef6ff 0%, #ffffff 42%, #dcecff 100%);
            font-family: "Segoe UI", sans-serif;
            color: #16335f;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .login-header {
            padding: 18px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            background: #fff;
            border-radius: 16px;
            padding: 8px;
            box-shadow: 0 10px 25px rgba(13, 110, 253, .12);
        }

        .login-shell {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
        }

        .login-panel {
            width: 100%;
            max-width: 1120px;
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(360px, .85fr);
            gap: 0;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(22, 51, 95, .16);
            background: #fff;
        }

        .workflow-side {
            position: relative;
            padding: 48px;
            background: linear-gradient(135deg, #0d6efd 0%, #0649a8 100%);
            color: #fff;
            overflow: hidden;
        }

        .workflow-side::after {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            right: -120px;
            bottom: -120px;
            border-radius: 50%;
            background: rgba(255,255,255,.12);
        }

        .workflow-side::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url('{{ asset('images/logo-bps.png') }}');
            background-repeat: no-repeat;
            background-size: 420px auto;
            background-position: center 54%;
            opacity: .09;
            filter: grayscale(12%) saturate(.92);
            pointer-events: none;
            z-index: 1;
        }

        .workflow-title {
            position: relative;
            z-index: 2;
            max-width: 580px;
        }

        .workflow-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            column-gap: 58px;
            row-gap: 34px;
            margin-top: 34px;
        }

        .workflow-card {
            position: relative;
            border: 1px solid rgba(255,255,255,.18);
            background: rgba(255,255,255,.13);
            border-radius: 18px;
            padding: 15px 34px 14px 15px;
            backdrop-filter: blur(8px);
        }

        .workflow-step-number {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #fff;
            color: #0d6efd;
            font-size: 12px;
            font-weight: 800;
            box-shadow: 0 5px 12px rgba(2, 6, 23, .16);
        }

        .workflow-arrow {
            position: absolute;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: rgba(255,255,255,.95);
            color: #0d6efd;
            box-shadow: 0 8px 18px rgba(2, 6, 23, .18);
        }

        .workflow-step-1 { grid-column: 1; grid-row: 1; }
        .workflow-step-2 { grid-column: 2; grid-row: 1; }
        .workflow-step-3 { grid-column: 2; grid-row: 2; }
        .workflow-step-4 { grid-column: 1; grid-row: 2; }
        .workflow-step-5 { grid-column: 1; grid-row: 3; }
        .workflow-step-6 { grid-column: 2; grid-row: 3; }

        .workflow-step-1 .workflow-arrow,
        .workflow-step-5 .workflow-arrow {
            right: -45px;
            top: 50%;
            transform: translateY(-50%);
        }

        .workflow-step-2 .workflow-arrow,
        .workflow-step-4 .workflow-arrow {
            left: 50%;
            bottom: -33px;
            transform: translateX(-50%);
        }

        .workflow-step-3 .workflow-arrow {
            left: -45px;
            top: 50%;
            transform: translateY(-50%);
        }

        .workflow-card > i {
            display: inline-flex;
            width: 34px;
            height: 34px;
            border-radius: 12px;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.2);
            margin-bottom: 9px;
        }

        .login-form-side {
            padding: 48px 42px;
        }

        .form-control,
        .input-group .btn {
            min-height: 46px;
        }

        .form-control {
            border-radius: 12px !important;
        }

        .input-group > .form-control:not(:last-child) {
            border-radius: 12px 0 0 12px !important;
        }

        .input-group > .btn:not(:first-child) {
            border-radius: 0 12px 12px 0 !important;
            margin-left: -1px;
        }

        .btn-primary {
            border-radius: 13px;
            min-height: 46px;
        }

        @media (max-width: 900px) {
            .login-panel { grid-template-columns: 1fr; }
            .workflow-side { padding: 34px 26px; }
            .login-form-side { padding: 34px 26px; }
        }

        @media (max-width: 640px) {
            .workflow-grid { grid-template-columns: 1fr; }
            .workflow-card {
                grid-column: 1 !important;
                grid-row: auto !important;
            }
            .workflow-card .workflow-arrow {
                left: 50% !important;
                right: auto !important;
                bottom: -22px !important;
                top: auto !important;
                transform: translateX(-50%) !important;
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <header class="login-header">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('images/logo-bps.png') }}" alt="Logo BPS" class="brand-logo">
                <div>
                    <div class="fw-bold fs-5">MANTIK</div>
                    <small class="text-muted">Manajemen Publikasi Statistik</small>
                </div>
            </div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2">BPS Provinsi Kepulauan Bangka Belitung</span>
        </header>

        <main class="login-shell">
            <div class="login-panel">
                <section class="workflow-side">
                    <div class="workflow-title">
                        <h1 class="fw-bold mb-3">Kelola publikasi dari perencanaan sampai rilis.</h1>
                        <p class="mb-0 opacity-75">Sistem membantu admin, pegawai, dan pimpinan memantau alur kerja publikasi secara terstruktur.</p>
                    </div>

                    <div class="workflow-grid">
                        <div class="workflow-card workflow-step-1">
                            <span class="workflow-step-number">1</span>
                            <i class="bi bi-journal-plus"></i><strong class="d-block">Publikasi</strong><small>Input data dan jadwal publikasi.</small>
                            <span class="workflow-arrow"><i class="bi bi-arrow-right"></i></span>
                        </div>
                        <div class="workflow-card workflow-step-2">
                            <span class="workflow-step-number">2</span>
                            <i class="bi bi-people"></i><strong class="d-block">Tim Kerja</strong><small>Alokasi pegawai sesuai peran.</small>
                            <span class="workflow-arrow"><i class="bi bi-arrow-down"></i></span>
                        </div>
                        <div class="workflow-card workflow-step-3">
                            <span class="workflow-step-number">3</span>
                            <i class="bi bi-file-earmark-text"></i><strong class="d-block">Penyusunan</strong><small>Dokumen dan SPRP disiapkan.</small>
                            <span class="workflow-arrow"><i class="bi bi-arrow-left"></i></span>
                        </div>
                        <div class="workflow-card workflow-step-4">
                            <span class="workflow-step-number">4</span>
                            <i class="bi bi-search"></i><strong class="d-block">Pemeriksaan</strong><small>Konten, layout, dan infografis diperiksa.</small>
                            <span class="workflow-arrow"><i class="bi bi-arrow-down"></i></span>
                        </div>
                        <div class="workflow-card workflow-step-5">
                            <span class="workflow-step-number">5</span>
                            <i class="bi bi-check2-circle"></i><strong class="d-block">Finalisasi</strong><small>Persetujuan dan paket rilis disiapkan.</small>
                            <span class="workflow-arrow"><i class="bi bi-arrow-right"></i></span>
                        </div>
                        <div class="workflow-card workflow-step-6">
                            <span class="workflow-step-number">6</span>
                            <i class="bi bi-cloud-upload"></i><strong class="d-block">Upload & Rilis</strong><small>Publikasi siap dikelola ke portal.</small>
                        </div>
                    </div>
                </section>

                <section class="login-form-side">
                    <div class="mb-4">
                        <h3 class="fw-bold mb-1">Masuk ke Sistem</h3>
                        <p class="text-muted mb-0">Gunakan ID Login atau email yang sudah terdaftar.</p>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form action="{{ route('login.post') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">ID Login / Email</label>
                            <input type="text" name="login" class="form-control" value="{{ old('login') }}" placeholder="Masukkan ID login atau email" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </button>

                        <div class="text-center mt-3">
                            <a href="{{ route('password.request') }}" class="text-decoration-none fw-semibold small">
                                <i class="bi bi-key me-1"></i> Lupa Kata Sandi?
                            </a>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>

    <script>
        function togglePassword(id, button) {
            const input = document.getElementById(id);
            const icon = button.querySelector('i');
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        }
    </script>
</body>
</html>
