<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('publications')) {
            Schema::table('publications', function (Blueprint $table) {
                if (!Schema::hasColumn('publications', 'infographic_review_started_at')) {
                    $table->timestamp('infographic_review_started_at')->nullable()->after('layout_review_finished_at');
                }

                if (!Schema::hasColumn('publications', 'infographic_review_finished_at')) {
                    $table->timestamp('infographic_review_finished_at')->nullable()->after('infographic_review_started_at');
                }

                if (!Schema::hasColumn('publications', 'leadership_approved_at')) {
                    $table->timestamp('leadership_approved_at')->nullable()->after('infographic_review_finished_at');
                }

                if (!Schema::hasColumn('publications', 'website_packaged_at')) {
                    $table->timestamp('website_packaged_at')->nullable()->after('leadership_approved_at');
                }

                if (!Schema::hasColumn('publications', 'ready_for_release_at')) {
                    $table->timestamp('ready_for_release_at')->nullable()->after('website_packaged_at');
                }
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE publications MODIFY status ENUM('penyusunan','pemeriksaan_konten','pemeriksaan_layout','pemeriksaan_infografis','persetujuan_pimpinan','operator_website','siap_rilis','pengajuan_rilis','rilis_selesai') NOT NULL DEFAULT 'penyusunan'");
            }
        }

        if (Schema::hasTable('publication_reviews') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE publication_reviews MODIFY review_type ENUM('konten','layout','infografis','pimpinan') NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('publications')) {
            DB::table('publications')
                ->whereIn('status', ['pemeriksaan_infografis', 'persetujuan_pimpinan', 'operator_website', 'siap_rilis'])
                ->update(['status' => 'pengajuan_rilis']);

            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE publications MODIFY status ENUM('penyusunan','pemeriksaan_konten','pemeriksaan_layout','pengajuan_rilis','rilis_selesai') NOT NULL DEFAULT 'penyusunan'");
            }

            Schema::table('publications', function (Blueprint $table) {
                foreach ([
                    'ready_for_release_at',
                    'website_packaged_at',
                    'leadership_approved_at',
                    'infographic_review_finished_at',
                    'infographic_review_started_at',
                ] as $column) {
                    if (Schema::hasColumn('publications', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('publication_reviews') && DB::getDriverName() === 'mysql') {
            DB::table('publication_reviews')
                ->whereIn('review_type', ['infografis', 'pimpinan'])
                ->update(['review_type' => 'layout']);

            DB::statement("ALTER TABLE publication_reviews MODIFY review_type ENUM('konten','layout') NOT NULL");
        }
    }
};
