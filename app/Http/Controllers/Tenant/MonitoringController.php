<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MonitoringController extends Controller
{
    private array $requiredMonitoringDocuments = [
        'naskah_pdf' => 'Naskah PDF',
        'naskah_zip' => 'Naskah ZIP/RAR',
        'surat_persetujuan_rilis' => 'Surat Persetujuan Rilis',
    ];

    public function index(Request $request)
    {
        abort_unless(Auth::user()->role === 'admin_provinsi', 403, 'Menu ini hanya untuk Admin Provinsi.');

        $kabkotaTenants = Tenant::query()
            ->whereIn('type', ['kabupaten', 'kota'])
            ->orderBy('code')
            ->get();

        $wilayahOptions = $kabkotaTenants
            ->pluck('wilayah')
            ->filter()
            ->values()
            ->all();

        $monthOptions = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $yearOptions = range(now()->year - 3, now()->year + 1);
        $selectedYear = (int) $request->input('tahun', now()->year);
        if (!in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = now()->year;
        }

        $selectedMonth = (int) $request->input('bulan', 0);
        if ($selectedMonth < 1 || $selectedMonth > 12) {
            $selectedMonth = 0;
        }

        $query = Publication::query()
            ->select('publications.*')
            ->with(['tenant', 'documents'])
            ->join('tenants', 'tenants.id', '=', 'publications.tenant_id')
            ->whereIn('tenants.type', ['kabupaten', 'kota'])
            ->where('publications.tahun', $selectedYear);

        if ($request->filled('q')) {
            $query->where('publications.nama_publikasi', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('wilayah')) {
            $query->where('tenants.wilayah', $request->wilayah);
        }

        if ($request->filled('status')) {
            $query->where('publications.status', $request->status);
        }

        if ($selectedMonth > 0) {
            $query->whereMonth('publications.jadwal_rilis', $selectedMonth);
        }

        $summaryPublications = (clone $query)->get();
        $this->appendMonitoringAttributes($summaryPublications);

        $summary = [
            'total' => $summaryPublications->count(),
            'siap_rilis' => $summaryPublications->whereIn('status', ['siap_rilis', 'rilis_selesai'])->count(),
            'dalam_proses' => $summaryPublications->whereNotIn('status', ['siap_rilis', 'rilis_selesai'])->count(),
            'lengkap' => $summaryPublications->where('monitoring_complete', true)->count(),
            'belum_lengkap' => $summaryPublications->where('monitoring_complete', false)->count(),
        ];

        $monthlySource = Publication::query()
            ->select('publications.*')
            ->with(['tenant', 'documents'])
            ->join('tenants', 'tenants.id', '=', 'publications.tenant_id')
            ->whereIn('tenants.type', ['kabupaten', 'kota'])
            ->where('publications.tahun', $selectedYear);

        if ($request->filled('wilayah')) {
            $monthlySource->where('tenants.wilayah', $request->wilayah);
        }

        $monthlyPublications = $monthlySource->get();
        $this->appendMonitoringAttributes($monthlyPublications);

        $regionalRecap = $kabkotaTenants
            ->when($request->filled('wilayah'), fn ($items) => $items->where('wilayah', $request->wilayah)->values())
            ->map(function (Tenant $tenant) use ($monthlyPublications, $monthOptions) {
                $tenantPublications = $monthlyPublications->where('tenant_id', $tenant->id);

                $months = collect($monthOptions)->map(function ($monthName, $monthNumber) use ($tenantPublications) {
                    $monthItems = $tenantPublications->filter(function (Publication $publication) use ($monthNumber) {
                        return $publication->jadwal_rilis && (int) $publication->jadwal_rilis->format('n') === (int) $monthNumber;
                    });

                    return [
                        'month' => $monthName,
                        'total' => $monthItems->count(),
                        'ready' => $monthItems->whereIn('status', ['siap_rilis', 'rilis_selesai'])->count(),
                        'complete' => $monthItems->where('monitoring_complete', true)->count(),
                        'incomplete' => $monthItems->where('monitoring_complete', false)->count(),
                    ];
                });

                return [
                    'tenant' => $tenant,
                    'total' => $tenantPublications->count(),
                    'ready' => $tenantPublications->whereIn('status', ['siap_rilis', 'rilis_selesai'])->count(),
                    'complete' => $tenantPublications->where('monitoring_complete', true)->count(),
                    'incomplete' => $tenantPublications->where('monitoring_complete', false)->count(),
                    'arc' => $tenantPublications->where('kategori', 'ARC')->count(),
                    'non_arc' => $tenantPublications->where('kategori', 'Non-ARC')->count(),
                    'months' => $months,
                ];
            });

        $allowedSorts = [
            'wilayah',
            'nama_publikasi',
            'kategori',
            'jadwal_rilis',
            'jadwal_upload',
            'jadwal_mulai_pemeriksaan',
            'jadwal_mulai_penyusunan',
            'status',
            'created_at',
        ];

        $sortBy = in_array($request->get('sort_by'), $allowedSorts, true)
            ? $request->get('sort_by')
            : 'jadwal_rilis';

        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';

        $sortColumns = [
            'wilayah' => 'tenants.wilayah',
            'nama_publikasi' => 'publications.nama_publikasi',
            'kategori' => 'publications.kategori',
            'jadwal_rilis' => 'publications.jadwal_rilis',
            'jadwal_upload' => 'publications.jadwal_upload',
            'jadwal_mulai_pemeriksaan' => 'publications.jadwal_mulai_pemeriksaan',
            'jadwal_mulai_penyusunan' => 'publications.jadwal_mulai_penyusunan',
            'status' => 'publications.status',
            'created_at' => 'publications.created_at',
        ];

        $publications = $query
            ->orderBy($sortColumns[$sortBy], $sortDir)
            ->paginate(10)
            ->withQueryString();

        $this->appendMonitoringAttributes($publications->getCollection());

        return view('tenant.monitoring.index', compact(
            'publications',
            'wilayahOptions',
            'sortBy',
            'sortDir',
            'yearOptions',
            'selectedYear',
            'selectedMonth',
            'monthOptions',
            'summary',
            'regionalRecap'
        ));
    }

    private function appendMonitoringAttributes(Collection $publications): void
    {
        $publications->each(function (Publication $publication) {
            $availableDocumentTypes = $publication->documents
                ->pluck('document_type')
                ->filter()
                ->unique()
                ->values();

            $missing = collect($this->requiredMonitoringDocuments)->filter(function ($label, $type) use ($availableDocumentTypes) {
                return !$availableDocumentTypes->contains($type);
            });

            $availableCount = count($this->requiredMonitoringDocuments) - $missing->count();

            $publication->setAttribute('monitoring_required_total', count($this->requiredMonitoringDocuments));
            $publication->setAttribute('monitoring_available_total', $availableCount);
            $publication->setAttribute('monitoring_missing_items', $missing->values()->all());
            $publication->setAttribute('monitoring_complete', $missing->isEmpty());
            $publication->setAttribute('monitoring_completion_percent', (int) round(($availableCount / count($this->requiredMonitoringDocuments)) * 100));
        });
    }
}
