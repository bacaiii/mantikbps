@extends('layouts.tenant')

@section('title', 'Dashboard')

@php
    $totalArc = ($statusChart['counts'][0] ?? 0) + ($statusChart['counts'][1] ?? 0);
    $totalNonArc = ($statusChart['counts'][2] ?? 0) + ($statusChart['counts'][3] ?? 0);

    $makeSortUrl = function ($column) use ($sortBy, $sortDir) {
        $newDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

        return route('tenant.dashboard', array_merge(request()->query(), [
            'sort_by' => $column,
            'sort_dir' => $newDir,
        ]));
    };

    $sortIcon = function ($column) use ($sortBy, $sortDir) {
        if ($sortBy !== $column) {
            return '';
        }

        return $sortDir === 'asc'
            ? '<i class="bi bi-caret-up-fill sort-icon"></i>'
            : '<i class="bi bi-caret-down-fill sort-icon"></i>';
    };

    $sortThClass = fn ($column) => $sortBy === $column ? 'sort-active' : '';
    $sortLinkClass = fn ($column) => $sortBy === $column ? 'sort-link active' : 'sort-link';

    $formatDateStack = function ($date) {
        if (!$date) {
            return '<span class="text-muted">-</span>';
        }

        return '<div class="date-stack">
                    <span class="date-main">' . e($date->translatedFormat('j F')) . '</span>
                    <span class="date-year">' . e($date->translatedFormat('Y')) . '</span>
                </div>';
    };
@endphp

