<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Models\PublicationReview;
use App\Models\PublicationDocument;
use App\Models\PublicationTeamAssignmentHistory;
use App\Support\PublicationNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class LeaderApprovalController extends Controller
{
    public function index(Request $request)
    {
        $query = Publication::with(['team.assignments.user'])
            ->where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('status', ['persetujuan_pimpinan', 'operator_website'])
            ->orderByRaw("CASE status WHEN 'persetujuan_pimpinan' THEN 1 WHEN 'operator_website' THEN 2 ELSE 3 END")
            ->orderBy('jadwal_rilis');

        if ($request->filled('status') && in_array($request->status, ['persetujuan_pimpinan', 'operator_website'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $query->where('nama_publikasi', 'like', '%' . $request->q . '%');
        }

        $publications = $query->paginate(10)->withQueryString();

        return view('leader.approvals.index', compact('publications'));
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
                ->with('error', 'File dokumen tidak ditemukan di folder storage.');
        }

        return response()->download(
            $realPath,
            $publicationDocument->file_original_name ?: basename($realPath)
        );
    }

    public function show(Publication $publication)
    {
        $this->authorizePublication($publication);

        $publication->load([
            'tenant',
            'team.assignments.user',
            'documents.uploader',
            'sprp.submittedBy',
            'drafts.uploader',
            'drafts.reviews.reviewer',
            'teamAssignmentHistories.changedBy',
        ]);

        return view('leader.approvals.show', compact('publication'));
    }

    public function decide(Request $request, Publication $publication)
    {
        $this->authorizePublication($publication);

        if ($publication->status !== 'persetujuan_pimpinan') {
            throw ValidationException::withMessages([
                'status' => 'Publikasi belum berada pada tahap Persetujuan Pimpinan.',
            ]);
        }

        $validated = $request->validate([
            'result' => ['required', 'in:disetujui,revisi'],
            'notes' => ['required', 'string'],
        ], [
            'result.required' => 'Keputusan persetujuan wajib dipilih.',
            'notes.required' => 'Catatan keputusan wajib diisi.',
        ]);

        $publication->loadMissing('team');

        if (!$publication->team || !$publication->drafts()->latest('version')->exists()) {
            throw ValidationException::withMessages([
                'dokumen' => 'Publikasi belum memiliki tim kerja atau naskah PDF yang dapat disetujui.',
            ]);
        }

        $revisionPublication = null;

        DB::transaction(function () use ($publication, $validated, &$revisionPublication) {
            $publication = Publication::whereKey($publication->id)->lockForUpdate()->first();
            $team = $publication->team;
            $latestDraft = $publication->drafts()->latest('version')->first();

            PublicationReview::create([
                'publication_draft_id' => $latestDraft?->id,
                'publication_team_id' => $team?->id,
                'publication_id' => $publication->id,
                'reviewer_id' => Auth::id(),
                'review_type' => 'pimpinan',
                'result' => $validated['result'],
                'checklist' => [
                    'mode' => 'leader_approval',
                    'review_type_label' => 'Persetujuan Pimpinan',
                    'final_notes' => $validated['notes'],
                    'generated_at' => now()->toDateTimeString(),
                ],
                'notes' => $validated['notes'],
                'reviewed_at' => now(),
            ]);

            if ($validated['result'] === 'revisi') {
                $publication->update([
                    'status' => 'penyusunan',
                    'revision_return_stage' => 'persetujuan_pimpinan',
                ]);

                PublicationTeamAssignmentHistory::create([
                    'publication_id' => $publication->id,
                    'action' => 'Persetujuan PIMPINAN - Revisi/Dikembalikan ke Tim Penyusun',
                    'old_value' => 'persetujuan_pimpinan',
                    'new_value' => 'penyusunan',
                    'notes' => $validated['notes'],
                    'changed_by' => Auth::id(),
                ]);

                $revisionPublication = $publication->fresh();
            } else {
                $publication->update([
                    'status' => 'operator_website',
                    'revision_return_stage' => null,
                    'leadership_approved_at' => now(),
                ]);

                PublicationTeamAssignmentHistory::create([
                    'publication_id' => $publication->id,
                    'action' => 'Persetujuan pimpinan disetujui',
                    'old_value' => 'persetujuan_pimpinan',
                    'new_value' => 'operator_website',
                    'notes' => $validated['notes'],
                    'changed_by' => Auth::id(),
                ]);
            }
        });

        if ($revisionPublication) {
            PublicationNotifier::notifyPreparersForRevision($revisionPublication, 'pimpinan');
        }

        return redirect()
            ->route('leader.approvals.index')
            ->with('success', 'Keputusan pimpinan berhasil disimpan.');
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
}
