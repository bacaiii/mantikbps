<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MANTIK - Atur Ulang Kata Sandi</title>
    <link rel="icon" href="{{ asset('images/mantik-favicon.svg') }}" type="image/svg+xml">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 45%, #e9f2ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Segoe UI", sans-serif;
            padding: 24px;
        }

        .back-link {
            position: fixed;
            top: 28px;
            left: 32px;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .reset-card {
            width: 100%;
            max-width: 620px;
            border: none;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);
        }

        .form-control,
        .input-group-text {
            border-radius: 10px !important;
        }

        .input-group {
            gap: 0 !important;
        }

        .input-group > .input-group-text:first-child {
            border-radius: 10px 0 0 10px !important;
        }

        .input-group > .form-control:not(:first-child) {
            border-radius: 0 !important;
            margin-left: -1px;
        }

        .input-group > .form-control:last-child {
            border-radius: 0 10px 10px 0 !important;
        }

        .input-group > .btn:last-child {
            border-radius: 0 10px 10px 0 !important;
            margin-left: -1px;
        }

        .btn-send-code {
            min-width: 150px;
            border-radius: 10px;
            font-weight: 700;
        }

        @media (max-width: 576px) {
            .back-link {
                position: static;
                width: 100%;
                margin-bottom: 16px;
            }

            body {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <a href="{{ route('login') }}" class="back-link">
        <i class="bi bi-arrow-left fs-4"></i> Kembali
    </a>

    <div class="card reset-card">
        <div class="card-body p-4 p-md-5 bg-white">
            <h3 class="fw-bold mb-3 text-primary">Atur Ulang Kata Sandi</h3>
            <p class="text-muted mb-4">
                Masukkan email akun Anda, tekan tombol <strong>Kirim Kode</strong>, lalu masukkan kode verifikasi yang dikirim ke email untuk membuat kata sandi baru.
            </p>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('password.reset.update') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-primary"><i class="bi bi-envelope"></i></span>
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="{{ old('email', session('reset_email')) }}"
                            placeholder="Email"
                            required
                        >
                        <button
                            type="submit"
                            class="btn btn-warning btn-send-code"
                            formaction="{{ route('password.otp.send') }}"
                            formmethod="POST"
                        >
                            Kirim Kode
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Masukkan Kode Verifikasi <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-primary"><i class="bi bi-key"></i></span>
                        <input
                            type="text"
                            name="otp"
                            class="form-control"
                            value="{{ old('otp') }}"
                            placeholder="Kode Verifikasi"
                            inputmode="numeric"
                            maxlength="6"
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Kata Sandi <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-primary"><i class="bi bi-lock"></i></span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Kata Sandi"
                        >
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-primary"><i class="bi bi-lock"></i></span>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            placeholder="Konfirmasi Kata Sandi"
                        >
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password_confirmation', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-save me-1"></i> Simpan Kata Sandi
                </button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(id, button) {
            const input = document.getElementById(id);
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
