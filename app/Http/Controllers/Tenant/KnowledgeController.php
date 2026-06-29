<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KnowledgeController extends Controller
{
    public function index()
    {
        $knowledgeLinks = KnowledgeLink::where(function ($q) {
            $q->whereNull('tenant_id')
                ->orWhere('tenant_id', Auth::user()->tenant_id);
        })
            ->latest()
            ->paginate(10);

        return view('tenant.knowledge.index', compact('knowledgeLinks'));
    }

    public function create()
    {
        $knowledgeLink = new KnowledgeLink(['is_active' => true]);

        return view('tenant.knowledge.create', compact('knowledgeLink'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['is_active'] = $request->boolean('is_active');

        KnowledgeLink::create($validated);

        return redirect()
            ->route('tenant.knowledge.index')
            ->with('success', 'Link knowledge berhasil ditambahkan.');
    }

    public function edit(KnowledgeLink $knowledge)
    {
        $this->authorizeKnowledge($knowledge);

        $knowledgeLink = $knowledge;

        return view('tenant.knowledge.edit', compact('knowledgeLink'));
    }

    public function update(Request $request, KnowledgeLink $knowledge)
    {
        $this->authorizeKnowledge($knowledge);

        $validated = $request->validate($this->rules(), $this->messages());
        $validated['is_active'] = $request->boolean('is_active');

        $knowledge->update($validated);

        return redirect()
            ->route('tenant.knowledge.index')
            ->with('success', 'Link knowledge berhasil diperbarui.');
    }

    public function destroy(KnowledgeLink $knowledge)
    {
        $this->authorizeKnowledge($knowledge);

        $knowledge->delete();

        return redirect()
            ->route('tenant.knowledge.index')
            ->with('success', 'Link knowledge berhasil dihapus.');
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'url' => ['required', 'url', 'max:1000'],
            'category' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Judul knowledge wajib diisi.',
            'url.required' => 'Link knowledge wajib diisi.',
            'url.url' => 'Format link knowledge tidak valid.',
        ];
    }

    protected function authorizeKnowledge(KnowledgeLink $knowledge): void
    {
        abort_unless(
            is_null($knowledge->tenant_id) || $knowledge->tenant_id === Auth::user()->tenant_id,
            403,
            'Anda tidak memiliki akses ke knowledge ini.'
        );
    }
}
