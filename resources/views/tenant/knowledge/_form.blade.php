<form action="{{ $formAction }}" method="POST">
    @csrf
    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Judul Materi</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $knowledgeLink->title) }}" placeholder="Contoh: Pedoman Penyusunan Publikasi BPS" required>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold">Kategori</label>
            <input type="text" name="category" class="form-control" value="{{ old('category', $knowledgeLink->category) }}" placeholder="Contoh: Video / Dokumen / Template">
        </div>

        <div class="col-md-12">
            <label class="form-label fw-semibold">Link</label>
            <input type="url" name="url" class="form-control" value="{{ old('url', $knowledgeLink->url) }}" placeholder="https://youtube.com/... atau https://drive.google.com/..." required>
        </div>

        <div class="col-md-12">
            <label class="form-label fw-semibold">Deskripsi</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Tuliskan penjelasan singkat materi knowledge.">{{ old('description', $knowledgeLink->description) }}</textarea>
        </div>

        <div class="col-md-12">
            <div class="form-check">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $knowledgeLink->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Aktif</label>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('tenant.knowledge.index') }}" class="btn btn-light border">Kembali</a>
            <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        </div>
    </div>
</form>
