<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TeamTemplate;
use App\Models\TeamTemplateMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeamTemplateController extends Controller
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
        $query = TeamTemplate::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->with(['members.user'])
            ->withCount('members');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'aktif');
        }

        $templates = $query
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('tenant.team_templates.index', compact('templates'));
    }

    public function create()
    {
        $template = new TeamTemplate(['is_active' => true]);
        $pegawai = $this->employeeOptions();
        $assignmentRoles = $this->assignmentRoleOptions();
        $memberRows = [
            ['user_id' => '', 'assignment_role' => 'penyusun_naskah'],
            ['user_id' => '', 'assignment_role' => 'ketua_pemeriksa_konten'],
            ['user_id' => '', 'assignment_role' => 'ketua_pemeriksa_layout'],
        ];

        if ($this->tenantRequiresInfographicOperator()) {
            $memberRows[] = ['user_id' => '', 'assignment_role' => 'operator_infografis'];
        }

        $memberRows[] = ['user_id' => '', 'assignment_role' => 'operator_website'];

        return view('tenant.team_templates.create', compact('template', 'pegawai', 'assignmentRoles', 'memberRows'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateTemplate($request);
        $rows = $this->prepareMemberRows($validated['members']);
        $this->validateMemberRows($rows);

        DB::transaction(function () use ($validated, $rows) {
            $template = TeamTemplate::create([
                'tenant_id' => Auth::user()->tenant_id,
                'name' => $validated['name'],
                'notes' => $validated['notes'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'created_by' => Auth::id(),
            ]);

            $this->syncMembers($template, $rows);
        });

        return redirect()
            ->route('tenant.team-templates.index')
            ->with('success', 'Template tim kerja berhasil dibuat.');
    }

    public function edit(TeamTemplate $teamTemplate)
    {
        $this->authorizeTemplate($teamTemplate);

        $template = $teamTemplate->load('members.user');
        $pegawai = $this->employeeOptions();
        $assignmentRoles = $this->assignmentRoleOptions();
        $memberRows = $template->members
            ->map(fn ($member) => [
                'user_id' => $member->user_id,
                'assignment_role' => $member->assignment_role,
            ])
            ->values()
            ->toArray();

        if (empty($memberRows)) {
            $memberRows = [['user_id' => '', 'assignment_role' => 'penyusun_naskah']];
        }

        return view('tenant.team_templates.edit', compact('template', 'pegawai', 'assignmentRoles', 'memberRows'));
    }

    public function update(Request $request, TeamTemplate $teamTemplate)
    {
        $this->authorizeTemplate($teamTemplate);

        $validated = $this->validateTemplate($request);
        $rows = $this->prepareMemberRows($validated['members']);
        $this->validateMemberRows($rows);

        DB::transaction(function () use ($teamTemplate, $validated, $rows) {
            $teamTemplate->update([
                'name' => $validated['name'],
                'notes' => $validated['notes'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $this->syncMembers($teamTemplate, $rows);
        });

        return redirect()
            ->route('tenant.team-templates.index')
            ->with('success', 'Template tim kerja berhasil diperbarui.');
    }

    public function destroy(TeamTemplate $teamTemplate)
    {
        $this->authorizeTemplate($teamTemplate);
        $teamTemplate->delete();

        return redirect()
            ->route('tenant.team-templates.index')
            ->with('success', 'Template tim kerja berhasil dihapus.');
    }

    protected function validateTemplate(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'members' => ['required', 'array', 'min:1'],
            'members.*.user_id' => ['required', 'integer'],
            'members.*.assignment_role' => ['required', Rule::in($this->assignmentRoles)],
        ], [
            'name.required' => 'Nama tim wajib diisi.',
            'members.required' => 'Minimal satu anggota tim wajib diisi.',
            'members.*.user_id.required' => 'Pegawai wajib dipilih.',
            'members.*.assignment_role.required' => 'Tugas wajib dipilih.',
        ]);
    }

    protected function prepareMemberRows(array $members)
    {
        $rows = collect($members)
            ->filter(fn ($row) => !empty($row['user_id']) && !empty($row['assignment_role']))
            ->map(fn ($row) => [
                'user_id' => (int) $row['user_id'],
                'assignment_role' => $row['assignment_role'],
            ])
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'members' => 'Minimal satu anggota tim wajib diisi.',
            ]);
        }

        return $rows;
    }

    protected function validateMemberRows($rows): void
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
                'members' => 'Pegawai hanya boleh merangkap tugas khusus Pemeriksa Layout, Operator Infografis, dan Operator Website.',
            ]);
        }

        $userIds = $rows->pluck('user_id')->unique()->values();


        $validUserCount = User::where('tenant_id', Auth::user()->tenant_id)
            ->where('role', 'pegawai')
            ->whereIn('id', $userIds)
            ->count();

        if ($validUserCount !== $userIds->count()) {
            throw ValidationException::withMessages([
                'members' => 'Pegawai yang dipilih tidak valid atau bukan bagian dari wilayah kerja Anda.',
            ]);
        }

        $roleCounts = $rows->pluck('assignment_role')->countBy();
        $penyusunNaskah = $roleCounts['penyusun_naskah'] ?? 0;
        $pemeriksaKonten = ($roleCounts['ketua_pemeriksa_konten'] ?? 0) + ($roleCounts['anggota_pemeriksa_konten'] ?? 0);
        $pemeriksaLayout = ($roleCounts['ketua_pemeriksa_layout'] ?? 0) + ($roleCounts['anggota_pemeriksa_layout'] ?? 0);
        $operatorWebsite = $roleCounts['operator_website'] ?? 0;
        $operatorInfografis = $roleCounts['operator_infografis'] ?? 0;

        if ($penyusunNaskah > 6) {
            throw ValidationException::withMessages(['members' => 'Maksimal Tim Penyusun 6 orang.']);
        }

        if ($pemeriksaKonten > 3) {
            throw ValidationException::withMessages(['members' => 'Pemeriksa Konten maksimal 3 orang.']);
        }

        if ($pemeriksaLayout > 3) {
            throw ValidationException::withMessages(['members' => 'Pemeriksa Layout maksimal 3 orang.']);
        }

        if ($operatorWebsite > 1) {
            throw ValidationException::withMessages(['members' => 'Operator Website maksimal 1 orang.']);
        }

        if ($operatorInfografis > 1) {
            throw ValidationException::withMessages(['members' => 'Operator Infografis maksimal 1 orang.']);
        }
    }

    protected function syncMembers(TeamTemplate $template, $rows): void
    {
        $template->members()->delete();

        foreach ($rows as $index => $row) {
            TeamTemplateMember::create([
                'team_template_id' => $template->id,
                'user_id' => $row['user_id'],
                'assignment_role' => $row['assignment_role'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function employeeOptions()
    {
        return User::where('tenant_id', Auth::user()->tenant_id)
            ->where('role', 'pegawai')
            ->orderBy('name')
            ->get();
    }

    protected function assignmentRoleOptions(): array
    {
        return [
            'penyusun_naskah' => 'Penyusun Naskah',
            'ketua_pemeriksa_konten' => 'Ketua Pemeriksa Konten',
            'anggota_pemeriksa_konten' => 'Anggota Pemeriksa Konten',
            'ketua_pemeriksa_layout' => 'Ketua Pemeriksa Layout',
            'anggota_pemeriksa_layout' => 'Anggota Pemeriksa Layout',
            'operator_infografis' => 'Operator Infografis',
            'operator_website' => 'Operator Website',
        ];
    }

    protected function tenantRequiresInfographicOperator(): bool
    {
        $tenant = Auth::user()->relationLoaded('tenant')
            ? Auth::user()->tenant
            : Auth::user()->tenant()->first();

        return optional($tenant)->type === 'provinsi';
    }

    protected function authorizeTemplate(TeamTemplate $template): void
    {
        abort_unless(
            $template->tenant_id === Auth::user()->tenant_id,
            403,
            'Anda tidak memiliki akses ke template tim kerja ini.'
        );
    }
}