@push('styles')
<style>
    /* ── Premium Chart Card (Light Mode) ── */
    .chart-card-premium {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .chart-card-premium .card-header {
        background: transparent;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 22px 26px 18px;
    }
    .chart-card-premium .card-header h5 {
        color: #1e293b;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: -0.2px;
        margin: 0;
    }
    .chart-card-premium .card-header small {
        color: #64748b;
        font-size: 12px;
    }
    .chart-card-premium .card-header .form-select {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-size: 13px;
        border-radius: 10px;
        font-weight: 500;
        cursor: pointer;
    }
    .chart-card-premium .card-header .form-select:focus {
        border-color: #94a3b8;
        box-shadow: none;
    }
    .chart-card-premium .card-body {
        padding: 22px 26px 26px;
    }

    /* ── Premium Stat Cards (Modern Dark Gradients) ── */
    .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }
    .stat-card-premium {
        border-radius: 14px;
        padding: 16px 14px 14px;
        text-align: center;
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.05);
        box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    }
    .stat-card-premium::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
        pointer-events: none;
    }
    .stat-card-premium:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    .stat-card-premium small {
        font-size: 11px;
        color: rgba(255,255,255,0.7);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        display: block;
        margin-bottom: 8px;
    }
    .stat-card-premium .stat-number {
        font-size: 28px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1;
        margin-bottom: 6px;
        font-variant-numeric: tabular-nums;
    }
    .stat-card-premium .stat-pct {
        font-size: 12px;
        font-weight: 700;
        color: rgba(255,255,255,0.9);
        background: rgba(0,0,0,0.25);
        border-radius: 999px;
        padding: 2px 10px;
        display: inline-block;
    }
    
    /* Modern Dark Gradients */
    .stat-card-premium.total-card { background: linear-gradient(135deg, #334155 0%, #0f172a 100%); }
    .stat-card-premium.arc-proses { background: linear-gradient(135deg, #1e40af 0%, #172554 100%); }
    .stat-card-premium.arc-selesai { background: linear-gradient(135deg, #166534 0%, #052e16 100%); }
    .stat-card-premium.nonarc-proses { background: linear-gradient(135deg, #0f766e 0%, #042f2e 100%); }
    .stat-card-premium.nonarc-selesai { background: linear-gradient(135deg, #3730a3 0%, #1e1b4b 100%); }

    .stat-card-premium .stat-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 5px;
        background: rgba(255,255,255,0.8) !important;
        vertical-align: middle;
    }
    .stat-card-premium.total-card .stat-dot { display: none; }

    .breakdown-row {
        display: flex;
        gap: 6px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .breakdown-chip {
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 9px;
        border-radius: 999px;
        border: 1px solid;
    }
    .chip-arc   { background: rgba(96,165,250,0.15); color: #bfdbfe; border-color: rgba(96,165,250,0.3); }
    .chip-nonarc{ background: rgba(255,255,255,0.1); color: #e2e8f0; border-color: rgba(255,255,255,0.2); }

    /* ── Chart Wrap ── */
    .vertical-bar-chart-wrap {
        min-height: 320px;
        position: relative;
    }

    /* ── Summary Table ── */
    .dashboard-summary-table {
        table-layout: fixed;
        width: 100%;
        font-size: 12.6px;
    }
    .dashboard-summary-table th,
    .dashboard-summary-table td {
        white-space: normal;
        word-break: break-word;
        padding: 9px 7px !important;
    }
    .dashboard-summary-table col.col-title { width: 28%; }
    .dashboard-summary-table col.col-category { width: 9%; }
    .dashboard-summary-table col.col-date,
    .dashboard-summary-table col.col-check-date,
    .dashboard-summary-table col.col-start-date { width: 12%; }
    .dashboard-summary-table col.col-status { width: 15%; }
</style>
@endpush

@section('content')
    <div class="chart-card-premium mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5>Grafik Status Publikasi</h5>
                <small>Persentase ARC dan Non-ARC berdasarkan tahun yang dipilih.</small>
            </div>
            <form method="GET" class="d-flex align-items-center gap-2">
                <label class="fw-semibold small mb-0" style="color:#64748b">Tahun</label>
                <select name="tahun" class="form-select form-select-sm" style="width:130px" onchange="this.form.submit()">
                    @foreach($yearOptions as $year)
                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="card-body">
            {{-- Premium Stat Cards --}}
            <div class="stat-cards-row">
                <div class="stat-card-premium total-card">
                    <span class="stat-dot"></span>
                    <small>Total Publikasi</small>
                    <div class="stat-number">{{ $totalPublications }}</div>
                    <div class="breakdown-row">
                        <span class="breakdown-chip chip-arc">ARC: {{ $totalArc }}</span>
                        <span class="breakdown-chip chip-nonarc">Non-ARC: {{ $totalNonArc }}</span>
                    </div>
                </div>

                @php
                    $cardClasses = ['arc-proses', 'arc-selesai', 'nonarc-proses', 'nonarc-selesai'];
                    $dotColors   = ['#fbbf24', '#34d399', '#22d3ee', '#60a5fa'];
                @endphp
                @foreach($statusChart['labels'] as $idx => $label)
                    <div class="stat-card-premium {{ $cardClasses[$idx] ?? '' }}">
                        <small>
                            <span class="stat-dot" style="background:{{ $dotColors[$idx] ?? '#94a3b8' }}"></span>
                            {{ $label }}
                        </small>
                        <div class="stat-number">{{ $statusChart['counts'][$idx] }}</div>
                        <span class="stat-pct">{{ $statusChart['percentages'][$idx] }}%</span>
                    </div>
                @endforeach
            </div>

            {{-- Vertical Bar Chart --}}
            <div class="vertical-bar-chart-wrap">
                <canvas id="statusStackedChart"></canvas>
            </div>
        </div>
    </div>


    @include('partials.dashboard-process-flow-card')

    <div class="card table-card">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">
                    Ringkasan Publikasi Bulan {{ $currentMonthName }} Tahun {{ $currentMonthYear }}
                </h5>
                <small class="text-muted">
                    Menampilkan publikasi yang akan rilis dan telah rilis pada bulan saat ini.
                </small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-fit-wrapper">
                <table class="table align-middle table-bordered table-clean publication-fit-table dashboard-summary-table">
                    <colgroup>
                        <col class="col-title">
                        <col class="col-category">
                        <col class="col-date">
                        <col class="col-date">
                        <col class="col-check-date">
                        <col class="col-start-date">
                        <col class="col-status">
                    </colgroup>

                    <thead>
                        <tr>
                            @foreach([
                                'nama_publikasi' => 'Nama Publikasi',
                                'kategori' => 'Kategori',
                                'jadwal_rilis' => 'Jadwal Rilis',
                                'jadwal_upload' => 'Jadwal Upload',
                                'jadwal_mulai_pemeriksaan' => 'Mulai<br>Pemeriksaan',
                                'jadwal_mulai_penyusunan' => 'Mulai<br>Penyusunan',
                                'status' => 'Status',
                            ] as $column => $label)
                                <th class="{{ $sortThClass($column) }}">
                                    <a href="{{ $makeSortUrl($column) }}" class="{{ $sortLinkClass($column) }}">
                                        <span>{!! $label !!}</span>
                                        {!! $sortIcon($column) !!}
                                    </a>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($dashboardPublications as $publication)
                            <tr>
                                <td class="name-cell">
                                    {{ $publication->nama_publikasi }}
                                </td>

                                <td>
                                    <span class="badge {{ $publication->kategori === 'ARC' ? 'bg-primary' : 'bg-secondary' }} compact-badge">
                                        {{ $publication->kategori }}
                                    </span>
                                </td>

                                <td>{!! $formatDateStack($publication->jadwal_rilis) !!}</td>
                                <td>{!! $formatDateStack($publication->jadwal_upload) !!}</td>
                                <td>{!! $formatDateStack($publication->jadwal_mulai_pemeriksaan) !!}</td>
                                <td>{!! $formatDateStack($publication->jadwal_mulai_penyusunan) !!}</td>

                                <td>
                                    <span class="status-chip {{ $publication->status_css_class }}">
                                        {{ $publication->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Belum ada publikasi yang rilis pada bulan ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-end">
                {{ $dashboardPublications->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    (function() {
        const ctx = document.getElementById('statusStackedChart');
        if (!ctx) return;
        const counts = @json($statusChart['counts']);
        const labels = @json($statusChart['labels']);
        
        // Gradient builder for each bar
        const c = ctx.getContext('2d');
        function makeGrad(r1,g1,b1,r2,g2,b2) {
            const g = c.createLinearGradient(0,0,0,400);
            g.addColorStop(0, `rgba(${r1},${g1},${b1},1)`);
            g.addColorStop(1, `rgba(${r2},${g2},${b2},0.85)`);
            return g;
        }

        const barColors = [
            makeGrad(30,64,175, 23,37,84),     // ARC Proses
            makeGrad(22,101,52, 5,46,22),      // ARC Selesai
            makeGrad(15,118,110, 4,47,46),     // Non-ARC Proses
            makeGrad(55,48,163, 30,27,75),     // Non-ARC Selesai
        ];

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Publikasi',
                    data: counts,
                    backgroundColor: barColors,
                    borderRadius: 8,
                    borderSkipped: false,
                    barPercentage: 0.55,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 900, easing: 'easeOutQuart' },
                layout: { padding: { top: 20 } },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: {
                            color: '#64748b',
                            font: { size: 12, family: 'Inter, Segoe UI, sans-serif', weight: '500' },
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.04)',
                            drawBorder: false,
                            borderDash: [5, 5]
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 12, family: 'Inter, Segoe UI, sans-serif' },
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 14,
                        titleColor: '#0f172a',
                        bodyColor: '#475569',
                        titleFont: { size: 13, weight: '700', family: 'Inter, Segoe UI, sans-serif' },
                        bodyFont:  { size: 13, family: 'Inter, Segoe UI, sans-serif', weight: '500' },
                        displayColors: false,
                        boxShadow: '0 4px 6px -1px rgba(0,0,0,0.1)',
                        callbacks: {
                            label: (item) => '  ' + item.raw + ' publikasi'
                        }
                    }
                }
            }
        });
    })();
    </script>
    @endpush
@endsection