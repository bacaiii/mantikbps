<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\InspectionGuideline;
use App\Models\KnowledgeLink;
use App\Models\PublicationDocument;
use App\Models\PublicationDraft;
use App\Models\PublicationReview;
use App\Models\PublicationReviewSlide;
use App\Models\PublicationSprp;
use App\Models\PublicationTeam;
use App\Models\PublicationTeamAssignmentHistory;
use App\Support\PublicationNotifier;
use App\Support\EmployeeWorkReportPdf;
use App\Support\ExternalReleaseDocumentDownloader;
use App\Support\PortableZipWriter;
use App\Support\RevisionInspectionPdf;
use App\Support\SprpPackagePdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeTaskController extends Controller
{
    public function knowledge()
    {
        $knowledgeLinks = KnowledgeLink::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('tenant_id')
                    ->orWhere('tenant_id', Auth::user()->tenant_id);
            })
            ->latest()
            ->paginate(9);

        return view('employee.knowledge.index', compact('knowledgeLinks'));
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = PublicationTeam::with(['publication.tenant', 'assignments.user', 'documents'])
            ->select('publication_teams.*')
            ->leftJoin('publications', 'publications.id', '=', 'publication_teams.publication_id')
            ->whereHas('assignments', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        $yearOptions = range(now()->year - 3, now()->year + 1);
        $selectedYear = (int) $request->input('tahun', now()->year);
        if (!in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = now()->year;
        }
        $query->where('publications.tahun', $selectedYear);

        $monthOptions = collect(range(1, 12))->mapWithKeys(fn ($month) => [
            $month => \Illuminate\Support\Carbon::createFromDate($selectedYear, $month, 1)->translatedFormat('F')
        ]);
        $selectedMonth = $request->input('bulan');

        if ($selectedMonth !== null && $selectedMonth !== '') {
            $query->whereMonth('publications.jadwal_rilis', (int) $selectedMonth);
        }

        $taskOptions = [
            'penyusun' => 'Penyusun Naskah',
            'konten' => 'Pemeriksa Konten',
            'layout' => 'Pemeriksa Layout',
            'infografis' => 'Operator Infografis',
            'website' => 'Operator Website',
        ];
        $selectedTask = $request->input('task');

        if (!array_key_exists((string) $selectedTask, $taskOptions)) {
            $selectedTask = null;
        }

        if ($selectedTask) {
            $roles = match ($selectedTask) {
                'penyusun' => ['penyusun_naskah'],
                'konten' => ['ketua_pemeriksa_konten', 'anggota_pemeriksa_konten'],
                'layout' => ['ketua_pemeriksa_layout', 'anggota_pemeriksa_layout'],
                'infografis' => ['operator_infografis'],
                'website' => ['operator_website'],
                default => [],
            };

            if (!empty($roles)) {
                $query->whereHas('assignments', function ($q) use ($user, $roles) {
                    $q->where('user_id', $user->id)
                        ->whereIn('assignment_role', $roles);
                });
            }
        }

        $statusOptions = [
            'penyusunan' => 'Penyusunan',
            'pemeriksaan_konten' => 'Pemeriksaan Konten',
            'pemeriksaan_layout' => 'Pemeriksaan Layout',
            'pemeriksaan_infografis' => 'Pemeriksaan Infografis',
            'persetujuan_pimpinan' => 'Persetujuan Pimpinan',
            'operator_website' => 'Finalisasi Rilis',
            'siap_rilis' => 'Siap Rilis',
        ];
        $selectedStatus = $request->input('status');

        if (!array_key_exists((string) $selectedStatus, $statusOptions)) {
            $selectedStatus = null;
        }

        if ($selectedStatus) {
            $query->where('publications.status', $selectedStatus);
        }

        if ($request->filled('q')) {
            $query->where('publications.nama_publikasi', 'like', '%' . $request->q . '%');
        }

        $sort = $request->query('sort_by');
        $direction = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'no' => $query->orderBy('publication_teams.id', $direction),
            'tim' => $query->orderBy('publication_teams.name', $direction),
            'publikasi' => $query->orderBy('publications.nama_publikasi', $direction),
            'tanggal_rilis' => $query->orderBy('publications.jadwal_rilis', $direction),
            'tugas' => $query->orderByRaw(
                '(select min(assignment_role) from publication_team_assignments where publication_team_assignments.publication_team_id = publication_teams.id and publication_team_assignments.user_id = ?) ' . $direction,
                [$user->id]
            ),
            'status' => $query->orderBy('publications.status', $direction),
            default => $query->latest('publication_teams.created_at'),
        };

        $teams = $query->paginate(10)->withQueryString();

        $currentUnlockedAuthorTeamId = $this->currentUnlockedAuthorTeamId((int) $user->id);
        $currentUnlockedAuthorTeam = $currentUnlockedAuthorTeamId
            ? PublicationTeam::with('publication')->find($currentUnlockedAuthorTeamId)
            : null;

        return view('employee.tasks.index', compact(
            'teams',
            'currentUnlockedAuthorTeamId',
            'currentUnlockedAuthorTeam',
            'yearOptions',
            'selectedYear',
            'monthOptions',
            'selectedMonth',
            'taskOptions',
            'selectedTask',
            'statusOptions',
            'selectedStatus'
        ));
    }


    public function readyRelease(Request $request)
    {
        $user = Auth::user();
        $currentYear = now()->year;
        $yearOptions = range($currentYear - 3, $currentYear + 1);
        $selectedYear = (int) $request->input('tahun', $currentYear);

        if (!in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = $currentYear;
        }

        $selectedMonth = $request->input('bulan');
        $selectedAssignment = $request->input('penugasan');
        $monthOptions = collect(range(1, 12))->mapWithKeys(fn ($month) => [
            $month => \Illuminate\Support\Carbon::createFromDate($selectedYear, $month, 1)->translatedFormat('F')
        ]);

        $assignmentRoleOptions = [
            'penyusun_naskah' => 'Penyusun Naskah',
            'ketua_pemeriksa_konten' => 'Ketua Pemeriksa Konten',
            'anggota_pemeriksa_konten' => 'Anggota Pemeriksa Konten',
            'ketua_pemeriksa_layout' => 'Ketua Pemeriksa Layout',
            'anggota_pemeriksa_layout' => 'Anggota Pemeriksa Layout',
            'operator_infografis' => 'Operator Infografis',
            'operator_website' => 'Operator Website',
        ];

        if (!array_key_exists((string) $selectedAssignment, $assignmentRoleOptions)) {
            $selectedAssignment = null;
        }

        $query = PublicationTeam::with([
                'publication.tenant',
                'assignments.user',
                'documents.uploader',
                'sprp.submittedBy',
            ])
            ->select('publication_teams.*')
            ->join('publications', 'publications.id', '=', 'publication_teams.publication_id')
            ->where('publications.status', 'siap_rilis')
            ->whereYear('publications.jadwal_rilis', $selectedYear)
            ->whereHas('assignments', fn ($q) => $q->where('user_id', $user->id));

        if ($selectedMonth !== null && $selectedMonth !== '') {
            $query->whereMonth('publications.jadwal_rilis', (int) $selectedMonth);
        }

        if ($request->filled('q')) {
            $query->where('publications.nama_publikasi', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('kategori')) {
            $query->where('publications.kategori', $request->kategori);
        }

        if ($selectedAssignment) {
            $query->whereHas('assignments', function ($q) use ($user, $selectedAssignment) {
                $q->where('user_id', $user->id)
                    ->where('assignment_role', $selectedAssignment);
            });
        }

        $teams = $query
            ->orderByDesc('publications.ready_for_release_at')
            ->orderByDesc('publications.updated_at')
            ->paginate(8)
            ->withQueryString();

        return view('employee.ready_release.index', compact(
            'teams',
            'yearOptions',
            'selectedYear',
            'monthOptions',
            'selectedMonth',
            'assignmentRoleOptions',
            'selectedAssignment'
        ));
    }

    public function workReport(PublicationTeam $publicationTeam)
    {
        $report = $this->buildWorkReport($publicationTeam);

        return view('employee.ready_release.work_report', $report);
    }

    public function workReportPdf(PublicationTeam $publicationTeam)
    {
        $report = $this->buildWorkReport($publicationTeam);
        $publication = $report['publication'];
        $user = Auth::user();
        $rolesText = $report['myAssignments']->pluck('assignment_role_label')->implode(', ');

        $pdfData = [
            'employee' => [
                'name' => $user->name ?? '-',
                'email' => $user->email ?: '-',
                'region' => optional($user->tenant)->wilayah ?? '-',
                'roles' => $rolesText ?: '-',
            ],
            'publication' => [
                'title' => $publication->nama_publikasi ?? '-',
                'team' => $publicationTeam->name ?? '-',
                'region' => $publication->wilayah ?? optional($publication->tenant)->wilayah ?? '-',
                'category' => $publication->kategori ?? '-',
                'period' => $publication->periode ?? '-',
                'accuracy' => $publication->akurasi_publikasi ?? '-',
                'release_date' => $publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('d F Y') : '-',
                'status' => $publication->status_label ?? '-',
                'year' => (string) ($publication->tahun ?? '-'),
            ],
            'summary' => [
                'total_activities' => $report['activities']->count(),
                'uploaded_documents' => $report['myDocuments']->count(),
                'review_count' => $report['myReviews']->count(),
                'assignment_count' => $report['myAssignments']->count(),
            ],
            'activities' => $report['activities']->map(fn ($activity) => [
                'tanggal' => (string) ($activity['tanggal'] ?? '-'),
                'aktivitas' => (string) ($activity['aktivitas'] ?? '-'),
                'keterangan' => (string) ($activity['keterangan'] ?? '-'),
            ])->values()->all(),
        ];

        $fileName = 'rekap-hasil-kerja-' . $publicationTeam->id . '.pdf';

        return response(EmployeeWorkReportPdf::make($pdfData), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function downloadReleasePackage(PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);
        $this->mustHaveRole($publicationTeam, ['operator_website']);

        $publicationTeam->load([
            'publication.tenant',
            'assignments.user',
            'documents.uploader',
            'sprp.submittedBy',
        ]);

        $publication = $publicationTeam->publication;

        abort_unless($publication && $publication->status === 'siap_rilis', 404, 'Paket rilis hanya tersedia untuk publikasi yang sudah siap rilis.');

        $documentsByType = $publicationTeam->documents
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
        $zipPath = tempnam($temporaryDirectory, 'paket_rilis_');

        try {
            $zip = new PortableZipWriter($zipPath);
            $zip->addFromString($baseFolder . '/README_Paket_Rilis.txt', $this->releasePackageReadme($publicationTeam));
            $externalDownloader = new ExternalReleaseDocumentDownloader();
            $externalDownloadNotes = [];

            if ($publicationTeam->sprp) {
                $zip->addFromString($baseFolder . '/00_Form_SPRP/SPRP_Digital.pdf', SprpPackagePdf::make($publicationTeam->sprp));
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

    public function downloadTemplate(DocumentTemplate $documentTemplate)
    {
        abort_if($documentTemplate->tenant_id !== Auth::user()->tenant_id, 403, 'Anda tidak memiliki akses ke template ini.');

        $realPath = $this->resolvePublicStoragePath($documentTemplate->file_path);

        if (!$realPath) {
            return redirect()
                ->back()
                ->with('error', 'File template surat tidak ditemukan di folder storage. Silakan upload ulang template melalui Admin > Kelola Pedoman Pemeriksaan > Template Surat.');
        }

        return response()->download(
            $realPath,
            $documentTemplate->file_original_name ?: basename($realPath)
        );
    }

    public function downloadDocument(PublicationDocument $publicationDocument)
    {
        $publicationTeam = $publicationDocument->publicationTeam;

        abort_unless($publicationTeam, 404, 'Data tim kerja dokumen tidak ditemukan.');

        abort_unless(
            $publicationTeam->assignments()
                ->where('user_id', Auth::id())
                ->exists(),
            403,
            'Anda tidak memiliki akses ke dokumen ini.'
        );

        if ($publicationDocument->is_link) {
            return redirect()->away($publicationDocument->external_url);
        }

        $realPath = $this->resolvePublicStoragePath($publicationDocument->file_path);

        if (!$realPath) {
            return redirect()
                ->back()
                ->with('error', 'File dokumen penyusun tidak ditemukan di folder storage. Silakan unggah ulang file tersebut.');
        }

        return response()->download(
            $realPath,
            $publicationDocument->file_original_name ?: basename($realPath)
        );
    }

    public function downloadReviewRevisionPdf(PublicationReview $publicationReview)
    {
        $report = $this->buildReviewRevisionReport($publicationReview);

        $fileName = 'detail-revisi-pemeriksaan-' . $publicationReview->id . '.pdf';

        return response(RevisionInspectionPdf::make($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function downloadReviewRevisionExcel(PublicationReview $publicationReview)
    {
        return $this->downloadReviewRevisionPdf($publicationReview);
    }

    protected function buildReviewRevisionReport(PublicationReview $publicationReview): array
    {
        $publicationReview->load(['publication.tenant', 'publicationTeam.assignments.user', 'reviewer', 'draft']);
        $publicationTeam = $publicationReview->publicationTeam;

        abort_unless($publicationTeam, 404, 'Data tim kerja pemeriksaan tidak ditemukan.');

        abort_unless(
            $publicationTeam->assignments()
                ->where('user_id', Auth::id())
                ->exists(),
            403,
            'Anda tidak memiliki akses ke detail revisi ini.'
        );

        abort_unless($publicationReview->result === 'revisi', 404, 'File PDF hanya tersedia untuk riwayat revisi.');

        $publication = $publicationReview->publication;
        $reviewTypeLabel = $publicationReview->review_type_label;
        $draftVersion = data_get($publicationReview->checklist, 'draft_version', optional($publicationReview->draft)->version ?? '-');
        $draftLabel = $draftVersion && $draftVersion !== '-'
            ? 'Versi ' . $draftVersion
            : '-';
        $finalNotes = data_get($publicationReview->checklist, 'final_notes', $publicationReview->notes) ?: '-';

        $slides = collect(data_get($publicationReview->checklist, 'slides', []))
            ->map(function ($slide) use ($publicationReview) {
                $failedItems = collect(data_get($slide, 'failed_items', []))
                    ->map(function ($failedItem) use ($slide) {
                        return [
                            'anatomy_section' => data_get($failedItem, 'anatomy_section', data_get($slide, 'anatomy_section', '-')),
                            'sub_anatomy' => data_get($failedItem, 'sub_anatomy', data_get($slide, 'sub_anatomy', '-')),
                            'requirement_detail' => data_get($failedItem, 'requirement_detail', '-'),
                        ];
                    })
                    ->filter(fn ($failedItem) => trim((string) $failedItem['requirement_detail']) !== '')
                    ->values()
                    ->all();

                return [
                    'anatomy_section' => data_get($slide, 'anatomy_section', '-'),
                    'sub_anatomy' => data_get($slide, 'sub_anatomy', '-'),
                    'notes' => data_get($slide, 'notes') ?: 'Tidak ada catatan tambahan.',
                    'reviewer_name' => data_get($slide, 'reviewer_name', optional($publicationReview->reviewer)->name ?? '-'),
                    'reviewer_role' => data_get($slide, 'reviewer_role', 'Pemeriksa'),
                    'failed_items' => $failedItems,
                ];
            })
            ->filter(fn ($slide) => count($slide['failed_items']) > 0)
            ->values();

        $failedCount = $slides->sum(fn ($slide) => count($slide['failed_items']));
        $firstSlide = $slides->first();

        return [
            'publication' => [
                'title' => optional($publication)->nama_publikasi ?? '-',
                'team' => optional($publicationTeam)->name ?? '-',
                'region' => optional(optional($publication)->tenant)->wilayah ?? optional($publication)->wilayah ?? '-',
                'status' => optional($publication)->status_label ?? '-',
            ],
            'review' => [
                'type_label' => $reviewTypeLabel,
                'draft_label' => $draftLabel,
                'result_label' => $publicationReview->result_label,
                'reviewer' => optional($publicationReview->reviewer)->name ?? '-',
                'reviewer_role' => data_get($firstSlide, 'reviewer_role', 'Pemeriksa'),
                'reviewed_at' => optional($publicationReview->reviewed_at)->format('d-m-Y H:i') ?: '-',
                'final_notes' => $finalNotes,
            ],
            'summary' => [
                'slide_count' => $slides->count(),
                'failed_count' => $failedCount,
            ],
            'slides' => $slides->all(),
        ];
    }

    public function show(Request $request, PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);

        $publicationTeam->load([
            'publication.tenant',
            'assignments.user',
            'documents.uploader',
            'sprp.submittedBy',
            'drafts.uploader',
            'drafts.reviews.reviewer',
            'drafts.reviewSlides.reviewer',
        ]);

        $myRoles = $this->myAssignmentRoles($publicationTeam);
        $selectedAssignmentRole = $request->query('role');

        if ($selectedAssignmentRole) {
            abort_unless(in_array($selectedAssignmentRole, $myRoles, true), 403, 'Anda tidak memiliki akses pada peran tugas ini.');

            $myRoles = [$selectedAssignmentRole];
        }

        $latestDraft = $publicationTeam->drafts()
            ->with(['reviews.reviewer', 'reviewSlides.reviewer'])
            ->latest('version')
            ->first();

        $documentsByType = $publicationTeam->documents
            ->sortByDesc('version')
            ->groupBy('document_type');

        $latestDocuments = $documentsByType->map(fn ($items) => $items->sortByDesc('version')->first());

        $documentTemplates = DocumentTemplate::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('template_type', ['surat_persetujuan_rilis'])
            ->get()
            ->keyBy('template_type');

        $sprp = $publicationTeam->sprp;
        $yearOptions = range(now()->year - 3, now()->year + 3);

        $completion = $this->authorCompletion($publicationTeam);
        $authorSubmitTargetStage = $this->nextAuthorSubmitStage($publicationTeam->publication);
        $authorSubmitTargetLabel = $this->stageLabel($authorSubmitTargetStage);
        $isRevisionReturn = !empty($publicationTeam->publication->revision_return_stage);

        $contentGuidelineSlides = $this->guidelineSlides('konten');
        $layoutGuidelineSlides = $this->guidelineSlides('layout');

        $contentSavedSlides = $this->savedSlidesForCurrentUser($publicationTeam, $latestDraft, 'konten');
        $layoutSavedSlides = $this->savedSlidesForCurrentUser($publicationTeam, $latestDraft, 'layout');

        $examinationDocuments = collect([
            'naskah_pdf' => $latestDocuments->get('naskah_pdf'),
            'naskah_zip' => $latestDocuments->get('naskah_zip'),
            'daftar_tabel_gambar' => $latestDocuments->get('daftar_tabel_gambar'),
        ])->filter();

        $authorWorkUnlocked = !in_array('penyusun_naskah', $myRoles, true)
            || $this->isAuthorWorkUnlocked($publicationTeam, (int) Auth::id());

        $blockingAuthorTeam = $authorWorkUnlocked
            ? null
            : $this->blockingAuthorTeam($publicationTeam, (int) Auth::id());

        if (!$authorWorkUnlocked) {
            $blockingTitle = optional(optional($blockingAuthorTeam)->publication)->nama_publikasi;

            return redirect()
                ->route('employee.tasks.index')
                ->with('error', $blockingTitle
                    ? 'Penyusunan sebelumnya belum selesai. Selesaikan dan submit publikasi "' . $blockingTitle . '" terlebih dahulu.'
                    : 'Penyusunan sebelumnya belum selesai. Selesaikan dan submit publikasi sebelumnya terlebih dahulu.');
        }

        return view('employee.tasks.show', compact(
            'publicationTeam',
            'myRoles',
            'latestDraft',
            'documentsByType',
            'latestDocuments',
            'documentTemplates',
            'sprp',
            'yearOptions',
            'completion',
            'authorSubmitTargetStage',
            'authorSubmitTargetLabel',
            'isRevisionReturn',
            'contentGuidelineSlides',
            'layoutGuidelineSlides',
            'contentSavedSlides',
            'layoutSavedSlides',
            'examinationDocuments',
            'authorWorkUnlocked',
            'blockingAuthorTeam'
        ));
    }

    public function uploadDocument(Request $request, PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);
        $this->mustHaveRole($publicationTeam, ['penyusun_naskah']);
        $this->ensurePublicationInDraftStage($publicationTeam);
        $this->ensureAuthorWorkUnlocked($publicationTeam);

        $type = $request->input('document_type');

        $request->validate([
            'document_type' => ['required', Rule::in(array_keys($this->documentUploadRules()))],
            'notes' => ['nullable', 'string'],
            'external_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $rules = $this->documentUploadRules()[$type];
        $this->validateDocumentInput($request, $type, $rules);

        DB::transaction(function () use ($publicationTeam, $type, $request) {
            $publication = $publicationTeam->publication;

            if ($request->filled('external_url')) {
                $this->storeLinkedDocument($publicationTeam, $publication, $type, $request->input('external_url'), $request->input('notes'));
                return;
            }

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
        });

        return back()->with('success', $rules['success']);
    }

    public function saveSprp(Request $request, PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);
        $this->mustHaveRole($publicationTeam, ['penyusun_naskah']);
        $this->ensurePublicationInDraftStage($publicationTeam);
        $this->ensureAuthorWorkUnlocked($publicationTeam);

        $publication = $publicationTeam->publication;

        $validated = $request->validate([
            'rancangan_perwajahan' => ['required', Rule::in(['Seksi Diseminasi dan Layanan Statistik', 'subject matter'])],
            'publikasi_baru' => ['required', Rule::in(['1', '0'])],
            'ukuran' => ['required', 'string', 'max:100'],
            'orientasi' => ['required', Rule::in(['Portrait', 'Landscape'])],
            'terbitan_ke' => ['required', 'string', 'max:100'],
            'tahun_pertama_terbit' => ['required', 'integer', 'min:' . (now()->year - 3), 'max:' . (now()->year + 3)],
            'diterbitkan_untuk' => ['required', Rule::in(['Eksternal', 'Internal'])],
            'jumlah_halaman_romawi' => ['required', 'regex:/^[A-Za-z]+$/', 'max:50'],
            'jumlah_halaman_arab' => ['required', 'regex:/^[0-9]+$/', 'max:50'],
            'kerja_sama_luar_bps' => ['required', Rule::in(['1', '0'])],
            'bahasa' => ['required', 'array', 'min:1'],
            'bahasa.*' => [Rule::in(['Indonesia', 'Inggris'])],
        ], [
            'rancangan_perwajahan.required' => 'Rancangan perwajahan wajib dipilih.',
            'publikasi_baru.required' => 'Status publikasi baru wajib dipilih.',
            'ukuran.required' => 'Ukuran publikasi wajib dipilih.',
            'orientasi.required' => 'Orientasi publikasi wajib dipilih.',
            'terbitan_ke.required' => 'Terbitan yang ke wajib diisi.',
            'tahun_pertama_terbit.required' => 'Tahun pertama kali terbit wajib dipilih.',
            'diterbitkan_untuk.required' => 'Tujuan penerbitan wajib dipilih.',
            'jumlah_halaman_romawi.required' => 'Jumlah halaman romawi wajib diisi.',
            'jumlah_halaman_romawi.regex' => 'Jumlah halaman romawi hanya boleh berisi huruf.',
            'jumlah_halaman_arab.required' => 'Jumlah halaman arab wajib diisi.',
            'jumlah_halaman_arab.regex' => 'Jumlah halaman arab hanya boleh berisi angka.',
            'kerja_sama_luar_bps.required' => 'Pilihan kerja sama luar BPS wajib diisi.',
            'bahasa.required' => 'Minimal satu bahasa wajib dipilih.',
        ]);

        PublicationSprp::updateOrCreate(
            ['publication_team_id' => $publicationTeam->id],
            [
                'publication_id' => $publication->id,
                'submitted_by' => Auth::id(),
                'bidang_bagian' => $publicationTeam->name,
                'rancangan_perwajahan' => $validated['rancangan_perwajahan'],
                'judul_publikasi' => $publication->nama_publikasi,
                'publikasi_baru' => (bool) $validated['publikasi_baru'],
                'ukuran' => $validated['ukuran'],
                'orientasi' => $validated['orientasi'],
                'frekuensi_terbit' => $publication->periode,
                'terbitan_ke' => $validated['terbitan_ke'],
                'tahun_pertama_terbit' => $validated['tahun_pertama_terbit'],
                'diterbitkan_untuk' => $validated['diterbitkan_untuk'],
                'kategori_rilis' => $publication->kategori,
                'tanggal_rilis' => $publication->jadwal_rilis,
                'jumlah_halaman_romawi' => $validated['jumlah_halaman_romawi'],
                'jumlah_halaman_arab' => $validated['jumlah_halaman_arab'],
                'kerja_sama_luar_bps' => (bool) $validated['kerja_sama_luar_bps'],
                'bahasa' => $validated['bahasa'],
                'submitted_at' => now(),
            ]
        );

        return back()->with('success', 'Form SPRP berhasil disimpan.');
    }

    public function submitAuthorWork(PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);
        $this->mustHaveRole($publicationTeam, ['penyusun_naskah']);
        $this->ensurePublicationInDraftStage($publicationTeam);
        $this->ensureAuthorWorkUnlocked($publicationTeam);

        $completion = $this->authorCompletion($publicationTeam);

        if (in_array(false, $completion, true)) {
            throw ValidationException::withMessages([
                'submit' => 'Dokumen penyusunan belum lengkap. Lengkapi semua file utama dan form SPRP sebelum menekan Submit.',
            ]);
        }

        $targetStage = $this->nextAuthorSubmitStage($publicationTeam->publication);
        $targetLabel = $this->stageLabel($targetStage);
        $isRevisionSubmit = !empty($publicationTeam->publication->revision_return_stage);

        $publicationForLeaderNotification = null;

        DB::transaction(function () use ($publicationTeam, $targetStage, $targetLabel, $isRevisionSubmit, &$publicationForLeaderNotification) {
            $publication = $publicationTeam->publication()->lockForUpdate()->first();
            $targetStage = $this->nextAuthorSubmitStage($publication);
            $targetLabel = $this->stageLabel($targetStage);
            $isRevisionSubmit = !empty($publication->revision_return_stage);

            $this->ensureLatestPdfDraftExists($publicationTeam, $publication);

            $updateData = [
                'status' => $targetStage,
                'revision_return_stage' => null,
                'draft_submitted_at' => now(),
            ];

            if ($targetStage === 'pemeriksaan_layout') {
                $updateData['layout_review_started_at'] = now();
                $updateData['layout_review_finished_at'] = null;
            } elseif ($targetStage === 'pemeriksaan_infografis') {
                $updateData['infographic_review_started_at'] = now();
                $updateData['infographic_review_finished_at'] = null;
            } elseif ($targetStage === 'persetujuan_pimpinan') {
                $updateData['leadership_approved_at'] = null;
            } else {
                $updateData['content_review_started_at'] = now();
                $updateData['content_review_finished_at'] = null;
                $updateData['layout_review_started_at'] = null;
                $updateData['layout_review_finished_at'] = null;
                $updateData['infographic_review_started_at'] = null;
                $updateData['infographic_review_finished_at'] = null;
                $updateData['leadership_approved_at'] = null;
                $updateData['website_packaged_at'] = null;
                $updateData['ready_for_release_at'] = null;
            }

            $publication->update($updateData);

            $revisionSubmitAction = $isRevisionSubmit
                ? 'Submit ulang revisi ' . $this->stageShortLabel($targetStage)
                : 'Submit dokumen penyusunan';

            PublicationTeamAssignmentHistory::create([
                'publication_id' => $publication->id,
                'action' => $revisionSubmitAction,
                'old_value' => 'penyusunan',
                'new_value' => $targetStage,
                'notes' => $isRevisionSubmit
                    ? 'Tim penyusun menekan Submit ulang. Publikasi dikirim kembali ke ' . $targetLabel . ' sesuai tahap pemeriksa yang meminta revisi.'
                    : 'Tim penyusun menekan tombol Submit setelah dokumen dan SPRP dilengkapi.',
                'changed_by' => Auth::id(),
            ]);

            if ($targetStage === 'persetujuan_pimpinan') {
                $publicationForLeaderNotification = $publication->fresh();
            }
        });

        if ($publicationForLeaderNotification) {
            PublicationNotifier::notifyLeadersForApproval($publicationForLeaderNotification);
        }

        return back()->with('success', 'Dokumen penyusunan berhasil disubmit. Publikasi masuk tahap ' . $targetLabel . '.');
    }

    protected function nextAuthorSubmitStage($publication): string
    {
        return in_array($publication->revision_return_stage, [
            'pemeriksaan_konten',
            'pemeriksaan_layout',
            'pemeriksaan_infografis',
            'persetujuan_pimpinan',
        ], true)
            ? $publication->revision_return_stage
            : 'pemeriksaan_konten';
    }

    protected function stageLabel(string $stage): string
    {
        return match ($stage) {
            'pemeriksaan_layout' => 'Pemeriksaan Layout',
            'pemeriksaan_konten' => 'Pemeriksaan Konten',
            'pemeriksaan_infografis' => 'Pemeriksaan Infografis',
            'persetujuan_pimpinan' => 'Persetujuan Pimpinan',
            'operator_website' => 'Finalisasi Rilis',
            'siap_rilis' => 'Siap Rilis',
            'pengajuan_rilis' => 'Pengajuan Rilis',
            default => 'Pemeriksaan Konten',
        };
    }

    protected function stageShortLabel(string $stage): string
    {
        return match ($stage) {
            'pemeriksaan_layout' => 'LAYOUT',
            'pemeriksaan_infografis' => 'INFOGRAFIS',
            'persetujuan_pimpinan' => 'PIMPINAN',
            default => 'KONTEN',
        };
    }

    protected function ensureLatestPdfDraftExists(PublicationTeam $publicationTeam, $publication): void
    {
        $latestPdf = $publicationTeam->documents()
            ->where('document_type', 'naskah_pdf')
            ->latest('version')
            ->first();

        if (!$latestPdf) {
            return;
        }

        $alreadyDrafted = $publicationTeam->drafts()
            ->where('file_path', $latestPdf->file_path)
            ->exists();

        if ($alreadyDrafted) {
            return;
        }

        $nextDraftVersion = ((int) $publicationTeam->drafts()->max('version')) + 1;

        PublicationDraft::create([
            'publication_team_id' => $publicationTeam->id,
            'publication_id' => $publication->id,
            'uploaded_by' => $latestPdf->uploaded_by,
            'version' => max($nextDraftVersion, (int) $latestPdf->version),
            'source_type' => $latestPdf->source_type ?? 'file',
            'file_path' => $latestPdf->file_path,
            'external_url' => $latestPdf->external_url,
            'file_original_name' => $latestPdf->file_original_name,
            'mime_type' => $latestPdf->mime_type,
            'notes' => $latestPdf->notes,
            'submitted_at' => now(),
        ]);
    }

    public function uploadDraft(Request $request, PublicationTeam $publicationTeam)
    {
        if ($request->hasFile('draft_file')) {
            $request->files->set('file', $request->file('draft_file'));
        }

        $request->merge(['document_type' => 'naskah_pdf']);

        return $this->uploadDocument($request, $publicationTeam);
    }

    public function saveReviewSlide(Request $request, PublicationTeam $publicationTeam, string $type)
    {
        $this->authorizeTeam($publicationTeam);
        $type = $this->normalizeReviewType($type);
        $this->ensureReviewAccess($publicationTeam, $type);
        $this->ensureReviewStage($publicationTeam, $type);

        $latestDraft = $this->latestDraftOrFail($publicationTeam);
        $section = trim((string) $request->input('anatomy_section'));
        $subAnatomy = trim((string) $request->input('sub_anatomy'));

        $guidelines = $this->activeGuidelines($type)
            ->where('anatomy_section', $section)
            ->where('inspection_item', $subAnatomy)
            ->values();

        if ($guidelines->isEmpty()) {
            throw ValidationException::withMessages([
                'anatomy_section' => 'Anatomi atau sub-anatomi pemeriksaan tidak ditemukan atau belum aktif.',
            ]);
        }

        $validated = $request->validate([
            'anatomy_section' => ['required', 'string', 'max:255'],
            'sub_anatomy' => ['required', 'string', 'max:255'],
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', Rule::in(['ya', 'tidak'])],
            'notes' => ['nullable', 'string'],
        ], [
            'answers.*.in' => 'Pilihan pemeriksaan hanya boleh Ya atau Tidak.',
            'sub_anatomy.required' => 'Sub-anatomi pemeriksaan wajib dipilih.',
        ]);

        $validatedAnswers = collect($validated['answers'] ?? [])
            ->filter(fn ($answer) => in_array($answer, ['ya', 'tidak'], true));

        $answers = $guidelines->map(function ($guideline) use ($validatedAnswers) {
            return [
                'guideline_id' => $guideline->id,
                'anatomy_section' => $guideline->anatomy_section,
                'sub_anatomy' => $guideline->inspection_item,
                'requirement_detail' => $guideline->requirement_detail,
                'answer' => $validatedAnswers->get($guideline->id),
            ];
        })->values()->all();

        $totalItems = $guidelines->count();
        $savedItems = collect($answers)
            ->filter(fn ($item) => in_array($item['answer'] ?? null, ['ya', 'tidak'], true))
            ->count();

        $slideKey = $this->slideStorageKey($section, $subAnatomy);

        DB::transaction(function () use ($latestDraft, $publicationTeam, $type, $slideKey, $guidelines, $answers, $validated) {
            $existingSlides = PublicationReviewSlide::where('publication_draft_id', $latestDraft->id)
                ->where('publication_team_id', $publicationTeam->id)
                ->where('review_type', $type)
                ->where('anatomy_section', $slideKey)
                ->orderByDesc('saved_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            $reviewSlide = $existingSlides->first() ?: new PublicationReviewSlide([
                'publication_draft_id' => $latestDraft->id,
                'publication_team_id' => $publicationTeam->id,
                'review_type' => $type,
                'anatomy_section' => $slideKey,
            ]);

            $keepId = $reviewSlide->exists ? $reviewSlide->id : null;

            // Pemeriksaan konten/layout adalah satu form bersama untuk tim.
            // Data lama yang sempat tersimpan per-user dihapus dulu agar saat reviewer_id
            // diganti menjadi penyimpan terakhir tidak menabrak unique index lama.
            if ($existingSlides->count() > 1) {
                PublicationReviewSlide::whereIn(
                    'id',
                    $existingSlides
                        ->pluck('id')
                        ->when($keepId, fn ($ids) => $ids->reject(fn ($id) => (int) $id === (int) $keepId))
                        ->values()
                        ->all()
                )->delete();
            }

            $reviewSlide->fill([
                'publication_id' => $publicationTeam->publication_id,
                'reviewer_id' => Auth::id(),
                'sort_order' => (int) ($guidelines->min('sort_order') ?? 1),
                'answers' => $answers,
                'notes' => $validated['notes'] ?? null,
                'saved_at' => now(),
            ]);

            $reviewSlide->save();
        });

        return back()->with(
            'success',
            'Berhasil disimpan. ' . $savedItems . ' dari ' . $totalItems . ' rincian pemeriksaan tersimpan pada slide ' . $section . ' - ' . $subAnatomy . '.'
        );
    }

    public function reviewContent(Request $request, PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);
        $this->ensureReviewAccess($publicationTeam, 'konten', true);
        $this->ensureReviewStage($publicationTeam, 'konten');

        $publication = $publicationTeam->publication;
        $latestDraft = $this->latestDraftOrFail($publicationTeam);

        $validated = $request->validate([
            'result' => ['required', 'in:disetujui,revisi'],
            'final_notes' => ['required', 'string'],
        ], [
            'result.required' => 'Keputusan akhir pemeriksaan konten wajib dipilih.',
            'final_notes.required' => 'Catatan keputusan akhir wajib diisi.',
        ]);

        $summary = $this->buildRevisionSummary($publicationTeam, $latestDraft, 'konten', $validated['final_notes']);

        if ($validated['result'] === 'disetujui') {
            $this->ensureAllReviewItemsApproved($publicationTeam, $latestDraft, 'konten');
        }

        if ($validated['result'] === 'revisi' && empty($summary['slides'])) {
            throw ValidationException::withMessages([
                'result' => 'Keputusan revisi membutuhkan minimal satu rincian pemeriksaan yang dipilih Tidak pada slide pemeriksaan.',
            ]);
        }

        $revisionPublication = null;
        $publicationForLeaderNotification = null;

        DB::transaction(function () use ($publicationTeam, $latestDraft, $validated, $publication, $summary, &$revisionPublication, &$publicationForLeaderNotification) {
            PublicationReview::create([
                'publication_draft_id' => $latestDraft->id,
                'publication_team_id' => $publicationTeam->id,
                'publication_id' => $publication->id,
                'reviewer_id' => Auth::id(),
                'review_type' => 'konten',
                'result' => $validated['result'],
                'checklist' => $summary,
                'notes' => $validated['final_notes'],
                'reviewed_at' => now(),
            ]);

            if ($validated['result'] === 'revisi') {
                $publication->update([
                    'status' => 'penyusunan',
                    'revision_return_stage' => 'pemeriksaan_konten',
                    'content_review_finished_at' => now(),
                ]);

                PublicationTeamAssignmentHistory::create([
                    'publication_id' => $publication->id,
                    'action' => 'Pemeriksaan KONTEN - Revisi/Dikembalikan ke Tim Penyusun',
                    'old_value' => 'pemeriksaan_konten',
                    'new_value' => 'penyusunan',
                    'notes' => $validated['final_notes'],
                    'changed_by' => Auth::id(),
                ]);

                $revisionPublication = $publication->fresh();
            } else {
                $publication->update([
                    'status' => 'pemeriksaan_layout',
                    'revision_return_stage' => null,
                    'content_review_finished_at' => now(),
                    'layout_review_started_at' => now(),
                ]);

                PublicationTeamAssignmentHistory::create([
                    'publication_id' => $publication->id,
                    'action' => 'Pemeriksaan konten disetujui',
                    'old_value' => 'pemeriksaan_konten',
                    'new_value' => 'pemeriksaan_layout',
                    'notes' => $validated['final_notes'],
                    'changed_by' => Auth::id(),
                ]);
            }
        });

        if ($revisionPublication) {
            PublicationNotifier::notifyPreparersForRevision($revisionPublication, 'konten');
        }

        return back()->with('success', 'Keputusan akhir pemeriksaan konten berhasil disimpan.');
    }

    public function reviewLayout(Request $request, PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);
        $this->ensureReviewAccess($publicationTeam, 'layout', true);
        $this->ensureReviewStage($publicationTeam, 'layout');

        $publication = $publicationTeam->publication;
        $latestDraft = $this->latestDraftOrFail($publicationTeam);

        $validated = $request->validate([
            'result' => ['required', 'in:disetujui,revisi'],
            'final_notes' => ['required', 'string'],
        ], [
            'result.required' => 'Keputusan akhir pemeriksaan layout wajib dipilih.',
            'final_notes.required' => 'Catatan keputusan akhir wajib diisi.',
        ]);

        $summary = $this->buildRevisionSummary($publicationTeam, $latestDraft, 'layout', $validated['final_notes']);

        if ($validated['result'] === 'disetujui') {
            $this->ensureAllReviewItemsApproved($publicationTeam, $latestDraft, 'layout');
        }

        if ($validated['result'] === 'revisi' && empty($summary['slides'])) {
            throw ValidationException::withMessages([
                'result' => 'Keputusan revisi membutuhkan minimal satu rincian pemeriksaan yang dipilih Tidak pada slide pemeriksaan.',
            ]);
        }

        $revisionPublication = null;
        $publicationForLeaderNotification = null;

        DB::transaction(function () use ($publicationTeam, $latestDraft, $validated, $publication, $summary, &$revisionPublication, &$publicationForLeaderNotification) {
            PublicationReview::create([
                'publication_draft_id' => $latestDraft->id,
                'publication_team_id' => $publicationTeam->id,
                'publication_id' => $publication->id,
                'reviewer_id' => Auth::id(),
                'review_type' => 'layout',
                'result' => $validated['result'],
                'checklist' => $summary,
                'notes' => $validated['final_notes'],
                'reviewed_at' => now(),
            ]);

            if ($validated['result'] === 'revisi') {
                $publication->update([
                    'status' => 'penyusunan',
                    'revision_return_stage' => 'pemeriksaan_layout',
                    'layout_review_finished_at' => now(),
                ]);

                PublicationTeamAssignmentHistory::create([
                    'publication_id' => $publication->id,
                    'action' => 'Pemeriksaan LAYOUT - Revisi/Dikembalikan ke Tim Penyusun',
                    'old_value' => 'pemeriksaan_layout',
                    'new_value' => 'penyusunan',
                    'notes' => $validated['final_notes'],
                    'changed_by' => Auth::id(),
                ]);

                $revisionPublication = $publication->fresh();
            } else {
                if ($this->shouldEnterInfographicReview($publicationTeam)) {
                    $publication->update([
                        'status' => 'pemeriksaan_infografis',
                        'revision_return_stage' => null,
                        'layout_review_finished_at' => now(),
                        'infographic_review_started_at' => now(),
                        'infographic_review_finished_at' => null,
                    ]);

                    PublicationTeamAssignmentHistory::create([
                        'publication_id' => $publication->id,
                        'action' => 'Pemeriksaan layout disetujui',
                        'old_value' => 'pemeriksaan_layout',
                        'new_value' => 'pemeriksaan_infografis',
                        'notes' => $validated['final_notes'],
                        'changed_by' => Auth::id(),
                    ]);
                } else {
                    $publication->update([
                        'status' => 'persetujuan_pimpinan',
                        'revision_return_stage' => null,
                        'layout_review_finished_at' => now(),
                        'infographic_review_started_at' => null,
                        'infographic_review_finished_at' => now(),
                    ]);

                    PublicationTeamAssignmentHistory::create([
                        'publication_id' => $publication->id,
                        'action' => 'Pemeriksaan layout disetujui',
                        'old_value' => 'pemeriksaan_layout',
                        'new_value' => 'persetujuan_pimpinan',
                        'notes' => $validated['final_notes'],
                        'changed_by' => Auth::id(),
                    ]);

                    $publicationForLeaderNotification = $publication->fresh();
                }
            }
        });

        if ($revisionPublication) {
            PublicationNotifier::notifyPreparersForRevision($revisionPublication, 'layout');
        }

        if ($publicationForLeaderNotification) {
            PublicationNotifier::notifyLeadersForApproval($publicationForLeaderNotification);
        }

        $successMessage = $publicationForLeaderNotification
            ? 'Keputusan akhir pemeriksaan layout berhasil disimpan. Publikasi masuk tahap Persetujuan Pimpinan.'
            : 'Keputusan akhir pemeriksaan layout berhasil disimpan. Publikasi masuk tahap Pemeriksaan Infografis.';

        return back()->with('success', $successMessage);
    }

    public function reviewInfographic(Request $request, PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);
        $this->mustHaveRole($publicationTeam, ['operator_infografis']);
        $this->ensureInfographicStage($publicationTeam);

        $publication = $publicationTeam->publication;
        $latestDraft = $this->latestDraftOrFail($publicationTeam);

        $validated = $request->validate([
            'result' => ['required', 'in:disetujui,revisi'],
            'final_notes' => ['required', 'string'],
            'review_table_file' => ['nullable', 'file', 'mimes:xls,xlsx,csv', 'max:10240'],
            'review_table_url' => ['nullable', 'url', 'max:2048'],
            'review_infographic_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'review_infographic_url' => ['nullable', 'url', 'max:2048'],
        ], [
            'result.required' => 'Keputusan pemeriksaan infografis wajib dipilih.',
            'final_notes.required' => 'Catatan pemeriksaan infografis wajib diisi.',
            'review_table_file.mimes' => 'File hasil pemeriksaan daftar tabel/gambar harus XLS, XLSX, atau CSV.',
            'review_table_file.max' => 'Ukuran file hasil pemeriksaan daftar tabel/gambar maksimal 10MB.',
            'review_table_url.url' => 'Link hasil pemeriksaan daftar tabel/gambar harus berupa URL yang valid.',
            'review_table_url.max' => 'Link hasil pemeriksaan daftar tabel/gambar maksimal 2048 karakter.',
            'review_infographic_file.mimes' => 'File hasil pemeriksaan infografis harus JPG, JPEG, atau PNG.',
            'review_infographic_file.max' => 'Ukuran file hasil pemeriksaan infografis maksimal 5MB.',
            'review_infographic_url.url' => 'Link hasil pemeriksaan infografis harus berupa URL yang valid.',
            'review_infographic_url.max' => 'Link hasil pemeriksaan infografis maksimal 2048 karakter.',
        ]);

        $documents = $publicationTeam->documents()
            ->whereIn('document_type', ['infografis', 'daftar_tabel_gambar'])
            ->orderBy('document_type')
            ->orderByDesc('version')
            ->get();

        if ($this->isProvinsiPublication($publicationTeam)
            && ($documents->where('document_type', 'infografis')->isEmpty() || $documents->where('document_type', 'daftar_tabel_gambar')->isEmpty())) {
            throw ValidationException::withMessages([
                'dokumen' => 'File infografis dan daftar tabel/gambar harus tersedia sebelum pemeriksaan infografis diselesaikan.',
            ]);
        }

        if (!$this->isProvinsiPublication($publicationTeam) && $documents->isEmpty()) {
            throw ValidationException::withMessages([
                'dokumen' => 'Dokumen infografis/daftar tabel-gambar tidak tersedia sehingga pemeriksaan infografis tidak diperlukan.',
            ]);
        }

        $publicationForLeaderNotification = null;
        $revisionPublication = null;

        DB::transaction(function () use ($request, $publicationTeam, $latestDraft, $validated, $publication, $documents, &$publicationForLeaderNotification, &$revisionPublication) {
            $revisionDocuments = collect();

            $fileInputs = [
                'review_table_file' => [
                    'document_type' => 'hasil_pemeriksaan_daftar_tabel_gambar',
                    'link_input' => 'review_table_url',
                ],
                'review_infographic_file' => [
                    'document_type' => 'hasil_pemeriksaan_infografis',
                    'link_input' => 'review_infographic_url',
                ],
            ];

            foreach ($fileInputs as $inputName => $meta) {
                $documentType = $meta['document_type'];
                $linkInput = $meta['link_input'];
                $nextVersion = ((int) PublicationDocument::where('publication_team_id', $publicationTeam->id)
                    ->where('document_type', $documentType)
                    ->max('version')) + 1;

                if ($request->filled($linkInput)) {
                    $revisionDocuments->push(PublicationDocument::create([
                        'publication_team_id' => $publicationTeam->id,
                        'publication_id' => $publication->id,
                        'uploaded_by' => Auth::id(),
                        'document_type' => $documentType,
                        'version' => $nextVersion,
                        'source_type' => 'link',
                        'file_path' => 'external-link',
                        'file_original_name' => 'Link ' . (new PublicationDocument(['document_type' => $documentType]))->document_type_label,
                        'mime_type' => 'text/uri-list',
                        'file_size' => null,
                        'external_url' => $request->input($linkInput),
                        'notes' => $validated['final_notes'],
                        'uploaded_at' => now(),
                    ]));
                    continue;
                }

                if (!$request->hasFile($inputName)) {
                    continue;
                }

                $file = $request->file($inputName);
                $path = $file->store('publication-documents/' . $publication->id . '/' . $documentType, 'public');

                $revisionDocuments->push(PublicationDocument::create([
                    'publication_team_id' => $publicationTeam->id,
                    'publication_id' => $publication->id,
                    'uploaded_by' => Auth::id(),
                    'document_type' => $documentType,
                    'version' => $nextVersion,
                    'source_type' => 'file',
                    'file_path' => $path,
                    'file_original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'external_url' => null,
                    'notes' => $validated['final_notes'],
                    'uploaded_at' => now(),
                ]));
            }

            PublicationReview::create([
                'publication_draft_id' => $latestDraft->id,
                'publication_team_id' => $publicationTeam->id,
                'publication_id' => $publication->id,
                'reviewer_id' => Auth::id(),
                'review_type' => 'infografis',
                'result' => $validated['result'],
                'checklist' => [
                    'mode' => 'infographic_review',
                    'review_type_label' => 'Pemeriksaan Infografis',
                    'checked_documents' => $documents->map(fn ($document) => [
                        'type' => $document->document_type,
                        'label' => $document->document_type_label,
                        'version' => $document->version,
                        'file_original_name' => $document->file_original_name,
                    ])->values()->all(),
                    'revision_documents' => $revisionDocuments->map(fn ($document) => [
                        'id' => $document->id,
                        'type' => $document->document_type,
                        'label' => $document->document_type_label,
                        'version' => $document->version,
                        'file_original_name' => $document->file_original_name,
                        'source_type' => $document->source_type,
                        'is_link' => $document->is_link,
                    ])->values()->all(),
                    'final_notes' => $validated['final_notes'],
                    'generated_at' => now()->toDateTimeString(),
                ],
                'notes' => $validated['final_notes'],
                'reviewed_at' => now(),
            ]);

            if ($validated['result'] === 'revisi') {
                $publication->update([
                    'status' => 'penyusunan',
                    'revision_return_stage' => 'pemeriksaan_infografis',
                    'infographic_review_finished_at' => now(),
                ]);

                PublicationTeamAssignmentHistory::create([
                    'publication_id' => $publication->id,
                    'action' => 'Pemeriksaan INFOGRAFIS - Revisi/Dikembalikan ke Tim Penyusun',
                    'old_value' => 'pemeriksaan_infografis',
                    'new_value' => 'penyusunan',
                    'notes' => $validated['final_notes'],
                    'changed_by' => Auth::id(),
                ]);

                $revisionPublication = $publication->fresh();
            } else {
                $publication->update([
                    'status' => 'persetujuan_pimpinan',
                    'revision_return_stage' => null,
                    'infographic_review_finished_at' => now(),
                ]);

                PublicationTeamAssignmentHistory::create([
                    'publication_id' => $publication->id,
                    'action' => 'Pemeriksaan infografis disetujui',
                    'old_value' => 'pemeriksaan_infografis',
                    'new_value' => 'persetujuan_pimpinan',
                    'notes' => $validated['final_notes'],
                    'changed_by' => Auth::id(),
                ]);

                $publicationForLeaderNotification = $publication->fresh();
            }
        });

        if ($revisionPublication) {
            PublicationNotifier::notifyPreparersForRevision($revisionPublication, 'infografis');
        }

        if ($publicationForLeaderNotification) {
            PublicationNotifier::notifyLeadersForApproval($publicationForLeaderNotification);
        }

        return back()->with('success', 'Keputusan pemeriksaan infografis berhasil disimpan.');
    }

    public function completeWebsiteReleasePackage(Request $request, PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);
        $this->mustHaveRole($publicationTeam, ['operator_website']);
        $this->ensureWebsiteStage($publicationTeam);

        $validated = $request->validate([
            'estimasi_nomor_publikasi' => ['required', 'string', 'max:255'],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'notes' => ['nullable', 'string'],
        ], [
            'estimasi_nomor_publikasi.required' => 'Nomor estimasi publikasi wajib diisi sebelum publikasi dinyatakan siap rilis.',
            'external_url.url' => 'Link Surat Persetujuan Rilis harus berupa URL yang valid.',
            'file.mimes' => 'Surat Persetujuan Rilis harus PDF, DOC, atau DOCX.',
            'file.max' => 'Ukuran Surat Persetujuan Rilis maksimal 10MB.',
        ]);

        if (!$request->hasFile('file') && !$request->filled('external_url')) {
            throw ValidationException::withMessages([
                'file' => 'Isi salah satu: upload file atau input link Surat Persetujuan Rilis.',
            ]);
        }

        $readyPublication = null;

        DB::transaction(function () use ($publicationTeam, $validated, $request, &$readyPublication) {
            $publication = $publicationTeam->publication()->lockForUpdate()->first();
            if ($request->filled('external_url')) {
                $this->storeLinkedDocument(
                    $publicationTeam,
                    $publication,
                    'surat_persetujuan_rilis',
                    $validated['external_url'],
                    $validated['notes'] ?? null
                );
            } else {
                $file = $request->file('file');
                $nextVersion = ((int) PublicationDocument::where('publication_team_id', $publicationTeam->id)
                    ->where('document_type', 'surat_persetujuan_rilis')
                    ->max('version')) + 1;

                $path = $file->store('publication-documents/' . $publication->id . '/surat_persetujuan_rilis', 'public');

                PublicationDocument::create([
                    'publication_team_id' => $publicationTeam->id,
                    'publication_id' => $publication->id,
                    'uploaded_by' => Auth::id(),
                    'document_type' => 'surat_persetujuan_rilis',
                    'version' => $nextVersion,
                    'source_type' => 'file',
                    'file_path' => $path,
                    'file_original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'external_url' => null,
                    'notes' => $validated['notes'] ?? null,
                    'uploaded_at' => now(),
                ]);
            }

            $publication->update([
                'estimasi_nomor_publikasi' => $validated['estimasi_nomor_publikasi'],
                'status' => 'siap_rilis',
                'revision_return_stage' => null,
                'website_packaged_at' => now(),
                'ready_for_release_at' => now(),
            ]);

            PublicationTeamAssignmentHistory::create([
                'publication_id' => $publication->id,
                'action' => 'Finalisasi rilis diselesaikan',
                'old_value' => 'operator_website',
                'new_value' => 'siap_rilis',
                'notes' => $validated['notes'] ?? 'Surat Persetujuan Rilis telah dilampirkan dan nomor estimasi publikasi telah dilengkapi.',
                'changed_by' => Auth::id(),
            ]);

            $readyPublication = $publication->fresh();
        });

        if ($readyPublication) {
            PublicationNotifier::notifyTenantAdminsPublicationReady($readyPublication);
        }

        return back()->with('success', 'Paket publikasi selesai diperiksa. Status publikasi menjadi Siap Rilis.');
    }

    protected function normalizeReviewType(string $type): string
    {
        abort_unless(in_array($type, ['konten', 'layout'], true), 404);

        return $type;
    }

    protected function ensureReviewAccess(PublicationTeam $publicationTeam, string $type, bool $mustBeChief = false): array
    {
        $chiefRole = $type === 'konten' ? 'ketua_pemeriksa_konten' : 'ketua_pemeriksa_layout';
        $memberRole = $type === 'konten' ? 'anggota_pemeriksa_konten' : 'anggota_pemeriksa_layout';

        $isChief = $this->hasRole($publicationTeam, $chiefRole);
        $isMember = $this->hasRole($publicationTeam, $memberRole);

        if ($mustBeChief) {
            abort_unless(
                $isChief,
                403,
                'Keputusan akhir hanya dapat dilakukan oleh ketua pemeriksa.'
            );
        } else {
            abort_unless(
                $isChief || $isMember,
                403,
                'Anda tidak memiliki hak untuk pemeriksaan ini.'
            );
        }

        return [$isChief, $isMember];
    }

    protected function ensureReviewStage(PublicationTeam $publicationTeam, string $type): void
    {
        $expectedStatus = $type === 'konten' ? 'pemeriksaan_konten' : 'pemeriksaan_layout';
        $message = $type === 'konten'
            ? 'Pemeriksaan konten belum aktif. Pemeriksa konten harus menunggu Tim Penyusun menekan Submit.'
            : 'Pemeriksaan layout belum aktif. Pemeriksa layout harus menunggu konten disetujui oleh Ketua Pemeriksa Konten.';

        if ($publicationTeam->publication->status !== $expectedStatus) {
            throw ValidationException::withMessages([
                $type => $message,
            ]);
        }
    }

    protected function ensureInfographicStage(PublicationTeam $publicationTeam): void
    {
        if ($publicationTeam->publication->status !== 'pemeriksaan_infografis') {
            throw ValidationException::withMessages([
                'infografis' => 'Pemeriksaan infografis belum aktif. Operator infografis harus menunggu layout disetujui.',
            ]);
        }
    }

    protected function ensureWebsiteStage(PublicationTeam $publicationTeam): void
    {
        if ($publicationTeam->publication->status !== 'operator_website') {
            throw ValidationException::withMessages([
                'operator_website' => 'Finalisasi rilis belum aktif. Operator website harus menunggu persetujuan pimpinan.',
            ]);
        }
    }

    protected function latestDraftOrFail(PublicationTeam $publicationTeam): PublicationDraft
    {
        $latestDraft = $publicationTeam->drafts()->latest('version')->first();

        if (!$latestDraft) {
            throw ValidationException::withMessages([
                'draft' => 'Belum ada naskah PDF yang dapat diperiksa.',
            ]);
        }

        return $latestDraft;
    }

    protected function activeGuidelines(string $type)
    {
        return InspectionGuideline::where('type', $type)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('tenant_id')
                    ->orWhere('tenant_id', Auth::user()->tenant_id);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    protected function guidelineSlides(string $type)
    {
        return $this->activeGuidelines($type)
            ->groupBy(fn ($item) => $this->slideStorageKey($item->anatomy_section, $item->inspection_item))
            ->map(function ($items, $key) {
                $first = $items->first();

                return [
                    'key' => $key,
                    'anatomy_section' => $first->anatomy_section,
                    'sub_anatomy' => $first->inspection_item,
                    'sort_order' => (int) ($items->min('sort_order') ?? 1),
                    'items' => $items->values(),
                ];
            })
            ->sortBy('sort_order')
            ->values();
    }

    protected function savedSlidesForCurrentUser(PublicationTeam $publicationTeam, ?PublicationDraft $latestDraft, string $type)
    {
        if (!$latestDraft) {
            return collect();
        }

        return PublicationReviewSlide::with('reviewer')
            ->where('publication_team_id', $publicationTeam->id)
            ->where('publication_draft_id', $latestDraft->id)
            ->where('review_type', $type)
            ->orderByDesc('saved_at')
            ->get()
            ->unique('anatomy_section')
            ->keyBy('anatomy_section');
    }

    protected function ensureAllReviewItemsApproved(PublicationTeam $publicationTeam, PublicationDraft $latestDraft, string $type): void
    {
        $stats = $this->reviewAnswerStats($publicationTeam, $latestDraft, $type);

        if ($stats['failed'] > 0 || $stats['unanswered'] > 0) {
            $typeLabel = $type === 'konten' ? 'konten' : 'layout';
            $messages = [];

            if ($stats['failed'] > 0) {
                $messages[] = $stats['failed'] . ' rincian masih dipilih Tidak';
            }

            if ($stats['unanswered'] > 0) {
                $messages[] = $stats['unanswered'] . ' rincian belum dipilih';
            }

            throw ValidationException::withMessages([
                'result' => 'Keputusan Disetujui tidak dapat diproses karena ' . implode(' dan ', $messages) . '. Lengkapi semua rincian pemeriksaan ' . $typeLabel . ' dan pastikan seluruhnya bernilai Ya.',
            ]);
        }
    }

    protected function reviewAnswerStats(PublicationTeam $publicationTeam, PublicationDraft $latestDraft, string $type): array
    {
        $guidelineGroups = $this->activeGuidelines($type)
            ->groupBy(fn ($item) => $this->slideStorageKey($item->anatomy_section, $item->inspection_item));

        $savedSlides = PublicationReviewSlide::where('publication_team_id', $publicationTeam->id)
            ->where('publication_draft_id', $latestDraft->id)
            ->where('review_type', $type)
            ->orderByDesc('saved_at')
            ->get()
            ->unique('anatomy_section')
            ->keyBy('anatomy_section');

        $total = 0;
        $approved = 0;
        $failed = 0;
        $unanswered = 0;

        foreach ($guidelineGroups as $slideKey => $guidelines) {
            $answerMap = collect(optional($savedSlides->get($slideKey))->answers ?? [])
                ->keyBy('guideline_id');

            foreach ($guidelines as $guideline) {
                $total++;
                $answer = data_get($answerMap->get($guideline->id), 'answer');

                if ($answer === 'ya') {
                    $approved++;
                } elseif ($answer === 'tidak') {
                    $failed++;
                } else {
                    $unanswered++;
                }
            }
        }

        return compact('total', 'approved', 'failed', 'unanswered');
    }

    protected function buildRevisionSummary(PublicationTeam $publicationTeam, PublicationDraft $latestDraft, string $type, string $finalNotes): array
    {
        $slides = PublicationReviewSlide::with('reviewer')
            ->where('publication_team_id', $publicationTeam->id)
            ->where('publication_draft_id', $latestDraft->id)
            ->where('review_type', $type)
            ->orderBy('sort_order')
            ->orderBy('anatomy_section')
            ->orderByDesc('saved_at')
            ->get()
            ->unique('anatomy_section')
            ->values();

        $revisionSlides = $slides
            ->map(function (PublicationReviewSlide $slide) use ($publicationTeam) {
                [$anatomySection, $subAnatomy] = $this->parseSlideStorageKey($slide->anatomy_section);

                $failedItems = collect($slide->answers ?? [])
                    ->filter(fn ($item) => ($item['answer'] ?? null) === 'tidak')
                    ->map(fn ($item) => [
                        'anatomy_section' => $item['anatomy_section'] ?? $anatomySection,
                        'sub_anatomy' => $item['sub_anatomy'] ?? $subAnatomy,
                        'requirement_detail' => $item['requirement_detail'] ?? '-',
                    ])
                    ->values()
                    ->all();

                if (empty($failedItems)) {
                    return null;
                }

                return [
                    'anatomy_section' => $anatomySection,
                    'sub_anatomy' => $subAnatomy,
                    'reviewer_name' => optional($slide->reviewer)->name ?? '-',
                    'reviewer_role' => $this->reviewerRoleLabel($publicationTeam, (int) $slide->reviewer_id, $slide->review_type),
                    'notes' => $slide->notes,
                    'failed_items' => $failedItems,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'mode' => 'slide_review',
            'review_type' => $type,
            'review_type_label' => $type === 'konten' ? 'Pemeriksaan Konten' : 'Pemeriksaan Layout',
            'draft_version' => $latestDraft->version,
            'final_notes' => $finalNotes,
            'slides' => $revisionSlides,
            'saved_slide_count' => $slides->count(),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    protected function slideStorageKey(string $anatomySection, string $subAnatomy): string
    {
        return trim($anatomySection) . '||' . trim($subAnatomy);
    }

    protected function parseSlideStorageKey(?string $storedKey): array
    {
        $storedKey = (string) $storedKey;

        if (str_contains($storedKey, '||')) {
            [$anatomySection, $subAnatomy] = explode('||', $storedKey, 2);

            return [trim($anatomySection), trim($subAnatomy)];
        }

        return [trim($storedKey), '-'];
    }

    protected function reviewerRoleLabel(PublicationTeam $publicationTeam, int $reviewerId, string $type): string
    {
        $role = $publicationTeam->assignments()
            ->where('user_id', $reviewerId)
            ->whereIn('assignment_role', $type === 'konten'
                ? ['ketua_pemeriksa_konten', 'anggota_pemeriksa_konten']
                : ['ketua_pemeriksa_layout', 'anggota_pemeriksa_layout'])
            ->value('assignment_role');

        return match ($role) {
            'ketua_pemeriksa_konten' => 'Ketua Pemeriksa Konten',
            'anggota_pemeriksa_konten' => 'Anggota Pemeriksa Konten',
            'ketua_pemeriksa_layout' => 'Ketua Pemeriksa Layout',
            'anggota_pemeriksa_layout' => 'Anggota Pemeriksa Layout',
            default => 'Pemeriksa',
        };
    }


    protected function pdfKeyValueLine(string $label, mixed $value): string
    {
        return str_pad($this->plainPdfText($label), 30) . ': ' . $this->plainPdfText((string) ($value ?? '-'));
    }

    protected function plainPdfText(mixed $value): string
    {
        $text = strip_tags((string) ($value ?? '-'));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text) !== '' ? trim($text) : '-';
    }

    protected function buildWorkReport(PublicationTeam $publicationTeam): array
    {
        $this->authorizeTeam($publicationTeam);

        $publicationTeam->load([
            'publication.tenant',
            'assignments.user',
            'documents.uploader',
            'sprp.submittedBy',
            'drafts.uploader',
            'reviews.reviewer',
        ]);

        abort_unless(
            $publicationTeam->publication && $publicationTeam->publication->status === 'siap_rilis',
            404,
            'Rekap hasil hanya tersedia untuk publikasi yang sudah siap rilis.'
        );

        $userId = (int) Auth::id();
        $myAssignments = $publicationTeam->assignments->where('user_id', $userId)->values();
        $myDocuments = $publicationTeam->documents->where('uploaded_by', $userId)->values();
        $myDrafts = $publicationTeam->drafts->where('uploaded_by', $userId)->values();
        $myReviews = $publicationTeam->reviews->where('reviewer_id', $userId)->values();
        $myHistories = PublicationTeamAssignmentHistory::where('publication_id', $publicationTeam->publication_id)
            ->where('changed_by', $userId)
            ->orderBy('created_at')
            ->get();

        $activities = collect();

        foreach ($myAssignments as $assignment) {
            $activities->push([
                'tanggal' => optional($assignment->created_at)->format('d-m-Y H:i') ?? '-',
                'sort_date' => optional($assignment->created_at)->timestamp ?? 0,
                'aktivitas' => 'Ditugaskan sebagai ' . $assignment->assignment_role_label,
                'keterangan' => $assignment->notes ?: 'Penugasan pada publikasi.',
            ]);
        }

        foreach ($myDocuments as $document) {
            $activities->push([
                'tanggal' => optional($document->uploaded_at ?: $document->created_at)->format('d-m-Y H:i') ?? '-',
                'sort_date' => optional($document->uploaded_at ?: $document->created_at)->timestamp ?? 0,
                'aktivitas' => 'Mengunggah ' . $document->document_type_label . ' versi ' . $document->version,
                'keterangan' => $document->file_original_name,
            ]);
        }

        foreach ($myDrafts as $draft) {
            $activities->push([
                'tanggal' => optional($draft->submitted_at ?: $draft->created_at)->format('d-m-Y H:i') ?? '-',
                'sort_date' => optional($draft->submitted_at ?: $draft->created_at)->timestamp ?? 0,
                'aktivitas' => 'Submit naskah publikasi versi ' . $draft->version,
                'keterangan' => $draft->file_original_name,
            ]);
        }

        foreach ($myReviews as $review) {
            $activities->push([
                'tanggal' => optional($review->reviewed_at ?: $review->created_at)->format('d-m-Y H:i') ?? '-',
                'sort_date' => optional($review->reviewed_at ?: $review->created_at)->timestamp ?? 0,
                'aktivitas' => $review->review_type_label . ' - ' . $review->result_label,
                'keterangan' => $review->notes ?: '-',
            ]);
        }

        foreach ($myHistories as $history) {
            $activities->push([
                'tanggal' => optional($history->created_at)->format('d-m-Y H:i') ?? '-',
                'sort_date' => optional($history->created_at)->timestamp ?? 0,
                'aktivitas' => $history->action,
                'keterangan' => $history->notes ?: '-',
            ]);
        }

        $activities = $activities
            ->sortBy('sort_date')
            ->values();

        return [
            'publicationTeam' => $publicationTeam,
            'publication' => $publicationTeam->publication,
            'myAssignments' => $myAssignments,
            'myDocuments' => $myDocuments,
            'myDrafts' => $myDrafts,
            'myReviews' => $myReviews,
            'myHistories' => $myHistories,
            'activities' => $activities,
        ];
    }

    protected function releasePackageReadme(PublicationTeam $publicationTeam): string
    {
        $publication = $publicationTeam->publication;
        $operator = Auth::user();

        return implode(PHP_EOL, [
            'PAKET PUBLIKASI SIAP RILIS',
            'Manajemen Publikasi Statistik',
            '',
            'Nama Publikasi        : ' . ($publication->nama_publikasi ?? '-'),
            'Tim Kerja             : ' . ($publicationTeam->name ?? '-'),
            'Kategori              : ' . ($publication->kategori ?? '-'),
            'Periode               : ' . ($publication->periode ?? '-'),
            'Akurasi Publikasi     : ' . ($publication->akurasi_publikasi ?? '-'),
            'Nomor Estimasi        : ' . ($publication->estimasi_nomor_publikasi ?: '-'),
            'Jadwal Rilis          : ' . ($publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('d F Y') : '-'),
            'Wilayah               : ' . ($publication->wilayah ?? optional($publication->tenant)->wilayah ?? '-'),
            'Status                : ' . ($publication->status_label ?? '-'),
            '',
            'Diunduh Oleh          : ' . ($operator->name ?? '-'),
            'Peran Unduh           : Operator Website',
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

    protected function sprpHtmlForPackage(PublicationSprp $sprp): string
    {
        $languages = implode(', ', $sprp->bahasa ?? []);
        $yesNo = fn ($value) => $value === null ? '-' : ($value ? 'Ya' : 'Tidak');
        $date = fn ($value) => $value ? $value->translatedFormat('d F Y') : '-';
        $text = fn ($value) => e((string) ($value ?? '-'));

        $rows = [
            ['Bidang/Bagian', $sprp->bidang_bagian],
            ['Rancangan Perwajahan', $sprp->rancangan_perwajahan],
            ['Judul Publikasi', $sprp->judul_publikasi],
            ['Publikasi Baru', $yesNo($sprp->publikasi_baru)],
            ['Ukuran', $sprp->ukuran],
            ['Orientasi', $sprp->orientasi],
            ['Frekuensi Terbit', $sprp->frekuensi_terbit],
            ['Terbitan Ke', $sprp->terbitan_ke],
            ['Tahun Pertama Terbit', $sprp->tahun_pertama_terbit],
            ['Diterbitkan Untuk', $sprp->diterbitkan_untuk],
            ['ARC/Non-ARC', ($sprp->kategori_rilis ?? '-') . ', ' . $date($sprp->tanggal_rilis)],
            ['Jumlah Halaman', 'Romawi: ' . ($sprp->jumlah_halaman_romawi ?? '-') . ' | Arab: ' . ($sprp->jumlah_halaman_arab ?? '-')],
            ['Kerja Sama Luar BPS', $yesNo($sprp->kerja_sama_luar_bps)],
            ['Bahasa', $languages ?: '-'],
            ['Diisi Oleh', optional($sprp->submittedBy)->name ?? '-'],
            ['Waktu Simpan', optional($sprp->submitted_at)->format('d-m-Y H:i') ?? '-'],
        ];

        $items = collect($rows)->map(function ($row) use ($text) {
            return '<div class="sprp-detail-item"><small>' . $text($row[0]) . '</small><strong>' . $text($row[1]) . '</strong></div>';
        })->implode(PHP_EOL);

        return '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form SPRP</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; color: #0f172a; background: #ffffff; }
        .sprp-page { border: 1px solid #cbd5e1; border-radius: 18px; padding: 22px; }
        .sprp-title { margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0; }
        .sprp-title h1 { margin: 0; font-size: 18px; color: #0f2b66; }
        .sprp-title p { margin: 6px 0 0; font-size: 12px; color: #64748b; }
        .sprp-detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .sprp-detail-item { border: 1px solid #dbeafe; border-radius: 14px; padding: 11px 13px; background: #f8fbff; min-height: 62px; }
        .sprp-detail-item small { display: block; font-size: 11px; color: #64748b; margin-bottom: 4px; }
        .sprp-detail-item strong { display: block; font-size: 13px; line-height: 1.35; color: #0f172a; }
        .sprp-footer { margin-top: 18px; font-size: 11px; color: #64748b; }
        @media print { .sprp-page { border-radius: 0; } }
    </style>
</head>
<body>
    <div class="sprp-page">
        <div class="sprp-title">
            <h1>Form SPRP</h1>
            <p>Surat Permintaan/Pengesahan Rancangan Publikasi</p>
        </div>
        <div class="sprp-detail-grid">
            ' . $items . '
        </div>
        <div class="sprp-footer">Dokumen ini dihasilkan dari Manajemen Publikasi Statistik pada ' . e(now()->format('d-m-Y H:i')) . '.</div>
    </div>
</body>
</html>';
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

    protected function authorizeTeam(PublicationTeam $publicationTeam): void
    {
        abort_unless(
            $publicationTeam->assignments()
                ->where('user_id', Auth::id())
                ->exists(),
            403,
            'Anda tidak memiliki akses ke tim kerja ini.'
        );
    }

    protected function myAssignmentRoles(PublicationTeam $publicationTeam): array
    {
        return $publicationTeam->assignments()
            ->where('user_id', Auth::id())
            ->pluck('assignment_role')
            ->toArray();
    }

    protected function mustHaveRole(PublicationTeam $publicationTeam, array $roles): void
    {
        abort_unless(
            $publicationTeam->assignments()
                ->where('user_id', Auth::id())
                ->whereIn('assignment_role', $roles)
                ->exists(),
            403,
            'Anda tidak memiliki hak untuk aksi ini.'
        );
    }

    protected function hasRole(PublicationTeam $publicationTeam, string $role): bool
    {
        return $publicationTeam->assignments()
            ->where('user_id', Auth::id())
            ->where('assignment_role', $role)
            ->exists();
    }

    protected function ensurePublicationInDraftStage(PublicationTeam $publicationTeam): void
    {
        if ($publicationTeam->publication->status !== 'penyusunan') {
            throw ValidationException::withMessages([
                'status' => 'Dokumen penyusunan hanya dapat diubah ketika status publikasi berada pada tahap Penyusunan atau Revisi.',
            ]);
        }
    }

    protected function ensureAuthorWorkUnlocked(PublicationTeam $publicationTeam): void
    {
        if ($this->isAuthorWorkUnlocked($publicationTeam, (int) Auth::id())) {
            return;
        }

        $blockingTeam = $this->blockingAuthorTeam($publicationTeam, (int) Auth::id());
        $blockingTitle = optional(optional($blockingTeam)->publication)->nama_publikasi;

        throw ValidationException::withMessages([
            'penyusunan' => $blockingTitle
                ? 'Selesaikan dan submit penyusunan publikasi "' . $blockingTitle . '" terlebih dahulu sebelum mengelola publikasi ini.'
                : 'Selesaikan dan submit penyusunan publikasi sebelumnya terlebih dahulu sebelum mengelola publikasi ini.',
        ]);
    }

    protected function isAuthorWorkUnlocked(PublicationTeam $publicationTeam, int $userId): bool
    {
        if ($publicationTeam->publication->status !== 'penyusunan') {
            return true;
        }

        if (!empty($publicationTeam->publication->draft_submitted_at)) {
            return true;
        }

        $currentTeamId = $this->currentUnlockedAuthorTeamId($userId);

        return !$currentTeamId || (int) $currentTeamId === (int) $publicationTeam->id;
    }

    protected function blockingAuthorTeam(PublicationTeam $publicationTeam, int $userId): ?PublicationTeam
    {
        $currentTeamId = $this->currentUnlockedAuthorTeamId($userId);

        if (!$currentTeamId || (int) $currentTeamId === (int) $publicationTeam->id) {
            return null;
        }

        return PublicationTeam::with('publication')->find($currentTeamId);
    }

    protected function currentUnlockedAuthorTeamId(int $userId): ?int
    {
        return PublicationTeam::query()
            ->select('publication_teams.id')
            ->join('publications', 'publications.id', '=', 'publication_teams.publication_id')
            ->where('publications.status', 'penyusunan')
            ->whereNull('publications.draft_submitted_at')
            ->whereHas('assignments', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->where('assignment_role', 'penyusun_naskah');
            })
            ->orderByRaw('publications.jadwal_mulai_penyusunan IS NULL')
            ->orderBy('publications.jadwal_mulai_penyusunan')
            ->orderByRaw('publications.jadwal_upload IS NULL')
            ->orderBy('publications.jadwal_upload')
            ->orderByRaw('publications.jadwal_mulai_pemeriksaan IS NULL')
            ->orderBy('publications.jadwal_mulai_pemeriksaan')
            ->orderByRaw('publications.jadwal_rilis IS NULL')
            ->orderBy('publications.jadwal_rilis')
            ->orderBy('publication_teams.created_at')
            ->orderBy('publication_teams.id')
            ->value('publication_teams.id');
    }

    protected function isProvinsiPublication(PublicationTeam $publicationTeam): bool
    {
        $publicationTeam->loadMissing('publication.tenant');

        return optional(optional($publicationTeam->publication)->tenant)->type === 'provinsi';
    }

    protected function hasInfographicOperator(PublicationTeam $publicationTeam): bool
    {
        $assignments = $publicationTeam->relationLoaded('assignments')
            ? $publicationTeam->assignments
            : $publicationTeam->assignments()->get();

        return $assignments->where('assignment_role', 'operator_infografis')->isNotEmpty();
    }

    protected function hasInfographicReviewDocuments(PublicationTeam $publicationTeam): bool
    {
        $documents = $publicationTeam->relationLoaded('documents')
            ? $publicationTeam->documents
            : $publicationTeam->documents()->get();

        return $documents->whereIn('document_type', ['infografis', 'daftar_tabel_gambar'])->isNotEmpty();
    }

    protected function shouldEnterInfographicReview(PublicationTeam $publicationTeam): bool
    {
        if ($this->isProvinsiPublication($publicationTeam)) {
            return true;
        }

        return $this->hasInfographicOperator($publicationTeam)
            && $this->hasInfographicReviewDocuments($publicationTeam);
    }

    protected function authorCompletion(PublicationTeam $publicationTeam): array
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

    protected function storeLinkedDocument(PublicationTeam $publicationTeam, $publication, string $type, string $externalUrl, ?string $notes = null): void
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
                'rules' => [
                    'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
                ],
                'messages' => [
                    'file.required' => 'Naskah PDF wajib diunggah atau isi link dokumen.',
                    'file.mimes' => 'Naskah publikasi harus berformat PDF.',
                    'file.max' => 'Ukuran Naskah PDF maksimal 20MB.',
                ],
                'success' => 'Naskah publikasi PDF berhasil disimpan.',
            ],
            'naskah_zip' => [
                'rules' => [
                    'file' => ['required', 'file', 'mimes:zip,rar', 'max:51200'],
                ],
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
                'rules' => [
                    'file' => ['required', 'file', 'mimes:xls,xlsx,csv', 'max:10240'],
                ],
                'messages' => [
                    'file.required' => 'File daftar tabel dan gambar wajib diunggah atau isi link dokumen.',
                    'file.mimes' => 'Daftar tabel dan gambar harus berformat XLS, XLSX, atau CSV.',
                    'file.max' => 'Ukuran file daftar tabel dan gambar maksimal 10MB.',
                ],
                'success' => 'File/link daftar tabel dan gambar berhasil disimpan.',
            ],
        ];
    }
}
