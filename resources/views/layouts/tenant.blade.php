<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MANTIK - @yield('title', 'Dashboard')</title>
    <link rel="icon" href="{{ asset('images/mantik-favicon.svg') }}" type="image/svg+xml">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/sidebar.css') }}?v=20260630" rel="stylesheet">

    <style>
        body {
            background-color: #f5f7fb;
            font-family: "Segoe UI", sans-serif;
        }

        .badge-status {
            white-space: normal;
            text-align: left;
            line-height: 1.3;
        }
        .table thead th {
            background: rgba(13, 110, 253, 0.10) !important;
            color: #0f172a;
            vertical-align: middle;
            border-color: rgba(13, 110, 253, 0.18) !important;
            font-size: 13px;
            font-weight: 700;
        }

        .table thead th.sort-active {
            background: rgba(13, 110, 253, 0.18) !important;
            box-shadow: inset 0 -2px 0 rgba(13, 110, 253, 0.65);
        }

        .sort-link {
            color: #0f172a;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            line-height: 1.3;
            width: 100%;
        }

        .sort-link:hover {
            color: #0d6efd;
        }

        .sort-link.active {
            color: #0d6efd;
        }

        .sort-icon {
            font-size: 11px;
            flex-shrink: 0;
        }

        .table-clean {
            width: 100%;
            border-collapse: collapse !important;
            border: 1.5px solid #b7c4d6 !important;
        }

        .table-clean thead th {
            background: rgba(13, 110, 253, 0.13) !important;
            color: #0f172a;
            border: 1.5px solid #b7c4d6 !important;
            font-size: 13px;
            font-weight: 700;
            vertical-align: middle;
            padding: 11px 12px;
            white-space: nowrap;
        }

        .table-clean tbody td {
            padding: 11px 12px;
            vertical-align: middle;
            border: 1.5px solid #c5d0df !important;
            background: #fff;
        }

        .table-clean tbody tr:hover td {
            background: #f4f8ff;
        }

        .table-clean .name-cell {
            min-width: 240px;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.4;
        }

        .date-stack {
            display: inline-flex;
            flex-direction: column;
            line-height: 1.2;
            min-width: 76px;
        }

        .date-stack .date-main {
            font-weight: 600;
            color: #1f2937;
        }

        .date-stack .date-year {
            font-size: 12px;
            color: #475569;
            margin-top: 3px;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.32rem 0.62rem;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 800;
            line-height: 1.15;
            white-space: normal;
            text-align: center;
            max-width: 115px;
            border: 1px solid transparent;
        }

        .status-chip.info {
            background: rgba(13, 202, 240, 0.50);
            color: #064e5b;
            border-color: rgba(13, 202, 240, 0.75);
        }

        .status-chip.success {
            background: rgba(25, 135, 84, 0.50);
            color: #063b22;
            border-color: rgba(25, 135, 84, 0.75);
        }

        .status-chip.warning {
            background: rgba(255, 193, 7, 0.55);
            color: #5f4100;
            border-color: rgba(255, 193, 7, 0.85);
        }

        .status-chip.secondary {
            background: rgba(108, 117, 125, 0.50);
            color: #263238;
            border-color: rgba(108, 117, 125, 0.75);
        }

        .compact-badge {
            font-size: 11px;
            font-weight: 800;
            padding: 0.32rem 0.55rem;
            border-radius: 999px;
        }

        .table-action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .col-estimasi {
            width: 105px !important;
            min-width: 105px !important;
            max-width: 110px !important;
            white-space: normal !important;
            line-height: 1.25;
        }

        .estimasi-cell {
            width: 105px;
            max-width: 105px;
            white-space: normal;
            word-break: break-word;
            font-size: 12.5px;
        }

        .status-col {
            width: 120px !important;
            min-width: 120px !important;
            max-width: 120px !important;
        }

        .user-table-fit {
            table-layout: fixed;
            width: 100%;
            font-size: 13px;
        }

        .user-table-fit th,
        .user-table-fit td {
            padding: 9px 8px !important;
            word-break: break-word;
        }

        .user-table-fit .col-no { width: 45px; }
        .user-table-fit .col-login { width: 125px; }
        .user-table-fit .col-name { width: 155px; }
        .user-table-fit .col-email { width: 190px; }
        .user-table-fit .col-phone { width: 110px; }
        .user-table-fit .col-role { width: 105px; }
        .user-table-fit .col-password { width: 165px; }
        .user-table-fit .col-action { width: 86px; }

        .user-table-fit .role-chip {
            display: inline-flex;
            max-width: 95px;
            white-space: normal;
            line-height: 1.15;
            text-align: center;
            justify-content: center;
        }

        .user-table-fit .password-input {
            font-size: 12px;
            padding-left: 6px;
            padding-right: 6px;
        }

        .pagination { margin-bottom: 0; }
        .pagination svg { width: 16px !important; height: 16px !important; }
        .table-fit-wrapper { width: 100%; overflow-x: hidden; }
        .publication-fit-table { width: 100%; table-layout: fixed; font-size: 12.8px; }

        .publication-fit-table th,
        .publication-fit-table td { word-break: break-word; white-space: normal; }

        .publication-fit-table thead th {
            white-space: normal !important;
            word-break: break-word;
            line-height: 1.2;
            text-align: center;
            padding: 10px 8px !important;
            font-size: 12.5px;
        }

        .publication-fit-table tbody td {
            white-space: normal;
            word-break: break-word;
            padding: 10px 8px !important;
            font-size: 12.8px;
        }

        .publication-fit-table .name-cell { min-width: 0 !important; width: auto !important; }
        .publication-fit-table col.col-title { width: 28%; }
        .publication-fit-table col.col-category { width: 9%; }
        .publication-fit-table col.col-date,
        .publication-fit-table col.col-check-date,
        .publication-fit-table col.col-start-date { width: 12%; }
        .publication-fit-table col.col-status { width: 13%; }
        .publication-fit-table col.col-action { width: 11%; }

        .publication-management-table col.col-title { width: 24%; }
        .publication-management-table col.col-date,
        .publication-management-table col.col-check-date,
        .publication-management-table col.col-start-date { width: 10.5%; }
        .publication-management-table col.col-action { width: 11%; }

        .assign-table th.col-team-name,
        .assign-table td.col-team-name { width: 40% !important; min-width: 40% !important; }
        .assign-table th.col-release-date,
        .assign-table td.col-release-date { width: 12% !important; min-width: 12% !important; }
        .assign-table th.col-region,
        .assign-table td.col-region { width: 14% !important; }
        .assign-table th.col-action,
        .assign-table td.col-action { width: 10% !important; }
    </style>

    <link href="{{ asset('css/bps-polish.css') }}?v=20260621-dashboard-process-flow-v2" rel="stylesheet">
    @stack('styles')
