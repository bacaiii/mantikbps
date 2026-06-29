<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\InspectionGuideline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InspectionGuidelineController extends Controller
{
    public function index(Request $request)
    {
        $activeType = in_array($request->get('type'), ['konten', 'layout', 'template'], true)
            ? $request->get('type')
            : 'konten';

        $guidelines = InspectionGuideline::where(function ($q) {
                $q->whereNull('tenant_id')
                    ->orWhere('tenant_id', Auth::user()->tenant_id);
            })
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $groupedGuidelines = $guidelines
            ->groupBy('type')
            ->map(function ($typeGroup) {
                return $typeGroup
                    ->groupBy('anatomy_section')
                    ->map(function ($anatomyGroup) {
                        return $anatomyGroup->groupBy('inspection_item');
                    });
            });

        $structures = $this->guidelineStructures();

        $stats = [
            'konten' => [
                'cards' => $guidelines->where('type', 'konten')->groupBy('inspection_item')->count(),
                'items' => $guidelines->where('type', 'konten')->count(),
                'active' => $guidelines->where('type', 'konten')->where('is_active', true)->count(),
            ],
            'layout' => [
                'cards' => $guidelines->where('type', 'layout')->groupBy(fn ($item) => $item->anatomy_section . '|' . $item->inspection_item)->count(),
                'items' => $guidelines->where('type', 'layout')->count(),
                'active' => $guidelines->where('type', 'layout')->where('is_active', true)->count(),
            ],
        ];

        $documentTemplates = DocumentTemplate::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('template_type', array_keys($this->templateTypes()))
            ->get()
            ->keyBy('template_type');

        $templateTypes = $this->templateTypes();

        return view('tenant.inspection_guidelines.index', compact(
            'activeType',
            'guidelines',
            'groupedGuidelines',
            'structures',
            'stats',
            'documentTemplates',
            'templateTypes'
        ));
    }

    public function create(Request $request)
    {
        $type = in_array($request->get('type'), ['konten', 'layout'], true)
            ? $request->get('type')
            : 'konten';

        $structures = $this->guidelineStructures();
        $defaultAnatomy = array_key_first($structures[$type] ?? []) ?: ($type === 'layout' ? 'Depan' : 'Isi');
        $anatomy = $request->get('anatomy_section', $defaultAnatomy);
        $defaultItem = $structures[$type][$anatomy][0] ?? (collect($structures[$type] ?? [])->flatten()->first() ?: '');

        $guideline = new InspectionGuideline([
            'type' => $type,
            'anatomy_section' => $anatomy,
            'inspection_item' => $request->get('inspection_item', $defaultItem),
            'is_active' => true,
            'sort_order' => $this->nextSortOrder($type),
        ]);

        return view('tenant.inspection_guidelines.create', compact('guideline', 'structures'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $this->nextSortOrder($validated['type']);

        InspectionGuideline::create($validated);

        return redirect()
            ->route('tenant.inspection-guidelines.index', ['type' => $validated['type']])
            ->with('success', 'Rincian pedoman pemeriksaan berhasil ditambahkan.');
    }

    public function edit(InspectionGuideline $inspectionGuideline)
    {
        $this->authorizeGuideline($inspectionGuideline);

        $guideline = $inspectionGuideline;
        $structures = $this->guidelineStructures();

        return view('tenant.inspection_guidelines.edit', compact('guideline', 'structures'));
    }

    public function update(Request $request, InspectionGuideline $inspectionGuideline)
    {
        $this->authorizeGuideline($inspectionGuideline);

        $validated = $request->validate($this->updateRules(), $this->messages());

        $inspectionGuideline->update([
            'requirement_detail' => $validated['requirement_detail'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('tenant.inspection-guidelines.index', ['type' => $inspectionGuideline->type])
            ->with('success', 'Rincian pedoman pemeriksaan berhasil diperbarui.');
    }

    public function destroy(InspectionGuideline $inspectionGuideline)
    {
        $this->authorizeGuideline($inspectionGuideline);

        $type = $inspectionGuideline->type;
        $inspectionGuideline->delete();

        return redirect()
            ->route('tenant.inspection-guidelines.index', ['type' => $type])
            ->with('success', 'Pedoman pemeriksaan berhasil dihapus.');
    }

    public function destroyCustomSection(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['konten', 'layout'])],
            'anatomy_section' => ['required', 'string', 'max:255'],
            'inspection_item' => ['required', 'string', 'max:255'],
        ]);

        $deleted = InspectionGuideline::where('tenant_id', Auth::user()->tenant_id)
            ->where('type', $validated['type'])
            ->where('anatomy_section', $validated['anatomy_section'])
            ->where('inspection_item', $validated['inspection_item'])
            ->delete();

        return redirect()
            ->route('tenant.inspection-guidelines.index', ['type' => $validated['type']])
            ->with('success', $deleted > 0
                ? 'Sub-anatomi tambahan berhasil dihapus beserta seluruh rinciannya.'
                : 'Tidak ada sub-anatomi tambahan yang dapat dihapus.');
    }

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'template_type' => ['required', Rule::in(array_keys($this->templateTypes()))],
            'template_file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
        ], [
            'template_type.required' => 'Jenis template wajib dipilih.',
            'template_file.required' => 'File template wajib diunggah.',
            'template_file.mimes' => 'Template harus berformat PDF, DOC, DOCX, XLS, atau XLSX.',
            'template_file.max' => 'Ukuran template maksimal 10MB.',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $file = $request->file('template_file');
        $path = $file->store('document-templates/' . $tenantId, 'public');

        $oldTemplate = DocumentTemplate::where('template_type', $validated['template_type'])
            ->where(function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            })
            ->first();

        if ($oldTemplate && $oldTemplate->file_path && Storage::disk('public')->exists($oldTemplate->file_path)) {
            Storage::disk('public')->delete($oldTemplate->file_path);
        }

        $payload = [
            'tenant_id' => $tenantId,
            'template_type' => $validated['template_type'],
            'title' => $this->templateTypes()[$validated['template_type']]['title'],
            'file_path' => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ];

        if ($oldTemplate) {
            $oldTemplate->update($payload);
        } else {
            DocumentTemplate::create($payload);
        }

        return redirect()
            ->route('tenant.inspection-guidelines.index', ['type' => 'template'])
            ->with('success', 'Template dokumen berhasil disimpan.');
    }

    public function destroyTemplate(DocumentTemplate $documentTemplate)
    {
        abort_unless(
            $documentTemplate->tenant_id === Auth::user()->tenant_id,
            403,
            'Anda tidak memiliki akses ke template ini.'
        );

        if ($documentTemplate->file_path && Storage::disk('public')->exists($documentTemplate->file_path)) {
            Storage::disk('public')->delete($documentTemplate->file_path);
        }

        $documentTemplate->delete();

        return redirect()
            ->route('tenant.inspection-guidelines.index', ['type' => 'template'])
            ->with('success', 'Template dokumen berhasil dihapus.');
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['konten', 'layout'])],
            'anatomy_section' => ['required', 'string', 'max:255'],
            'inspection_item' => ['required', 'string', 'max:255'],
            'requirement_detail' => ['required', 'string'],
        ];
    }

    protected function updateRules(): array
    {
        return [
            'requirement_detail' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'type.required' => 'Jenis pedoman wajib dipilih.',
            'anatomy_section.required' => 'Anatomi publikasi wajib diisi.',
            'inspection_item.required' => 'Bagian pemeriksaan wajib diisi.',
            'requirement_detail.required' => 'Rincian pemeriksaan wajib diisi.',
        ];
    }

    protected function nextSortOrder(string $type): int
    {
        return ((int) InspectionGuideline::where('type', $type)->max('sort_order')) + 1;
    }

    protected function authorizeGuideline(InspectionGuideline $guideline): void
    {
        abort_unless(
            is_null($guideline->tenant_id) || $guideline->tenant_id === Auth::user()->tenant_id,
            403,
            'Anda tidak memiliki akses ke pedoman ini.'
        );
    }

    protected function templateTypes(): array
    {
        return [
            'surat_persetujuan_rilis' => [
                'title' => 'Surat Persetujuan Rilis',
                'description' => 'Template ini disiapkan untuk Operator Website saat publikasi sudah masuk tahap siap rilis.',
                'icon' => 'bi-file-earmark-check',
                'color' => 'success',
            ],
        ];
    }

    protected function guidelineStructures(): array
    {
        return [
            'konten' => [
                'Isi' => [
                    'Pembatas Bab',
                    'Infografis',
                    'Narasi',
                    'Running Title',
                    'Tabel',
                    'Gambar',
                ],
            ],
            'layout' => [
                'Depan' => [
                    'Kover Depan',
                    'Halaman Judul',
                    'Halaman Katalog',
                ],
                'Pendahuluan' => [
                    'Tim Penyusun',
                    'Kata Sambutan',
                    'Kata Pengantar',
                    'Abstraksi',
                    'Daftar Isi',
                    'Daftar Tabel',
                    'Daftar Gambar',
                    'Daftar Lampiran',
                    'Penjelasan Umum',
                    'Penjelasan Teknis',
                ],
                'Penutup' => [
                    'Daftar Pustaka',
                    'Indeks',
                    'Daftar Istilah/Glosarium',
                    'Lampiran',
                    'Kover Belakang',
                ],
            ],
        ];
    }
}
