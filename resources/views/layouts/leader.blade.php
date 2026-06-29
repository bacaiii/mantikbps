<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MANTIK - @yield('title', 'Dashboard Pimpinan')</title>
    <link rel="icon" href="{{ asset('images/mantik-favicon.svg') }}" type="image/svg+xml">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/sidebar.css') }}?v=20260630" rel="stylesheet">

    <style>
        body {
            background-color: #f5f7fb;
            font-family: "Segoe UI", sans-serif;
        }

        .table-clean {
            border-collapse: collapse !important;
            border: 1.5px solid #b7c4d6 !important;
        }

        .table-clean thead th {
            background: rgba(13, 110, 253, 0.13) !important;
            border: 1.5px solid #b7c4d6 !important;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
        }

        .table-clean tbody td {
            border: 1.5px solid #c5d0df !important;
            vertical-align: middle;
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
            text-align: center;
        }

        .status-chip.info {
            background: rgba(13, 202, 240, 0.50);
            color: #064e5b;
            border: 1px solid rgba(13, 202, 240, 0.75);
        }

        .status-chip.success {
            background: rgba(25, 135, 84, 0.50);
            color: #063b22;
            border: 1px solid rgba(25, 135, 84, 0.75);
        }

        .status-chip.warning {
            background: rgba(255, 193, 7, 0.55);
            color: #5f4100;
            border: 1px solid rgba(255, 193, 7, 0.85);
        }
    </style>

    <link href="{{ asset('css/bps-polish.css') }}?v=20260621-dashboard-process-flow-v2" rel="stylesheet">
    @stack('styles')
</head>
<body class="app-leader">
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
                <span class="nav-section-label">Menu Utama</span>

                <a href="{{ route('leader.dashboard') }}"
                   class="nav-link {{ request()->routeIs('leader.dashboard') ? 'active' : '' }}"
                   title="Dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span class="link-text">Dashboard</span>
                </a>

                <a href="{{ route('leader.approvals.index') }}"
                   class="nav-link {{ request()->routeIs('leader.approvals.*') ? 'active' : '' }}"
                   title="Persetujuan Rilis">
                    <i class="bi bi-patch-check"></i>
                    <span class="link-text">Persetujuan Rilis</span>
                </a>

                <a href="{{ route('leader.ready-release.index') }}"
                   class="nav-link {{ request()->routeIs('leader.ready-release.*') ? 'active' : '' }}"
                   title="Publikasi Siap Rilis">
                    <i class="bi bi-box-seam"></i>
                    <span class="link-text">Publikasi Siap Rilis</span>
                </a>
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
                    <small class="text-muted">{{ optional(auth()->user()->tenant)->name }}</small>
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

    @stack('scripts')
</body>
</html>