</head>
<body class="app-tenant">
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <aside class="sidebar" id="mantikSidebar">
        <div class="brand">
            <img src="{{ asset('images/logo-bps.png') }}" alt="Logo BPS" class="logo-bps">
            <div class="brand-text">
                <div class="brand-title">MANTIK</div>
                <div class="brand-subtitle">Manajemen Publikasi Statistik</div>
            </div>
            <button type="button" class="btn-sidebar-toggle" id="sidebarToggle" title="Buka/Tutup Sidebar">
                <i class="bi bi-chevron-double-left"></i>
            </button>
        </div>

        <div class="nav-wrapper">
            <nav class="nav flex-column">
                <span class="nav-section-label">Manajemen</span>

                <a href="{{ route('tenant.dashboard') }}"
                   class="nav-link {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}"
                   title="Dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span class="link-text">Dashboard</span>
                </a>

                <a href="{{ route('tenant.publications.index') }}"
                   class="nav-link {{ request()->routeIs('tenant.publications.*') ? 'active' : '' }}"
                   title="Manajemen Publikasi">
                    <i class="bi bi-journal-text"></i>
                    <span class="link-text">Manajemen Publikasi</span>
                </a>

                <a href="{{ route('tenant.user-accounts.index') }}"
                   class="nav-link {{ request()->routeIs('tenant.user-accounts.*') ? 'active' : '' }}"
                   title="Kelola Akun Pengguna">
                    <i class="bi bi-person-gear"></i>
                    <span class="link-text">Kelola Akun Pengguna</span>
                </a>

                <span class="nav-section-label">Tim Kerja</span>

                <a href="{{ route('tenant.team-allocations.index') }}"
                   class="nav-link {{ request()->routeIs('tenant.team-allocations.*') ? 'active' : '' }}"
                   title="Tim Kerja Publikasi">
                    <i class="bi bi-people"></i>
                    <span class="link-text">Tim Kerja Publikasi</span>
                </a>

                <a href="{{ route('tenant.team-templates.index') }}"
                   class="nav-link {{ request()->routeIs('tenant.team-templates.*') ? 'active' : '' }}"
                   title="Atur Tim Kerja">
                    <i class="bi bi-person-check"></i>
                    <span class="link-text">Atur Tim Kerja</span>
                </a>

                <span class="nav-section-label">Pemantauan</span>

                <a href="{{ route('tenant.publication-progress.index') }}"
                   class="nav-link {{ request()->routeIs('tenant.publication-progress.*') ? 'active' : '' }}"
                   title="Progres Publikasi">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span class="link-text">Progres Publikasi</span>
                </a>

                <a href="{{ route('tenant.inspection-guidelines.index') }}"
                   class="nav-link {{ request()->routeIs('tenant.inspection-guidelines.*') ? 'active' : '' }}"
                   title="Kelola Pedoman">
                    <i class="bi bi-ui-checks-grid"></i>
                    <span class="link-text">Kelola Pedoman</span>
                </a>

                <a href="{{ route('tenant.knowledge.index') }}"
                   class="nav-link {{ request()->routeIs('tenant.knowledge.*') ? 'active' : '' }}"
                   title="Knowledge">
                    <i class="bi bi-link-45deg"></i>
                    <span class="link-text">Knowledge</span>
                </a>

                <a href="{{ route('tenant.ready-release.index') }}"
                   class="nav-link {{ request()->routeIs('tenant.ready-release.*') ? 'active' : '' }}"
                   title="Publikasi Siap Rilis">
                    <i class="bi bi-box-seam"></i>
                    <span class="link-text">Publikasi Siap Rilis</span>
                </a>

                @if(auth()->user()->role === 'admin_provinsi')
                    <span class="nav-section-label">Provinsi</span>

                    <a href="{{ route('tenant.monitoring.index') }}"
                       class="nav-link {{ request()->routeIs('tenant.monitoring.*') ? 'active' : '' }}"
                       title="Monitoring & Evaluasi Kab/Kota">
                        <i class="bi bi-clipboard-data"></i>
                        <span class="link-text">Monitoring & Evaluasi Kab/Kota</span>
                    </a>
                @endif
            </nav>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-mobile-menu" id="mobileMenuBtn">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="mb-0 fw-bold">@yield('title', 'Dashboard')</h5>
                    <small class="text-muted">
                        {{ optional(auth()->user()->tenant)->name }} - {{ optional(auth()->user()->tenant)->wilayah }}
                    </small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                @include('partials.notification-dropdown')

                <div class="text-end">
                    <div class="fw-semibold">{{ auth()->user()->name }}</div>
                    <small class="text-muted">{{ auth()->user()->login_id }}</small>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </header>

        <main class="content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>Terjadi kesalahan:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/sidebar.js') }}?v=20260630"></script>

    <script>
        function togglePassword(id, button) {
            const input = document.getElementById(id);
            const icon = button.querySelector('i');

            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>