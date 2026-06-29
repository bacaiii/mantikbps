<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Models\PublicationTeam;
use App\Models\PublicationTeamAssignment;
use App\Models\PublicationTeamAssignmentHistory;
use App\Models\TeamTemplate;
use App\Models\User;
use App\Support\PublicationNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeamAllocationController extends Controller
{
    protected array $assignmentRoles = [
        'penyusun_naskah',
        'ketua_pemeriksa_konten',
        'anggota_pemeriksa_konten',
        'ketua_pemeriksa_layout',
        'anggota_pemeriksa_layout',
        'operator_website',
        'operator_infografis',
    ];

    public function index(Request $request)
    {
        [$sortBy, $sortDir, $query] = $this->applyTeamSort($this->teamQuery($request), $request);

        $teams = $query
            ->paginate(10)
            ->withQueryString();

        $yearOptions = range(now()->year - 3, now()->year + 1);
        $selectedYear = (int) $request->input('tahun', now()->year);
        if (!in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = now()->year;
        }

        $monthOptions = $this->monthOptions($selectedYear);
        $selectedMonth = $request->input('bulan');

        return view('tenant.team_allocations.index', compact('teams', 'sortBy', 'sortDir', 'yearOptions', 'selectedYear', 'monthOptions', 'selectedMonth'));
    }

    public function assignIndex(Request $request)
    {
        [$sortBy, $sortDir, $query] = $this->applyTeamSort(
            $this->teamQuery($request)->with('assignments.user'),
            $request
        );

        $teams = $query
            ->paginate(10)
            ->withQueryString();

        $yearOptions = range(now()->year - 3, now()->year + 1);
        $selectedYear = (int) $request->input('tahun', now()->year);
        if (!in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = now()->year;
        }

        $monthOptions = $this->monthOptions($selectedYear);
        $selectedMonth = $request->input('bulan');

        return view('tenant.team_allocations.assign_index', compact('teams', 'sortBy', 'sortDir', 'yearOptions', 'selectedYear', 'monthOptions', 'selectedMonth'));
    }

    public function create()
    {
        $team = new PublicationTeam();
        $publications = $this->availablePublications();
        $teamTemplates = TeamTemplate::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_active', true)
            ->with(['members.user'])
            ->orderBy('name')
            ->get();

        return view('tenant.team_allocations.create', compact('team', 'publications', 'teamTemplates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'publication_id' => [
                'required',
                'integer',
                Rule::exists('publications', 'id')->where('tenant_id', Auth::user()->tenant_id),
                Rule::unique('publication_teams', 'publication_id'),
            ],
            'team_template_id' => [
                'required',
                'integer',
                Rule::exists('team_templates', 'id')->where('tenant_id', Auth::user()->tenant_id),
            ],
        ], [
            'publication_id.required' => 'Judul publikasi wajib dipilih.',
            'publication_id.unique' => 'Publikasi ini sudah memiliki tim kerja.',
            'team_template_id.required' => 'Template tim kerja wajib dipilih.',
        ]);

        $publication = Publication::where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($validated['publication_id']);

        $template = TeamTemplate::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_active', true)
            ->with('members')
            ->findOrFail($validated['team_template_id']);

        if ($template->members->isEmpty()) {
            throw ValidationException::withMessages([
                'team_template_id' => 'Tim kerja "' . $template->name . '" belum lengkap: template tim kerja belum memiliki anggota.',
            ]);
        }

        $rows = $template->members
            ->map(fn ($member) => [
                'user_id' => (int) $member->user_id,
                'assignment_role' => $member->assignment_role,
            ])
            ->values();

        try {
            $this->validateAssignmentRows($rows);
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->filter()->values();
            $message = $messages->first() ?: 'kelengkapan anggota tim belum valid.';

            throw ValidationException::withMessages([
                'team_template_id' => 'Tim kerja "' . $template->name . '" belum lengkap: ' . $message,
            ]);
        }

        $assignmentsToNotify = collect();

        DB::transaction(function () use ($publication, $template, $rows, &$assignmentsToNotify) {
            $team = PublicationTeam::create([
                'publication_id' => $publication->id,
                'name' => $template->name,
                'created_by' => Auth::id(),
                'notes' => 'Dialokasikan dari template tim: ' . $template->name,
            ]);

            foreach ($rows as $row) {
                $assignment = PublicationTeamAssignment::create([
                    'publication_id' => $publication->id,
                    'publication_team_id' => $team->id,
                    'user_id' => $row['user_id'],
                    'assignment_role' => $row['assignment_role'],
                    'assigned_by' => Auth::id(),
                    'notes' => 'Dialokasikan dari template tim: ' . $template->name,
                ]);

                $assignmentsToNotify->push($assignment->load('user'));
            }

            $team->load('assignments.user');
            PublicationTeamAssignmentHistory::create([
                'publication_id' => $publication->id,
                'action' => 'Alokasi tim kerja dari template',
                'old_value' => json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'new_value' => json_encode($this->buildSnapshot($team), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'notes' => 'Template tim: ' . $template->name,
                'changed_by' => Auth::id(),
            ]);
        });

        $createdTeam = PublicationTeam::where('publication_id', $publication->id)->latest('id')->first();
        if ($createdTeam) {
            $createdTeam->load('publication');
            PublicationNotifier::notifyEmployeeAssignments($createdTeam, $assignmentsToNotify);
        }

        return redirect()
            ->route('tenant.team-allocations.index')
            ->with('success', 'Alokasi tim ke publikasi berhasil dibuat.');
    }

    public function edit(PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);

        return redirect()->route('tenant.team-allocations.assign', $publicationTeam->id);
    }

    public function update(Request $request, PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);

        $validated = $request->validate([
            'publication_id' => [
                'required',
                'integer',
                Rule::exists('publications', 'id')->where('tenant_id', Auth::user()->tenant_id),
                Rule::unique('publication_teams', 'publication_id')->ignore($publicationTeam->id),
            ],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'publication_id.required' => 'Judul publikasi wajib dipilih.',
            'publication_id.unique' => 'Publikasi ini sudah memiliki tim kerja.',
            'name.required' => 'Nama tim wajib diisi.',
        ]);

        $publication = Publication::where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($validated['publication_id']);

        DB::transaction(function () use ($publicationTeam, $publication, $validated) {
            $publicationTeam->update([
                'publication_id' => $publication->id,
                'name' => $validated['name'],
                'notes' => null,
            ]);

            $publicationTeam->assignments()->update([
                'publication_id' => $publication->id,
            ]);
        });

        return redirect()
            ->route('tenant.team-allocations.index')
            ->with('success', 'Tim kerja berhasil diperbarui.');
    }

    public function destroy(PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);

        $publicationTeam->delete();

        return redirect()
            ->route('tenant.team-allocations.index')
            ->with('success', 'Tim kerja berhasil dihapus.');
    }

    public function assign(PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);

        $team = $publicationTeam->load([
            'publication.tenant',
            'assignments.user',
            'publication.teamAssignmentHistories.changedBy',
        ]);

        $pegawai = User::where('tenant_id', Auth::user()->tenant_id)
            ->where('role', 'pegawai')
            ->orderBy('name')
            ->get();

        $assignmentRows = $team->assignments
            ->map(fn ($assignment) => [
                'user_id' => $assignment->user_id,
                'assignment_role' => $assignment->assignment_role,
            ])
            ->values()
            ->toArray();

        if (empty($assignmentRows)) {
            $assignmentRows = [
                ['user_id' => '', 'assignment_role' => 'penyusun_naskah'],
                ['user_id' => '', 'assignment_role' => 'ketua_pemeriksa_konten'],
                ['user_id' => '', 'assignment_role' => 'ketua_pemeriksa_layout'],
            ];

            if ($this->tenantRequiresInfographicOperator()) {
                $assignmentRows[] = ['user_id' => '', 'assignment_role' => 'operator_infografis'];
            }

            $assignmentRows[] = ['user_id' => '', 'assignment_role' => 'operator_website'];
        }

        $assignmentRoles = $this->assignmentRoleOptions();

        return view('tenant.team_allocations.assign', compact(
            'team',
            'pegawai',
            'assignmentRows',
            'assignmentRoles'
        ));
    }

    public function updateAssign(Request $request, PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.user_id' => ['required', 'integer'],
            'assignments.*.assignment_role' => ['required', Rule::in($this->assignmentRoles)],
            'notes' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama tim wajib diisi.',
            'assignments.required' => 'Minimal satu anggota tim wajib diisi.',
            'assignments.*.user_id.required' => 'Pegawai wajib dipilih.',
            'assignments.*.assignment_role.required' => 'Tugas wajib dipilih.',
        ]);

        $rows = collect($validated['assignments'])
            ->filter(fn ($row) => !empty($row['user_id']) && !empty($row['assignment_role']))
            ->map(fn ($row) => [
                'user_id' => (int) $row['user_id'],
                'assignment_role' => $row['assignment_role'],
            ])
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'assignments' => 'Minimal satu anggota tim wajib diisi.',
            ]);
        }

        $this->validateAssignmentRows($rows);

        $assignmentsToNotify = collect();

        DB::transaction(function () use ($publicationTeam, $rows, $validated, &$assignmentsToNotify) {
            $publicationTeam->load('assignments.user', 'publication');
            $oldSnapshot = $this->buildSnapshot($publicationTeam);
            $oldAssignmentKeys = $publicationTeam->assignments
                ->map(fn ($assignment) => $assignment->user_id . '|' . $assignment->assignment_role)
                ->values()
                ->all();

            $publicationTeam->update([
                'name' => $validated['name'],
            ]);

            $publicationTeam->assignments()->delete();

            foreach ($rows as $row) {
                $assignment = PublicationTeamAssignment::create([
                    'publication_id' => $publicationTeam->publication_id,
                    'publication_team_id' => $publicationTeam->id,
                    'user_id' => $row['user_id'],
                    'assignment_role' => $row['assignment_role'],
                    'assigned_by' => Auth::id(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                $assignmentKey = $row['user_id'] . '|' . $row['assignment_role'];
                if (!in_array($assignmentKey, $oldAssignmentKeys, true)) {
                    $assignmentsToNotify->push($assignment->load('user'));
                }
            }

            $publicationTeam->load('assignments.user');
            $newSnapshot = $this->buildSnapshot($publicationTeam);

            PublicationTeamAssignmentHistory::create([
                'publication_id' => $publicationTeam->publication_id,
                'action' => 'Assign tim kerja diperbarui',
                'old_value' => json_encode($oldSnapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'new_value' => json_encode($newSnapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'notes' => $validated['notes'] ?? null,
                'changed_by' => Auth::id(),
            ]);
        });

        $publicationTeam->load('publication');
        PublicationNotifier::notifyEmployeeAssignments($publicationTeam, $assignmentsToNotify);

        return redirect()
            ->route('tenant.team-allocations.index')
            ->with('success', 'Assign anggota tim kerja berhasil disimpan.');
    }

    protected function applyTeamSort($query, Request $request): array
    {
        $allowedSorts = [
            'created_at',
            'name',
            'publication_name',
            'jadwal_rilis',
            'status_alokasi',
        ];

        $sortBy = in_array($request->get('sort_by'), $allowedSorts, true)
            ? $request->get('sort_by')
            : 'created_at';

        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'name') {
            $query->orderBy('publication_teams.name', $sortDir);
        } elseif ($sortBy === 'publication_name') {
            $query->orderBy(
                Publication::select('nama_publikasi')
                    ->whereColumn('publications.id', 'publication_teams.publication_id'),
                $sortDir
            );
        } elseif ($sortBy === 'jadwal_rilis') {
            $query->orderBy(
                Publication::select('jadwal_rilis')
                    ->whereColumn('publications.id', 'publication_teams.publication_id'),
                $sortDir
            );
        } elseif ($sortBy === 'status_alokasi') {
            $requiredRoles = $this->tenantRequiresInfographicOperator()
                ? "'penyusun_naskah','ketua_pemeriksa_konten','ketua_pemeriksa_layout','operator_infografis','operator_website'"
                : "'penyusun_naskah','ketua_pemeriksa_konten','ketua_pemeriksa_layout','operator_website'";

            $query->orderByRaw(
                "(SELECT COUNT(DISTINCT pta.assignment_role) FROM publication_team_assignments pta WHERE pta.publication_team_id = publication_teams.id AND pta.assignment_role IN ({$requiredRoles})) {$sortDir}"
            );
        } else {
            $query->orderBy('publication_teams.created_at', $sortDir);
        }

        if ($sortBy !== 'created_at') {
            $query->orderByDesc('publication_teams.created_at');
        }

        return [$sortBy, $sortDir, $query];
    }

    protected function teamQuery(Request $request)
    {
        $query = PublicationTeam::with(['publication.tenant', 'assignments.user'])
            ->whereHas('publication', fn ($q) => $q->where('tenant_id', Auth::user()->tenant_id));

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                    ->orWhereHas('publication', fn ($pub) => $pub->where('nama_publikasi', 'like', '%' . $request->q . '%'));
            });
        }

        if ($request->filled('kategori')) {
            $query->whereHas('publication', fn ($q) => $q->where('kategori', $request->kategori));
        }

        $yearOptions = range(now()->year - 3, now()->year + 1);
        $selectedYear = (int) $request->input('tahun', now()->year);
        if (!in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = now()->year;
        }
        $query->whereHas('publication', fn ($q) => $q->where('tahun', $selectedYear));

        $selectedMonth = $request->input('bulan');
        if ($selectedMonth !== null && $selectedMonth !== '') {
            $query->whereHas('publication', fn ($q) => $q->whereMonth('jadwal_rilis', (int) $selectedMonth));
        }

        if ($request->filled('status_alokasi')) {
            $requiresInfografis = $this->tenantRequiresInfographicOperator();

            if ($request->status_alokasi === 'lengkap') {
                $query->whereHas('assignments', fn ($q) => $q->where('assignment_role', 'penyusun_naskah'))
                    ->whereHas('assignments', fn ($q) => $q->where('assignment_role', 'ketua_pemeriksa_konten'))
                    ->whereHas('assignments', fn ($q) => $q->where('assignment_role', 'ketua_pemeriksa_layout'))
                    ->whereHas('assignments', fn ($q) => $q->where('assignment_role', 'operator_website'));

                if ($requiresInfografis) {
                    $query->whereHas('assignments', fn ($q) => $q->where('assignment_role', 'operator_infografis'));
                }
            }

            if ($request->status_alokasi === 'belum_lengkap') {
                $query->where(function ($q) use ($requiresInfografis) {
                    $q->whereDoesntHave('assignments', fn ($sub) => $sub->where('assignment_role', 'penyusun_naskah'))
                        ->orWhereDoesntHave('assignments', fn ($sub) => $sub->where('assignment_role', 'ketua_pemeriksa_konten'))
                        ->orWhereDoesntHave('assignments', fn ($sub) => $sub->where('assignment_role', 'ketua_pemeriksa_layout'))
                        ->orWhereDoesntHave('assignments', fn ($sub) => $sub->where('assignment_role', 'operator_website'));

                    if ($requiresInfografis) {
                        $q->orWhereDoesntHave('assignments', fn ($sub) => $sub->where('assignment_role', 'operator_infografis'));
                    }
                });
            }
        }

        return $query;
    }

    protected function monthOptions(int $selectedYear)
    {
        return collect(range(1, 12))->mapWithKeys(fn ($month) => [
            $month => Carbon::createFromDate($selectedYear, $month, 1)->translatedFormat('F'),
        ]);
    }

    protected function authorizeTeam(PublicationTeam $team): void
    {
        $team->loadMissing('publication');

        abort_unless(
            $team->publication && $team->publication->tenant_id === Auth::user()->tenant_id,
            403,
            'Anda tidak memiliki akses ke tim kerja ini.'
        );
    }

    protected function availablePublications(?int $currentPublicationId = null)
    {
        return Publication::where('tenant_id', Auth::user()->tenant_id)
            ->where(function ($q) use ($currentPublicationId) {
                $q->whereDoesntHave('team');

                if ($currentPublicationId) {
                    $q->orWhere('id', $currentPublicationId);
                }
            })
            ->orderBy('nomor')
            ->get();
    }

    protected function generateTeamName(Publication $publication): string
    {
        return 'Tim Kerja ' . $publication->nama_publikasi;
    }

    protected function validateAssignmentRows($rows): void
    {
        $specialMultiRoles = [
            'ketua_pemeriksa_layout',
            'anggota_pemeriksa_layout',
            'operator_infografis',
            'operator_website',
        ];

        $invalidDuplicate = $rows
            ->groupBy('user_id')
            ->contains(function ($items) use ($specialMultiRoles) {
                return $items->count() > 1
                    && $items->pluck('assignment_role')->contains(fn ($role) => !in_array($role, $specialMultiRoles, true));
            });

        if ($invalidDuplicate) {
            throw ValidationException::withMessages([
                'assignments' => 'Pegawai hanya boleh merangkap tugas khusus Pemeriksa Layout, Operator Infografis, dan Operator Website.',
            ]);
        }

        $userIds = $rows->pluck('user_id')->unique()->values();


        $validUserCount = User::where('tenant_id', Auth::user()->tenant_id)
            ->where('role', 'pegawai')
            ->whereIn('id', $userIds)
            ->count();

        if ($validUserCount !== $userIds->count()) {
            throw ValidationException::withMessages([
                'assignments' => 'Pegawai yang dipilih tidak valid atau bukan bagian dari tenant Anda.',
            ]);
        }

        $roleCounts = $rows->pluck('assignment_role')->countBy();

        $penyusunNaskah = $roleCounts['penyusun_naskah'] ?? 0;

        $pemeriksaKonten =
            ($roleCounts['ketua_pemeriksa_konten'] ?? 0)
            + ($roleCounts['anggota_pemeriksa_konten'] ?? 0);

        $pemeriksaLayout =
            ($roleCounts['ketua_pemeriksa_layout'] ?? 0)
            + ($roleCounts['anggota_pemeriksa_layout'] ?? 0);

        $operatorWebsite = $roleCounts['operator_website'] ?? 0;
        $operatorInfografis = $roleCounts['operator_infografis'] ?? 0;

        if ($penyusunNaskah > 6) {
            throw ValidationException::withMessages([
                'assignments' => 'Maksimal Tim penyusun 6 orang.',
            ]);
        }

        if ($pemeriksaKonten > 3) {
            throw ValidationException::withMessages([
                'assignments' => 'Pemeriksa Konten maks 3 orang.',
            ]);
        }

        if ($pemeriksaLayout > 3) {
            throw ValidationException::withMessages([
                'assignments' => 'Pemeriksa Layout maks 3 orang.',
            ]);
        }

        if ($operatorWebsite > 1) {
            throw ValidationException::withMessages([
                'assignments' => 'Operator website maks 1 orang.',
            ]);
        }

        if ($operatorInfografis > 1) {
            throw ValidationException::withMessages([
                'assignments' => 'Operator Infografis maks 1 orang.',
            ]);
        }

        if ($this->tenantRequiresInfographicOperator() && $operatorInfografis !== 1) {
            throw ValidationException::withMessages([
                'assignments' => 'Operator Infografis wajib tepat satu pegawai.',
            ]);
        }

        if ($operatorWebsite !== 1) {
            throw ValidationException::withMessages([
                'assignments' => 'Operator Website wajib tepat satu pegawai.',
            ]);
        }        

        if (($roleCounts['penyusun_naskah'] ?? 0) < 1) {
            throw ValidationException::withMessages([
                'assignments' => 'Minimal satu pegawai harus ditugaskan sebagai Penyusun Naskah.',
            ]);
        }

        if (($roleCounts['ketua_pemeriksa_konten'] ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'assignments' => 'Ketua Pemeriksa Konten wajib tepat satu pegawai.',
            ]);
        }

        if (($roleCounts['ketua_pemeriksa_layout'] ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'assignments' => 'Ketua Pemeriksa Layout wajib tepat satu pegawai.',
            ]);
        }
    }

    protected function buildSnapshot(PublicationTeam $team): array
    {
        $team->loadMissing('assignments.user');

        $snapshot = [];

        foreach ($this->assignmentRoleOptions() as $role => $label) {
            $snapshot[$role] = $team->assignments
                ->where('assignment_role', $role)
                ->map(fn ($assignment) => optional($assignment->user)->name)
                ->filter()
                ->values()
                ->toArray();
        }

        return $snapshot;
    }

    protected function assignmentRoleOptions(): array
    {
        return [
            'penyusun_naskah' => 'Penyusun Naskah',
            'ketua_pemeriksa_konten' => 'Ketua Pemeriksa Konten',
            'anggota_pemeriksa_konten' => 'Anggota Pemeriksa Konten',
            'ketua_pemeriksa_layout' => 'Ketua Pemeriksa Layout',
            'anggota_pemeriksa_layout' => 'Anggota Pemeriksa Layout',
            'operator_website' => 'Operator Website',
            'operator_infografis' => 'Operator Infografis',
        ];
    }

    protected function tenantRequiresInfographicOperator(): bool
    {
        $tenant = Auth::user()->relationLoaded('tenant')
            ? Auth::user()->tenant
            : Auth::user()->tenant()->first();

        return optional($tenant)->type === 'provinsi';
    }

    public function clearAssignments(PublicationTeam $publicationTeam)
    {
        $this->authorizeTeam($publicationTeam);

        $publicationTeam->assignments()->delete();

        return redirect()
            ->route('tenant.team-allocations.index')
            ->with('success', 'Anggota dan tugas pada tim kerja berhasil dihapus.');
    }
}
