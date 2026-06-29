<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Support\SimplePdf;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Publication::where('tenant_id', Auth::user()->tenant_id);

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $query->where('nama_publikasi', 'like', '%' . $request->q . '%');
        }

        $yearOptions = range(now()->year - 3, now()->year + 1);
        $selectedYear = (int) $request->input('tahun', now()->year);
        if (!in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = now()->year;
        }
        $query->where('tahun', $selectedYear);

        $monthOptions = collect(range(1, 12))->mapWithKeys(fn ($month) => [
            $month => Carbon::createFromDate($selectedYear, $month, 1)->translatedFormat('F')
        ]);
        $selectedMonth = $request->input('bulan');

        if ($selectedMonth !== null && $selectedMonth !== '') {
            $query->whereMonth('jadwal_rilis', (int) $selectedMonth);
        }

        $allowedSorts = [
            'nama_publikasi',
            'kategori',
            'periode',
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

        $publications = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate(10)
            ->withQueryString();

        return view('tenant.publications.index', compact('publications', 'sortBy', 'sortDir', 'yearOptions', 'selectedYear', 'monthOptions', 'selectedMonth'));
    }


    public function monthlyReport(Request $request)
    {
        [$publications, $yearOptions, $selectedYear, $monthOptions, $selectedMonth, $summary] = $this->monthlyReportData($request);

        return view('tenant.publications.monthly_report', compact(
            'publications',
            'yearOptions',
            'selectedYear',
            'monthOptions',
            'selectedMonth',
            'summary'
        ));
    }

    public function monthlyReportExcel(Request $request)
    {
        [$publications, $yearOptions, $selectedYear, $monthOptions, $selectedMonth] = $this->monthlyReportData($request);

        $headers = [
            'Nomor',
            'Nama Publikasi',
            'Estimasi Nomor Publikasi',
            'Pembuat Publikasi',
            'ARC/Non-ARC?',
            'Jadwal Rilis',
            'Jadwal Upload',
            'Jadwal Mulai Pemeriksaan',
            'Jadwal Mulai Penyusunan',
            'Akurasi Publikasi',
            'Status',
        ];

        $rows = $publications->values()->map(fn ($publication, $index) => [
            $index + 1,
            $publication->nama_publikasi,
            $publication->estimasi_nomor_publikasi ?: '-',
            optional($publication->team)->name ?: '-',
            $publication->kategori ?: '-',
            $publication->jadwal_rilis,
            $publication->jadwal_upload,
            $publication->jadwal_mulai_pemeriksaan,
            $publication->jadwal_mulai_penyusunan,
            $publication->akurasi_publikasi ?: '-',
            $publication->status_label,
        ])->all();

        $sheetName = 'ARC+Non ARC ' . $selectedYear;
        $xlsx = new SimpleXlsx($sheetName, $headers, $rows, [
            8, 48, 18, 24, 14, 16, 16, 20, 20, 16, 18,
        ]);

        $fileName = 'rekap-laporan-publikasi-' . $selectedYear . '-' . ($selectedMonth ?: 'semua-bulan') . '.xlsx';

        return response($xlsx->output(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function create()
    {
        $publication = new Publication([
            'kategori' => 'ARC',
            'periode' => 'Tahunan',
            'wilayah' => optional(Auth::user()->tenant)->wilayah,
            'status' => 'penyusunan',
        ]);

        return view('tenant.publications.create', compact('publication'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $this->validateScheduleLogic($validated, true);

        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['created_by'] = Auth::id();
        $validated['wilayah'] = optional(Auth::user()->tenant)->wilayah;

        $validated['tahun'] = $validated['jadwal_rilis']
            ? Carbon::parse($validated['jadwal_rilis'])->year
            : now()->year;

        $validated['status'] = 'penyusunan';

        DB::transaction(function () use ($validated) {
            $nextNomor = (Publication::where('tenant_id', $validated['tenant_id'])
                ->lockForUpdate()
                ->max('nomor') ?? 0) + 1;

            $validated['nomor'] = $nextNomor;

            Publication::create($validated);
        });

        return redirect()
            ->route('tenant.publications.index')
            ->with('success', 'Data publikasi berhasil ditambahkan. Status awal otomatis masuk tahap penyusunan.');
    }

    public function show(Publication $publication)
    {
        $this->authorizePublication($publication);

        return view('tenant.publications.show', compact('publication'));
    }

    public function edit(Publication $publication)
    {
        $this->authorizePublication($publication);

        return view('tenant.publications.edit', compact('publication'));
    }

    public function update(Request $request, Publication $publication)
    {
        $this->authorizePublication($publication);

        $validated = $request->validate($this->rules($publication), $this->messages());
        $this->validateScheduleLogic($validated, false);

        $validated['wilayah'] = optional(Auth::user()->tenant)->wilayah;

        $validated['tahun'] = $validated['jadwal_rilis']
            ? Carbon::parse($validated['jadwal_rilis'])->year
            : ($publication->tahun ?? now()->year);

        unset($validated['status']);

        $publication->update($validated);

        return redirect()
            ->route('tenant.publications.index')
            ->with('success', 'Data publikasi berhasil diperbarui.');
    }

    public function destroy(Publication $publication)
    {
        $this->authorizePublication($publication);

        $publication->delete();

        return redirect()
            ->route('tenant.publications.index')
            ->with('success', 'Data publikasi berhasil dihapus.');
    }


    protected function monthlyReportData(Request $request): array
    {
        $currentYear = now()->year;
        $yearOptions = range($currentYear - 3, $currentYear + 1);
        $selectedYear = (int) $request->input('tahun', $currentYear);

        if (!in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = $currentYear;
        }

        $monthOptions = collect(range(1, 12))->mapWithKeys(fn ($month) => [
            $month => Carbon::createFromDate($selectedYear, $month, 1)->translatedFormat('F')
        ]);
        $selectedMonth = $request->input('bulan', now()->month);

        if ($selectedMonth !== null && $selectedMonth !== '' && ((int) $selectedMonth < 1 || (int) $selectedMonth > 12)) {
            $selectedMonth = now()->month;
        }

        $query = Publication::with(['team', 'documents', 'sprp'])
            ->where('tenant_id', Auth::user()->tenant_id)
            ->whereYear('jadwal_rilis', $selectedYear);

        if ($selectedMonth !== null && $selectedMonth !== '') {
            $query->whereMonth('jadwal_rilis', (int) $selectedMonth);
        }

        $publications = $query
            ->orderBy('jadwal_rilis')
            ->orderBy('nama_publikasi')
            ->get();

        $summary = [
            'total' => $publications->count(),
            'siap_rilis' => $publications->where('status', 'siap_rilis')->count(),
            'dalam_proses' => $publications->where('status', '!=', 'siap_rilis')->count(),
            'lengkap' => $publications->filter(fn ($publication) => $this->publicationDocumentsComplete($publication))->count(),
        ];
        $summary['belum_lengkap'] = $summary['total'] - $summary['lengkap'];

        return [$publications, $yearOptions, $selectedYear, $monthOptions, $selectedMonth, $summary];
    }

    protected function publicationDocumentsComplete(Publication $publication): bool
    {
        $documentTypes = $publication->relationLoaded('documents')
            ? $publication->documents->pluck('document_type')->unique()
            : $publication->documents()->pluck('document_type')->unique();

        $tenantType = Auth::user()->tenant?->type;
        $requiredTypes = [
            'naskah_pdf',
            'naskah_zip',
            'surat_persetujuan_rilis',
        ];

        if (! in_array($tenantType, ['kabupaten', 'kota'], true)) {
            $requiredTypes[] = 'infografis';
            $requiredTypes[] = 'daftar_tabel_gambar';
        }

        return $publication->sprp !== null
            && collect($requiredTypes)->every(fn ($type) => $documentTypes->contains($type));
    }

    protected function dateText($date): string
    {
        return $date ? $date->translatedFormat('d F Y') : '-';
    }

    protected function authorizePublication(Publication $publication): void
    {
        abort_unless(
            $publication->tenant_id === Auth::user()->tenant_id,
            403,
            'Anda tidak memiliki akses ke publikasi ini.'
        );
    }

    protected function rules(?Publication $publication = null): array
    {
        return [
            'nama_publikasi' => [
                'required',
                'string',
                'max:255',
                Rule::unique('publications', 'nama_publikasi')
                    ->where(fn ($query) => $query->where('tenant_id', Auth::user()->tenant_id))
                    ->ignore($publication?->id),
            ],
            'estimasi_nomor_publikasi' => ['nullable', 'string', 'max:100'],
            'kategori' => ['required', Rule::in(['ARC', 'Non-ARC'])],
            'periode' => ['required', Rule::in(['Bulanan', 'Triwulanan', 'Semesteran', 'Tahunan', 'Lainnya'])],
            'jadwal_rilis' => ['required', 'date'],
            'jadwal_upload' => ['required', 'date'],
            'jadwal_mulai_pemeriksaan' => ['required', 'date'],
            'jadwal_mulai_penyusunan' => ['required', 'date'],
            'akurasi_publikasi' => ['nullable', Rule::in(['RSE', 'Non-RSE'])],
        ];
    }

    protected function messages(): array
    {
        return [
            'nama_publikasi.required' => 'Nama publikasi wajib diisi.',
            'nama_publikasi.unique' => 'Judul publikasi sudah digunakan. Judul publikasi tidak boleh sama persis dengan publikasi lain, meskipun kategorinya berbeda.',
            'kategori.required' => 'Kategori publikasi wajib dipilih.',
            'periode.required' => 'Periode publikasi wajib dipilih.',
            'jadwal_rilis.required' => 'Jadwal rilis wajib diisi.',
            'jadwal_upload.required' => 'Jadwal upload wajib diisi.',
            'jadwal_mulai_pemeriksaan.required' => 'Jadwal mulai pemeriksaan wajib diisi.',
            'jadwal_mulai_penyusunan.required' => 'Jadwal mulai penyusunan wajib diisi.',
        ];
    }

    protected function validateScheduleLogic(array $validated, bool $blockPastDates = true): void
    {
        $jadwalRilis = Carbon::parse($validated['jadwal_rilis'])->startOfDay();
        $jadwalUpload = Carbon::parse($validated['jadwal_upload'])->startOfDay();
        $jadwalMulaiPemeriksaan = Carbon::parse($validated['jadwal_mulai_pemeriksaan'])->startOfDay();
        $jadwalMulaiPenyusunan = Carbon::parse($validated['jadwal_mulai_penyusunan'])->startOfDay();
        $today = Carbon::today();

        if ($blockPastDates) {
            $dateFields = [
                'jadwal_rilis' => [$jadwalRilis, 'Jadwal Rilis Publikasi'],
                'jadwal_upload' => [$jadwalUpload, 'Jadwal Upload ke Portal'],
                'jadwal_mulai_pemeriksaan' => [$jadwalMulaiPemeriksaan, 'Jadwal Mulai Pemeriksaan'],
                'jadwal_mulai_penyusunan' => [$jadwalMulaiPenyusunan, 'Jadwal Mulai Penyusunan'],
            ];

            foreach ($dateFields as $field => [$date, $label]) {
                if ($date->lessThan($today)) {
                    throw ValidationException::withMessages([
                        $field => $label . ' tidak boleh menggunakan tanggal yang sudah lewat.',
                    ]);
                }
            }
        }

        $latestUpload = $jadwalRilis->copy()->subDays(3);
        if ($jadwalUpload->greaterThan($latestUpload)) {
            throw ValidationException::withMessages([
                'jadwal_upload' => 'Jadwal Upload ke Portal maksimal H-3 sebelum Jadwal Rilis Publikasi.',
            ]);
        }

        $latestBeforeUpload = $jadwalUpload->copy()->subDay();
        if ($jadwalMulaiPemeriksaan->greaterThan($latestBeforeUpload)) {
            throw ValidationException::withMessages([
                'jadwal_mulai_pemeriksaan' => 'Jadwal Mulai Pemeriksaan maksimal H-1 sebelum Jadwal Upload ke Portal.',
            ]);
        }

        if ($jadwalMulaiPenyusunan->greaterThan($latestBeforeUpload)) {
            throw ValidationException::withMessages([
                'jadwal_mulai_penyusunan' => 'Jadwal Mulai Penyusunan maksimal H-1 sebelum Jadwal Upload ke Portal.',
            ]);
        }

        if ($jadwalMulaiPenyusunan->greaterThan($jadwalMulaiPemeriksaan)) {
            throw ValidationException::withMessages([
                'jadwal_mulai_penyusunan' => 'Jadwal Mulai Penyusunan tidak boleh melewati Jadwal Mulai Pemeriksaan.',
            ]);
        }
    }

    protected function subtractWorkingDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy()->startOfDay();
        $count = 0;

        while ($count < $days) {
            $result->subDay();

            if (!$result->isWeekend()) {
                $count++;
            }
        }

        return $result;
    }
}