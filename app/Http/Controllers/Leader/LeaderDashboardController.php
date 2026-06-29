<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class LeaderDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $currentYear = now()->year;
        $yearOptions = range($currentYear - 3, $currentYear + 1);
        $selectedYear = (int) $request->get('tahun', $currentYear);

        if (!in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = $currentYear;
        }

        $baseQuery = Publication::where('tenant_id', $user->tenant_id)
            ->whereYear('jadwal_rilis', $selectedYear);

        $totalPublications = (clone $baseQuery)->count();

        $arcDalamProses = (clone $baseQuery)
            ->where('kategori', 'ARC')
            ->whereNotIn('status', ['siap_rilis', 'rilis_selesai'])
            ->count();

        $arcSelesai = (clone $baseQuery)
            ->where('kategori', 'ARC')
            ->whereIn('status', ['siap_rilis', 'rilis_selesai'])
            ->count();

        $nonArcDalamProses = (clone $baseQuery)
            ->where('kategori', 'Non-ARC')
            ->whereNotIn('status', ['siap_rilis', 'rilis_selesai'])
            ->count();

        $nonArcSelesai = (clone $baseQuery)
            ->where('kategori', 'Non-ARC')
            ->whereIn('status', ['siap_rilis', 'rilis_selesai'])
            ->count();

        $percentage = fn (int $value) => $totalPublications > 0
            ? round(($value / $totalPublications) * 100, 1)
            : 0;

        $statusChart = [
            'labels' => [
                'ARC dalam proses',
                'ARC selesai',
                'Non-ARC dalam proses',
                'Non-ARC selesai',
            ],
            'counts' => [
                $arcDalamProses,
                $arcSelesai,
                $nonArcDalamProses,
                $nonArcSelesai,
            ],
            'percentages' => [
                $percentage($arcDalamProses),
                $percentage($arcSelesai),
                $percentage($nonArcDalamProses),
                $percentage($nonArcSelesai),
            ],
        ];

        $allowedSorts = [
            'nama_publikasi',
            'kategori',
            'jadwal_rilis',
            'jadwal_upload',
            'jadwal_mulai_pemeriksaan',
            'jadwal_mulai_penyusunan',
            'status',
        ];

        $sortBy = in_array($request->get('sort_by'), $allowedSorts, true)
            ? $request->get('sort_by')
            : 'jadwal_rilis';

        $sortDir = $request->get('sort_dir') === 'desc' ? 'desc' : 'asc';

        $monthNow = Carbon::now();
        $dashboardPublications = Publication::where('tenant_id', $user->tenant_id)
            ->whereYear('jadwal_rilis', $monthNow->year)
            ->whereMonth('jadwal_rilis', $monthNow->month)
            ->orderBy($sortBy, $sortDir)
            ->paginate(10)
            ->withQueryString();

        $currentMonthName = $monthNow->translatedFormat('F');
        $currentMonthYear = $monthNow->year;

        return view('leader.dashboard', compact(
            'selectedYear',
            'yearOptions',
            'totalPublications',
            'statusChart',
            'dashboardPublications',
            'sortBy',
            'sortDir',
            'currentMonthName',
            'currentMonthYear'
        ));
    }

    public function readyRelease(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $currentYear = now()->year;
        $yearOptions = range($currentYear - 3, $currentYear + 1);
        $selectedYear = (int) $request->input('tahun', $currentYear);

        if (!in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = $currentYear;
        }

        $selectedMonth = $request->input('bulan');

        $query = Publication::with([
                'team.assignments.user',
                'documents.uploader',
                'sprp.submittedBy',
                'reviews.reviewer',
            ])
            ->where('tenant_id', $tenantId)
            ->where('status', 'siap_rilis')
            ->whereYear('jadwal_rilis', $selectedYear)
            ->orderByDesc('ready_for_release_at')
            ->orderByDesc('updated_at');

        if ($selectedMonth !== null && $selectedMonth !== '') {
            $query->whereMonth('jadwal_rilis', (int) $selectedMonth);
        }

        if ($request->filled('q')) {
            $query->where('nama_publikasi', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $publications = $query->paginate(8)->withQueryString();
        $monthOptions = collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => Carbon::createFromDate(now()->year, $month, 1)->translatedFormat('F')]);

        return view('leader.ready_release.index', compact('publications', 'yearOptions', 'selectedYear', 'monthOptions', 'selectedMonth'));
    }
}
