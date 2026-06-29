<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('publication_review_slides')) {
            return;
        }

        // Satukan data lama yang sempat tersimpan per pemeriksa.
        // Yang dipertahankan adalah data paling baru per draft + tim + jenis pemeriksaan + slide.
        $rows = DB::table('publication_review_slides')
            ->select('id', 'publication_draft_id', 'publication_team_id', 'review_type', 'anatomy_section', 'saved_at')
            ->orderBy('publication_draft_id')
            ->orderBy('publication_team_id')
            ->orderBy('review_type')
            ->orderBy('anatomy_section')
            ->orderByDesc('saved_at')
            ->orderByDesc('id')
            ->get();

        $seen = [];
        $deleteIds = [];

        foreach ($rows as $row) {
            $key = implode('|', [
                $row->publication_draft_id,
                $row->publication_team_id,
                $row->review_type,
                $row->anatomy_section,
            ]);

            if (isset($seen[$key])) {
                $deleteIds[] = $row->id;
                continue;
            }

            $seen[$key] = true;
        }

        foreach (array_chunk($deleteIds, 500) as $chunk) {
            DB::table('publication_review_slides')->whereIn('id', $chunk)->delete();
        }

        try {
            DB::statement('ALTER TABLE publication_review_slides DROP INDEX review_slide_unique_per_draft_reviewer_section');
        } catch (Throwable $e) {
            // Index mungkin sudah tidak ada pada database tertentu.
        }

        try {
            DB::statement('ALTER TABLE publication_review_slides ADD UNIQUE review_slide_unique_per_draft_team_section (publication_draft_id, publication_team_id, review_type, anatomy_section)');
        } catch (Throwable $e) {
            // Jika index sudah ada, migration tetap aman dijalankan ulang.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('publication_review_slides')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE publication_review_slides DROP INDEX review_slide_unique_per_draft_team_section');
        } catch (Throwable $e) {
            // Abaikan jika index sudah tidak ada.
        }

        try {
            DB::statement('ALTER TABLE publication_review_slides ADD UNIQUE review_slide_unique_per_draft_reviewer_section (publication_draft_id, publication_team_id, reviewer_id, review_type, anatomy_section)');
        } catch (Throwable $e) {
            // Abaikan jika data lama masih bentrok.
        }
    }
};
