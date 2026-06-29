<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Models\PublicationDocument;
use App\Models\PublicationDraft;
use App\Models\PublicationSprp;
use App\Models\PublicationTeamAssignmentHistory;
use App\Models\DocumentTemplate;
use App\Support\AdminPublicationReportPdf;
use App\Support\ExternalReleaseDocumentDownloader;
use App\Support\PortableZipWriter;
use App\Support\SprpPackagePdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class PublicationProgressController extends Controller
{
    public function index(Request $request)
    {
        $query = Publication::with(['team.assignments.user'])
            ->where('tenant_id', Auth::user()->tenant_id);

        if ($request->filled('q')) {
            $query->where('nama_publikasi', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $yearOptions = range(now()->year - 3, now()->year + 1);
        $selectedYear = (int) $request->input('tahun', now()->year);
        if (!in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = now()->year;
        }
        $query->where('tahun', $selectedYear);

        $monthOptions = collect(range(1, 12))->mapWithKeys(fn ($month) => [
            $month => Carbon::createFromDate($selectedYear, $month, 1)->translatedFormat('F'),
        ]);
        $selectedMonth = $request->input('bulan');

        if ($selectedMonth !== null && $selectedMonth !== '') {
            $query->whereMonth('jadwal_rilis', (int) $selectedMonth);
        }

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

        $publications = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate(10)
            ->withQueryString();

        return view('tenant.publication_progress.index', compact('publications', 'sortBy', 'sortDir', 'yearOptions', 'selectedYear', 'monthOptions', 'selectedMonth'));
    }

    public function show(Publication $publication)
    {
        $this->authorizePublication($publication);

        $publication->load([
            'tenant',
            'team.assignments.user',
            'drafts.uploader',
            'drafts.reviews.reviewer',
            'documents.uploader',
            'sprp.submittedBy',
        ]);

        return view('tenant.publication_progress.show', compact('publication'));
    }

    public function history(Publication $publication)
    {
        $this->authorizePublication($publication);

        $publication->load([
            'teamAssignmentHistories.changedBy',
            'drafts.uploader',
            'drafts.reviews.reviewer',
            'documents.uploader',
            'sprp.submittedBy',
        ]);

        $activities = collect();

        foreach ($publication->teamAssignmentHistories as $history) {
            $activities->push([
                'time' => $history->created_at,
                'title' => $this->formatHistoryAction($history->action),
                'actor' => optional($history->changedBy)->name,
                'description' => $history->notes,
            ]);
        }

        foreach ($publication->documents as $document) {
            $activities->push([
                'time' => $document->uploaded_at ?? $document->created_at,
                'title' => 'Upload ' . $document->document_type_label . ' versi ' . $document->version,
                'actor' => optional($document->uploader)->name,
                'description' => $document->file_original_name,
            ]);
        }

        if ($publication->sprp) {
            $activities->push([
                'time' => $publication->sprp->submitted_at ?? $publication->sprp->updated_at,
                'title' => 'Pengisian Form SPRP',
                'actor' => optional($publication->sprp->submittedBy)->name,
                'description' => 'Form SPRP telah disimpan oleh tim penyusun.',
            ]);
        }

        foreach ($publication->drafts as $draft) {
            $activities->push([
                'time' => $draft->created_at,
                'title' => 'Upload draft versi ' . $draft->version,
                'actor' => optional($draft->uploader)->name,
                'description' => $draft->file_original_name,
            ]);

            foreach ($draft->reviews as $review) {
                if ($review->result === 'revisi') {
                    continue;
                }

                $activities->push([
                    'time' => $review->created_at,
                    'title' => $this->formatReviewHistoryTitle($review),
                    'actor' => optional($review->reviewer)->name,
                    'description' => $review->notes,
                ]);
            }
        }

        $activities = $activities->sortByDesc('time')->values();

        return view('tenant.publication_progress.history', compact('publication', 'activities'));
    }

    protected function formatHistoryAction(?string $action): string
    {
        return match ($action) {
            'Pemeriksaan konten dikembalikan' => 'Pemeriksaan KONTEN - Revisi/Dikembalikan ke Tim Penyusun',
            'Pemeriksaan layout dikembalikan' => 'Pemeriksaan LAYOUT - Revisi/Dikembalikan ke Tim Penyusun',
            'Pemeriksaan infografis dikembalikan' => 'Pemeriksaan INFOGRAFIS - Revisi/Dikembalikan ke Tim Penyusun',
            'Persetujuan pimpinan dikembalikan' => 'Persetujuan PIMPINAN - Revisi/Dikembalikan ke Tim Penyusun',
            'Submit ulang revisi penyusunan' => 'Submit ulang revisi KONTEN',
            default => $action ?: '-',
        };
    }

    protected function formatReviewHistoryTitle($review): string
    {
        return match ($review->review_type) {
            'konten' => 'Pemeriksaan KONTEN - ' . $review->result_label,
            'layout' => 'Pemeriksaan LAYOUT - ' . $review->result_label,
            'infografis' => 'Pemeriksaan INFOGRAFIS - ' . $review->result_label,
            'pimpinan' => 'Persetujuan PIMPINAN - ' . $review->result_label,
            default => 'Pemeriksaan - ' . $review->result_label,
        };
    }

    public function authorTeam(Publication $publication)
    {
        $this->authorizePublication($publication);

        $publication->load(['tenant', 'team.assignments.user', 'team.documents.uploader', 'team.sprp.submittedBy']);

        $publicationTeam = $publication->team;
        if (!$publicationTeam) {
            return redirect()
                ->route('tenant.publication-progress.index')
                ->with('error', 'Belum ada tim penyusun.');
        }

        $documentsByType = $publicationTeam->documents
            ->sortByDesc('version')
            ->groupBy('document_type');

        $latestDocuments = $documentsByType->map(fn ($items) => $items->sortByDesc('version')->first());

        $documentTemplates = collect();

        $sprp = $publicationTeam->sprp;
        $yearOptions = range(now()->year - 3, now()->year + 3);
        $completion = $this->authorCompletion($publicationTeam);
        $canEditAuthorDocuments = $publication->status === 'penyusunan';

        return view('tenant.publication_progress.author_team', compact(
            'publication',
            'publicationTeam',
            'documentsByType',
            'latestDocuments',
            'documentTemplates',
            'sprp',
            'yearOptions',
            'completion',
            'canEditAuthorDocuments'
        ));
    }

    public function uploadAuthorDocument(Request $request, Publication $publication)
    {
        $this->authorizePublication($publication);

        $publication->load('team');
        $publicationTeam = $publication->team;
        abort_unless($publicationTeam, 404, 'Tim kerja publikasi belum tersedia.');

        $this->ensurePublicationInDraftStage($publication);

        $type = $request->input('document_type');

        $request->validate([
            'document_type' => ['required', Rule::in(array_keys($this->documentUploadRules()))],
            'notes' => ['nullable', 'string'],
            'external_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $rules = $this->documentUploadRules()[$type];
        $this->validateDocumentInput($request, $type, $rules);

        DB::transaction(function () use ($publicationTeam, $publication, $type, $request) {
            if ($request->filled('external_url')) {
                $this->storeLinkedDocument($publicationTeam, $publication, $type, $request->input('external_url'), $request->input('notes'));
            } else {
                $files = $type === 'infografis'
                    ? $request->file('files', [])
                    : [$request->file('file')];

                foreach ($files as $file) {
                    if (!$file) {
                        continue;
                    }

                    $nextVersion = (PublicationDocument::where('publication_team_id', $publicationTeam->id)
                        ->where('document_type', $type)
                        ->max('version') ?? 0) + 1;

                    $path = $file->store('publication-documents/' . $publication->id . '/' . $type, 'public');

                    $document = PublicationDocument::create([
                        'publication_team_id' => $publicationTeam->id,
                        'publication_id' => $publication->id,
                        'uploaded_by' => Auth::id(),
                        'document_type' => $type,
                        'version' => $nextVersion,
                        'source_type' => 'file',
                        'file_path' => $path,
                        'file_original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'external_url' => null,
                        'notes' => $request->input('notes'),
                        'uploaded_at' => now(),
                    ]);

                    if ($type === 'naskah_pdf') {
                        PublicationDraft::create([
                            'publication_team_id' => $publicationTeam->id,
                            'publication_id' => $publication->id,
                            'uploaded_by' => Auth::id(),
                            'version' => $nextVersion,
                            'source_type' => 'file',
                            'file_path' => $document->file_path,
                            'external_url' => null,
                            'file_original_name' => $document->file_original_name,
                            'mime_type' => $document->mime_type,
                            'notes' => $request->input('notes'),
                            'submitted_at' => now(),
                        ]);
                    }
                }
            }

            PublicationTeamAssignmentHistory::create([
                'publication_id' => $publication->id,
                'action' => 'Admin upload dokumen penyusunan',
                'old_value' => $publication->status,
                'new_value' => $publication->status,
                'notes' => 'Admin membantu menyimpan ' . (new PublicationDocument(['document_type' => $type]))->document_type_label . ' karena tim penyusun berkendala.',
                'changed_by' => Auth::id(),
            ]);
        });

        return back()->with('success', $rules['success']);
    }

    public function saveAuthorSprp(Request $request, Publication $publication)
    {
        $this->authorizePublication($publication);

        $publication->load('team');
        $publicationTeam = $publication->team;
        abort_unless($publicationTeam, 404, 'Tim kerja publikasi belum tersedia.');

        $this->ensurePublicationInDraftStage($publication);

        $validated = $request->validate([
            'rancangan_perwajahan' => ['required', Rule::in(['Seksi Diseminasi dan Layanan Statistik', 'subject matter'])],
            'publikasi_baru' => ['required', Rule::in(['1', '0'])],
            'ukuran' => ['required', Rule::in(['B5 ISO', 'B5 JIS', 'A5', 'A4', 'Lainnya'])],
            'ukuran_lainnya' => ['nullable', 'required_if:ukuran,Lainnya', 'string', 'max:100'],
            'orientasi' => ['required', Rule::in(['Portrait', 'Landscape'])],
            'terbitan_ke' => ['required', 'string', 'max:100'],
            'tahun_pertama_terbit' => ['required', 'integer', 'min:' . (now()->year - 3), 'max:' . (now()->year + 3)],
            'diterbitkan_untuk' => ['required', Rule::in(['Eksternal', 'Internal'])],
            'jumlah_halaman_romawi' => ['required', 'string', 'max:50'],
            'jumlah_halaman_arab' => ['required', 'string', 'max:50'],
            'kerja_sama_luar_bps' => ['required', Rule::in(['1', '0'])],
            'bahasa' => ['required', 'array', 'min:1'],
            'bahasa.*' => ['required', Rule::in(['Indonesia', 'Inggris'])],
        ]);

        if ($validated['ukuran'] === 'Lainnya') {
            $validated['ukuran'] = $validated['ukuran_lainnya'];
        }
        unset($validated['ukuran_lainnya']);

        $validated['publication_team_id'] = $publicationTeam->id;
        $validated['publication_id'] = $publication->id;
        $validated['submitted_by'] = Auth::id();
        $validated['bidang_bagian'] = $publicationTeam->name;
        $validated['judul_publikasi'] = $publication->nama_publikasi;
        $validated['frekuensi_terbit'] = $publication->periode;
        $validated['kategori_rilis'] = $publication->kategori;
        $validated['tanggal_rilis'] = $publication->jadwal_rilis;
        $validated['publikasi_baru'] = (bool) $validated['publikasi_baru'];
        $validated['kerja_sama_luar_bps'] = (bool) $validated['kerja_sama_luar_bps'];
        $validated['submitted_at'] = now();

        DB::transaction(function () use ($publicationTeam, $publication, $validated) {
            PublicationSprp::updateOrCreate(
                ['publication_team_id' => $publicationTeam->id],
                $validated
            );

            PublicationTeamAssignmentHistory::create([
                'publication_id' => $publication->id,
                'action' => 'Admin simpan SPRP',
                'old_value' => $publication->status,
                'new_value' => $publication->status,
                'notes' => 'Admin membantu melengkapi Form SPRP untuk tim penyusun.',
                'changed_by' => Auth::id(),
            ]);
        });

        return back()->with('success', 'Form SPRP berhasil disimpan oleh admin.');
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

        return view('tenant.ready_release.index', compact('publications', 'yearOptions', 'selectedYear', 'monthOptions', 'selectedMonth'));
    }


    public function readyReleaseReportPdf(Publication $publication)
    {
        $report = $this->buildReadyReleaseReport($publication);
        $fileName = 'rekap-publikasi-siap-rilis-' . $publication->id . '.pdf';

        return response(AdminPublicationReportPdf::make($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    protected function buildReadyReleaseReport(Publication $publication): array
    {
        $this->authorizePublication($publication);

        $publication->loadMissing([
            'tenant',
            'team.assignments.user',
            'reviews.reviewer',
            'drafts.uploader',
            'documents.uploader',
            'sprp.submittedBy',
            'teamAssignmentHistories.changedBy',
        ]);

        abort_unless($publication->status === 'siap_rilis', 404, 'Rekap publikasi hanya tersedia untuk publikasi yang sudah siap rilis.');

        $team = $publication->team;
        $assignments = $team ? $team->assignments : collect();
        $reviews = $publication->reviews;
        $latestDraft = $publication->drafts
            ->sortByDesc('version')
            ->sortByDesc(fn ($draft) => optional($draft->submitted_at ?: $draft->created_at)->timestamp ?? 0)
            ->first();
        $finalHistory = $publication->teamAssignmentHistories
            ->where('action', 'Finalisasi rilis diselesaikan')
            ->sortByDesc('created_at')
            ->first();

        $contentDecision = $this->reviewDecision($reviews, 'konten');
        $layoutDecision = $this->reviewDecision($reviews, 'layout');
        $infographicDecision = $this->reviewDecision($reviews, 'infografis');
        $leaderDecision = $this->reviewDecision($reviews, 'pimpinan');

        $leaderNames = $reviews
            ->where('review_type', 'pimpinan')
            ->pluck('reviewer.name')
            ->filter()
            ->unique()
            ->implode(', ');

        $rows = [
            [
                'task' => 'Penyusun Naskah',
                'names' => $this->assignmentNames($assignments, ['penyusun_naskah']),
                'start' => $this->dateLabel($publication->jadwal_mulai_penyusunan ?: $this->assignmentStart($assignments, ['penyusun_naskah']), 'd-m-Y'),
                'finish' => $this->dateLabel($publication->draft_submitted_at ?: optional($latestDraft)->submitted_at ?: optional($latestDraft)->created_at, 'd-m-Y'),
                'result' => 'Selesai',
                'notes' => $latestDraft
                    ? 'Naskah telah disubmit dalam draft versi ' . $latestDraft->version . '.'
                    : 'Naskah publikasi telah selesai disusun.',
            ],
            [
                'task' => 'Pemeriksa Konten Naskah',
                'names' => $this->assignmentNames($assignments, ['ketua_pemeriksa_konten', 'anggota_pemeriksa_konten']),
                'start' => $this->dateLabel($publication->content_review_started_at ?: $this->assignmentStart($assignments, ['ketua_pemeriksa_konten', 'anggota_pemeriksa_konten']), 'd-m-Y'),
                'finish' => $this->dateLabel($publication->content_review_finished_at ?: $contentDecision['date'], 'd-m-Y'),
                'result' => $contentDecision['result'],
                'notes' => $contentDecision['notes'],
            ],
            [
                'task' => 'Pemeriksa Layout Naskah',
                'names' => $this->assignmentNames($assignments, ['ketua_pemeriksa_layout', 'anggota_pemeriksa_layout']),
                'start' => $this->dateLabel($publication->layout_review_started_at ?: $this->assignmentStart($assignments, ['ketua_pemeriksa_layout', 'anggota_pemeriksa_layout']), 'd-m-Y'),
                'finish' => $this->dateLabel($publication->layout_review_finished_at ?: $layoutDecision['date'], 'd-m-Y'),
                'result' => $layoutDecision['result'],
                'notes' => $layoutDecision['notes'],
            ],
            [
                'task' => 'Operator Infografis',
                'names' => $this->assignmentNames($assignments, ['operator_infografis']),
                'start' => $this->dateLabel($publication->infographic_review_started_at ?: $this->assignmentStart($assignments, ['operator_infografis']), 'd-m-Y'),
                'finish' => $this->dateLabel($publication->infographic_review_finished_at ?: $infographicDecision['date'], 'd-m-Y'),
                'result' => $infographicDecision['result'],
                'notes' => $infographicDecision['notes'],
            ],
            [
                'task' => 'Persetujuan Rilis Pimpinan',
                'names' => $leaderNames ?: '-',
                'start' => $this->dateLabel($publication->infographic_review_finished_at ?: $publication->layout_review_finished_at, 'd-m-Y'),
                'finish' => $this->dateLabel($publication->leadership_approved_at ?: $leaderDecision['date'], 'd-m-Y'),
                'result' => $leaderDecision['result'],
                'notes' => $leaderDecision['notes'],
            ],
            [
                'task' => 'Petugas Upload ke Portal',
                'names' => $this->assignmentNames($assignments, ['operator_website']),
                'start' => $this->dateLabel($publication->leadership_approved_at ?: $this->assignmentStart($assignments, ['operator_website']), 'd-m-Y'),
                'finish' => $this->dateLabel($publication->ready_for_release_at ?: $publication->website_packaged_at, 'd-m-Y'),
                'result' => 'Selesai',
                'notes' => optional($finalHistory)->notes ?: 'Paket publikasi selesai diperiksa dan status publikasi menjadi Siap Rilis.',
            ],
        ];

        $decisionNotes = $this->readyReleaseDecisionNotes($reviews);

        $rows = collect($rows)
            ->reject(fn ($row) => $row['task'] === 'Operator Infografis' && $row['names'] === '-' && $row['result'] === '-')
            ->values()
            ->all();

        return [
            'publication' => [
                'title' => $publication->nama_publikasi ?? '-',
                'team' => optional($team)->name ?? '-',
                'region' => $publication->wilayah ?? optional($publication->tenant)->wilayah ?? '-',
                'category' => $publication->kategori ?? '-',
                'period' => $publication->periode ?? '-',
                'accuracy' => $publication->akurasi_publikasi ?? '-',
                'release_date' => $this->dateLabel($publication->jadwal_rilis, 'd F Y'),
                'ready_date' => $this->dateLabel($publication->ready_for_release_at),
                'status' => $publication->status_label ?? '-',
                'estimated_number' => $publication->estimasi_nomor_publikasi ?: '-',
                'year' => (string) ($publication->tahun ?? '-'),
            ],
            'summary' => [
                'stage_count' => count($rows),
                'revision_count' => $reviews->where('result', 'revisi')->count(),
                'approved_count' => $reviews->where('result', 'disetujui')->count(),
                'document_count' => $publication->documents->count(),
            ],
            'rows' => $rows,
            'decision_notes' => $decisionNotes,
        ];
    }

    protected function assignmentNames($assignments, array $roles): string
    {
        $names = $assignments
            ->whereIn('assignment_role', $roles)
            ->pluck('user.name')
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        return $names ?: '-';
    }

    protected function assignmentStart($assignments, array $roles)
    {
        return $assignments
            ->whereIn('assignment_role', $roles)
            ->sortBy('created_at')
            ->first()?->created_at;
    }

    protected function reviewDecision($reviews, string $type): array
    {
        $stageReviews = $reviews->where('review_type', $type)->values();

        if ($stageReviews->isEmpty()) {
            return [
                'result' => '-',
                'notes' => 'Belum ada keputusan tercatat.',
                'date' => null,
            ];
        }

        $latest = $stageReviews
            ->sortByDesc(fn ($review) => optional($review->reviewed_at ?: $review->created_at)->timestamp ?? 0)
            ->first();

        $revisionCount = $stageReviews->where('result', 'revisi')->count();
        $isApproved = $latest->result === 'disetujui';
        $result = $isApproved ? 'Disetujui' : 'Revisi';
        $notes = trim((string) $latest->notes);

        if ($notes === '') {
            $notes = $isApproved ? 'Keputusan akhir disetujui.' : 'Keputusan akhir perlu revisi.';
        } elseif ($isApproved && $revisionCount > 0) {
            $notes = 'Pernah revisi, keputusan akhir disetujui. ' . $notes;
        }

        return [
            'result' => $result,
            'notes' => $this->briefDecisionNote($notes),
            'date' => $latest->reviewed_at ?: $latest->created_at,
        ];
    }

    protected function readyReleaseDecisionNotes($reviews): array
    {
        $revisionReviews = $reviews
            ->where('result', 'revisi')
            ->groupBy(fn ($review) => ($review->review_type ?? '-') . ':' . ($review->reviewer_id ?? '0'))
            ->map(function ($items) {
                return $items
                    ->sortByDesc(fn ($review) => optional($review->reviewed_at ?: $review->created_at)->timestamp ?? 0)
                    ->first();
            })
            ->sortBy(fn ($review) => optional($review->reviewed_at ?: $review->created_at)->timestamp ?? 0)
            ->values();

        if ($revisionReviews->isEmpty()) {
            return [[
                'reviewer' => 'Semua Pemeriksa',
                'note' => 'Tidak terdapat catatan revisi akhir pada publikasi ini.',
                'result' => 'Disetujui',
            ]];
        }

        return $revisionReviews->map(function ($review) use ($reviews) {
            $reviewType = (string) ($review->review_type ?? '-');
            $reviewerName = optional($review->reviewer)->name ?: $this->reviewTypeLabel($reviewType);
            $latestInStage = $reviews
                ->where('review_type', $reviewType)
                ->sortByDesc(fn ($item) => optional($item->reviewed_at ?: $item->created_at)->timestamp ?? 0)
                ->first();

            $note = trim((string) $review->notes);
            if ($note === '') {
                $note = 'Terdapat catatan revisi pada ' . $this->reviewTypeLabel($reviewType) . '.';
            }

            $finalResult = optional($latestInStage)->result === 'disetujui' ? 'Sudah diperbaiki' : 'Perlu revisi';

            return [
                'reviewer' => $reviewerName,
                'note' => $this->briefDecisionNote($this->reviewTypeLabel($reviewType) . ': ' . $note),
                'result' => $finalResult,
            ];
        })->values()->all();
    }

    protected function reviewTypeLabel(string $type): string
    {
        return match ($type) {
            'konten' => 'Pemeriksaan Konten',
            'layout' => 'Pemeriksaan Layout',
            'infografis' => 'Pemeriksaan Infografis',
            'pimpinan' => 'Persetujuan Rilis Pimpinan',
            default => 'Pemeriksaan',
        };
    }

    protected function briefDecisionNote(string $notes): string
    {
        $notes = trim(preg_replace('/\s+/', ' ', $notes) ?? '');

        if ($notes === '') {
            return '-';
        }

        return mb_strlen($notes) > 180 ? mb_substr($notes, 0, 177) . '...' : $notes;
    }

    protected function dateLabel($date, string $format = 'd-m-Y H:i'): string
    {
        if (!$date) {
            return '-';
        }

        try {
            $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
            return $carbon->translatedFormat($format);
        } catch (\Throwable $exception) {
            return '-';
        }
    }

    public function downloadReleasePackage(Publication $publication)
    {
        $this->authorizePublication($publication);

        $publication->load([
            'tenant',
            'team.assignments.user',
            'documents.uploader',
            'sprp.submittedBy',
        ]);

        abort_unless($publication->status === 'siap_rilis', 404, 'Paket rilis hanya tersedia untuk publikasi yang sudah siap rilis.');

        $documentsByType = $publication->documents
            ->sortByDesc('version')
            ->groupBy('document_type');

        $materialTypes = [
            'naskah_pdf' => ['prefix' => '01', 'folder' => 'Naskah PDF'],
            'naskah_zip' => ['prefix' => '02', 'folder' => 'File Naskah ZIP-RAR'],
            'infografis' => ['prefix' => '03', 'folder' => 'Infografis'],
            'daftar_tabel_gambar' => ['prefix' => '04', 'folder' => 'Daftar Tabel dan Gambar'],
            'surat_persetujuan_rilis' => ['prefix' => '05', 'folder' => 'Surat Persetujuan Rilis'],
        ];

        $baseFolder = $this->safeZipName('Paket Rilis - ' . $publication->nama_publikasi);
        $zipFileName = $this->safeZipName('paket-rilis-' . $publication->nama_publikasi) . '.zip';

        $temporaryDirectory = storage_path('app/tmp');
        File::ensureDirectoryExists($temporaryDirectory);
        $zipPath = tempnam($temporaryDirectory, 'paket_rilis_admin_');

        try {
            $zip = new PortableZipWriter($zipPath);
            $zip->addFromString($baseFolder . '/README_Paket_Rilis.txt', $this->releasePackageReadme($publication));
            $externalDownloader = new ExternalReleaseDocumentDownloader();
            $externalDownloadNotes = [];

            if ($publication->sprp) {
                $zip->addFromString($baseFolder . '/00_Form_SPRP/SPRP_Digital.pdf', SprpPackagePdf::make($publication->sprp));
            }

            foreach ($materialTypes as $type => $meta) {
                $latestDocument = ($documentsByType->get($type) ?? collect())
                    ->sortByDesc('version')
                    ->first();

                if (!$latestDocument) {
                    continue;
                }

                if ($latestDocument->is_link) {
                    $targetFolder = $baseFolder . '/' . $meta['prefix'] . '_' . $meta['folder'];
                    $downloadedExternalDocument = $externalDownloader->download(
                        (string) $latestDocument->external_url,
                        $temporaryDirectory,
                        (string) $latestDocument->file_original_name,
                        $type
                    );

                    if ($downloadedExternalDocument['success']) {
                        $extension = pathinfo($downloadedExternalDocument['file_name'], PATHINFO_EXTENSION);
                        $baseName = pathinfo($downloadedExternalDocument['file_name'], PATHINFO_FILENAME);
                        $zipName = $meta['prefix'] . '_' . $this->safeZipName($baseName ?: $latestDocument->document_type_label);

                        if ($extension) {
                            $zipName .= '.' . strtolower($extension);
                        }

                        $zip->addFile($downloadedExternalDocument['path'], $targetFolder . '/' . $zipName);
                        @unlink($downloadedExternalDocument['path']);

                        $externalDownloadNotes[] = 'BERHASIL - ' . $latestDocument->document_type_label . ': file asli dari link berhasil dimasukkan ke paket sebagai ' . $zipName . '.';
                    } else {
                        $zip->addFromString(
                            $targetFolder . '/' . $meta['prefix'] . '_Link_Dokumen.txt',
                            'Dokumen belum dapat diunduh otomatis dari link eksternal.' . PHP_EOL
                            . 'Link tetap disertakan sebagai cadangan agar petugas dapat membuka dokumen secara manual.' . PHP_EOL . PHP_EOL
                            . 'Jenis Dokumen: ' . $latestDocument->document_type_label . PHP_EOL
                            . 'URL: ' . $latestDocument->external_url . PHP_EOL
                            . 'Alasan: ' . $downloadedExternalDocument['message'] . PHP_EOL
                        );

                        $externalDownloadNotes[] = 'GAGAL - ' . $latestDocument->document_type_label . ': ' . $downloadedExternalDocument['message'];
                    }

                    continue;
                }

                $realPath = $this->resolvePublicStoragePath($latestDocument->file_path);

                if (!$realPath) {
                    continue;
                }

                $extension = pathinfo($latestDocument->file_original_name, PATHINFO_EXTENSION);
                $baseName = pathinfo($latestDocument->file_original_name, PATHINFO_FILENAME);
                $zipName = $meta['prefix'] . '_' . $this->safeZipName($baseName);

                if ($extension) {
                    $zipName .= '.' . strtolower($extension);
                }

                $zip->addFile($realPath, $baseFolder . '/' . $meta['prefix'] . '_' . $meta['folder'] . '/' . $zipName);
            }

            if (!empty($externalDownloadNotes)) {
                $zip->addFromString(
                    $baseFolder . '/CATATAN_LINK_EKSTERNAL.txt',
                    'Catatan Pengambilan Dokumen Link Eksternal' . PHP_EOL
                    . 'Dibuat otomatis saat paket siap rilis diunduh.' . PHP_EOL . PHP_EOL
                    . implode(PHP_EOL, $externalDownloadNotes) . PHP_EOL
                );
            }

            $zip->close();
        } catch (\Throwable $exception) {
            if (isset($zip) && $zip instanceof PortableZipWriter) {
                try {
                    $zip->close();
                } catch (\Throwable $ignored) {
                    // Biarkan file sementara dihapus pada blok berikutnya.
                }
            }

            if (is_file($zipPath)) {
                @unlink($zipPath);
            }

            return redirect()
                ->back()
                ->with('error', 'Paket rilis gagal dibuat. Pastikan dokumen final masih tersedia di storage, lalu coba kembali.');
        }

        return response()
            ->download($zipPath, $zipFileName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function downloadDocument(PublicationDocument $publicationDocument)
    {
        $publication = $publicationDocument->publication;

        abort_unless($publication, 404, 'Data publikasi dokumen tidak ditemukan.');

        $this->authorizePublication($publication);

        if ($publicationDocument->is_link) {
            return redirect()->away($publicationDocument->external_url);
        }

        $realPath = $this->resolvePublicStoragePath($publicationDocument->file_path);

        if (!$realPath) {
            return redirect()
                ->back()
                ->with('error', 'File dokumen penyusun tidak ditemukan di folder storage. Silakan minta tim penyusun mengunggah ulang file tersebut.');
        }

        return response()->download(
            $realPath,
            $publicationDocument->file_original_name ?: basename($realPath)
        );
    }

    public function previewDocument(PublicationDocument $publicationDocument)
    {
        $publication = $publicationDocument->publication;

        abort_unless($publication, 404, 'Data publikasi dokumen tidak ditemukan.');

        $this->authorizePublication($publication);

        abort_unless(
            $publicationDocument->is_image,
            403,
            'Pratinjau hanya tersedia untuk file gambar.'
        );

        $realPath = $this->resolvePublicStoragePath($publicationDocument->file_path);

        abort_unless(
            $realPath,
            404,
            'File pratinjau tidak ditemukan di folder storage.'
        );

        $mimeType = $publicationDocument->mime_type ?: File::mimeType($realPath) ?: 'application/octet-stream';

        return response()->file($realPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . ($publicationDocument->file_original_name ?: basename($realPath)) . '"',
        ]);
    }    

    protected function releasePackageReadme(Publication $publication): string
    {
        $admin = Auth::user();
        $adminRoleLabel = $admin->role === 'admin_provinsi' ? 'Admin Provinsi' : 'Admin Wilayah';

        return implode(PHP_EOL, [
            'PAKET PUBLIKASI SIAP RILIS',
            'Manajemen Publikasi Statistik',
            '',
            'Nama Publikasi        : ' . ($publication->nama_publikasi ?? '-'),
            'Tim Kerja             : ' . (optional($publication->team)->name ?? '-'),
            'Kategori              : ' . ($publication->kategori ?? '-'),
            'Periode               : ' . ($publication->periode ?? '-'),
            'Akurasi Publikasi     : ' . ($publication->akurasi_publikasi ?? '-'),
            'Nomor Estimasi        : ' . ($publication->estimasi_nomor_publikasi ?: '-'),
            'Jadwal Rilis          : ' . ($publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('d F Y') : '-'),
            'Wilayah               : ' . ($publication->wilayah ?? optional($publication->tenant)->wilayah ?? '-'),
            'Status                : ' . ($publication->status_label ?? '-'),
            '',
            'Diunduh Oleh          : ' . ($admin->name ?? '-'),
            'Peran Unduh           : ' . $adminRoleLabel,
            'Tanggal Unduh         : ' . now()->format('d-m-Y H:i'),
            '',
            'Catatan:',
            '- Paket ini berisi dokumen versi terbaru/final yang tersedia di sistem.',
            '- Form SPRP disertakan dalam bentuk PDF agar dapat langsung digunakan pada paket rilis.',
            '- Jika dokumen disimpan sebagai link, sistem mencoba mengunduh file aslinya secara otomatis.',
            '- Jika link tidak dapat diakses server, paket tetap menyertakan file TXT berisi link cadangan.',
            '- Paket ini digunakan sebagai bahan finalisasi/unggah manual ke website resmi BPS.',
            '',
        ]);
    }

    protected function safeZipName(string $value): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('#[\\\\/:*?"<>|]+#u', '-', $value) ?? 'file';
        $value = preg_replace('/\s+/u', ' ', $value) ?? 'file';
        $value = trim($value, " .-_\t\n\r\0\x0B");

        return mb_substr($value !== '' ? $value : 'file', 0, 120);
    }

    protected function resolvePublicStoragePath(?string $filePath): ?string
    {
        $relativePath = ltrim((string) $filePath, '/');

        if ($relativePath === '') {
            return null;
        }

        $candidatePaths = [
            storage_path('app/public/' . $relativePath),
            public_path('storage/' . $relativePath),
        ];

        foreach ($candidatePaths as $candidatePath) {
            if ($candidatePath && File::exists($candidatePath)) {
                return $candidatePath;
            }
        }

        return null;
    }

    protected function authorizePublication(Publication $publication): void
    {
        abort_unless(
            $publication->tenant_id === Auth::user()->tenant_id,
            403,
            'Anda tidak memiliki akses ke publikasi ini.'
        );
    }

    protected function ensurePublicationInDraftStage(Publication $publication): void
    {
        if ($publication->status !== 'penyusunan') {
            throw ValidationException::withMessages([
                'status' => 'Dokumen penyusunan hanya dapat dibantu upload oleh admin ketika status publikasi berada pada tahap Penyusunan/Revisi.',
            ]);
        }
    }

    protected function authorCompletion($publicationTeam): array
    {
        $publicationTeam->loadMissing('publication.tenant');

        $documents = $publicationTeam->relationLoaded('documents')
            ? $publicationTeam->documents
            : $publicationTeam->documents()->get();

        $completion = [
            'naskah_pdf' => $documents->where('document_type', 'naskah_pdf')->isNotEmpty(),
            'naskah_zip' => $documents->where('document_type', 'naskah_zip')->isNotEmpty(),
            'sprp' => $publicationTeam->sprp()->exists(),
        ];

        $isProvinsi = optional(optional($publicationTeam->publication)->tenant)->type === 'provinsi';

        if ($isProvinsi) {
            $completion['infografis'] = $documents->where('document_type', 'infografis')->isNotEmpty();
            $completion['daftar_tabel_gambar'] = $documents->where('document_type', 'daftar_tabel_gambar')->isNotEmpty();
        }

        return $completion;
    }

    protected function validateDocumentInput(Request $request, string $type, array $rules): void
    {
        $hasLink = $request->filled('external_url');
        $hasFile = $type === 'infografis'
            ? $request->hasFile('files')
            : $request->hasFile('file');

        if (!$hasLink && !$hasFile) {
            throw ValidationException::withMessages([
                'file' => 'Isi salah satu: upload file atau input link dokumen.',
            ]);
        }

        if ($hasLink) {
            return;
        }

        $request->validate($rules['rules'], $rules['messages']);
    }

    protected function storeLinkedDocument($publicationTeam, Publication $publication, string $type, string $externalUrl, ?string $notes = null): void
    {
        $nextVersion = (PublicationDocument::where('publication_team_id', $publicationTeam->id)
            ->where('document_type', $type)
            ->max('version') ?? 0) + 1;

        $document = PublicationDocument::create([
            'publication_team_id' => $publicationTeam->id,
            'publication_id' => $publication->id,
            'uploaded_by' => Auth::id(),
            'document_type' => $type,
            'version' => $nextVersion,
            'source_type' => 'link',
            'file_path' => 'external-link',
            'file_original_name' => 'Link ' . (new PublicationDocument(['document_type' => $type]))->document_type_label,
            'mime_type' => 'text/uri-list',
            'file_size' => null,
            'external_url' => $externalUrl,
            'notes' => $notes,
            'uploaded_at' => now(),
        ]);

        if ($type === 'naskah_pdf') {
            PublicationDraft::create([
                'publication_team_id' => $publicationTeam->id,
                'publication_id' => $publication->id,
                'uploaded_by' => Auth::id(),
                'version' => $nextVersion,
                'source_type' => 'link',
                'file_path' => 'external-link',
                'external_url' => $externalUrl,
                'file_original_name' => $document->file_original_name,
                'mime_type' => 'text/uri-list',
                'notes' => $notes,
                'submitted_at' => now(),
            ]);
        }
    }

    protected function documentUploadRules(): array
    {
        return [
            'naskah_pdf' => [
                'rules' => ['file' => ['required', 'file', 'mimes:pdf', 'max:20480']],
                'messages' => [
                    'file.required' => 'Naskah PDF wajib diunggah atau isi link dokumen.',
                    'file.mimes' => 'Naskah publikasi harus berformat PDF.',
                    'file.max' => 'Ukuran Naskah PDF maksimal 20MB.',
                ],
                'success' => 'Naskah publikasi PDF berhasil disimpan.',
            ],
            'naskah_zip' => [
                'rules' => ['file' => ['required', 'file', 'mimes:zip,rar', 'max:51200']],
                'messages' => [
                    'file.required' => 'Naskah RAR/ZIP wajib diunggah atau isi link dokumen.',
                    'file.mimes' => 'Naskah harus berformat RAR atau ZIP.',
                    'file.max' => 'Ukuran Naskah RAR/ZIP maksimal 50MB.',
                ],
                'success' => 'Naskah publikasi RAR/ZIP berhasil disimpan.',
            ],
            'infografis' => [
                'rules' => [
                    'files' => ['required', 'array', 'min:1'],
                    'files.*' => ['required', 'file', 'mimes:jpg,jpeg', 'max:500'],
                ],
                'messages' => [
                    'files.required' => 'Minimal satu file infografis wajib diunggah atau isi link dokumen.',
                    'files.*.mimes' => 'Infografis harus berformat JPG atau JPEG.',
                    'files.*.max' => 'Ukuran setiap infografis maksimal 500KB.',
                ],
                'success' => 'File/link infografis berhasil disimpan.',
            ],
            'daftar_tabel_gambar' => [
                'rules' => ['file' => ['required', 'file', 'mimes:xls,xlsx,csv', 'max:10240']],
                'messages' => [
                    'file.required' => 'File daftar tabel dan gambar wajib diunggah atau isi link dokumen.',
                    'file.mimes' => 'Daftar tabel dan gambar harus berformat XLS, XLSX, atau CSV.',
                    'file.max' => 'Ukuran file daftar tabel dan gambar maksimal 10MB.',
                ],
                'success' => 'File/link daftar tabel dan gambar berhasil disimpan.',
            ],
        ];
    }

    public static function remainingDays(?Carbon $date): int
    {
        if (!$date) {
            return 0;
        }

        return max(0, now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false));
    }
}
