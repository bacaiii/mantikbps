<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            if (!Schema::hasColumn('publications', 'draft_submitted_at')) {
                $table->timestamp('draft_submitted_at')->nullable()->after('tanggal_rilis_aktual');
            }

            if (!Schema::hasColumn('publications', 'content_review_started_at')) {
                $table->timestamp('content_review_started_at')->nullable()->after('draft_submitted_at');
            }

            if (!Schema::hasColumn('publications', 'content_review_finished_at')) {
                $table->timestamp('content_review_finished_at')->nullable()->after('content_review_started_at');
            }

            if (!Schema::hasColumn('publications', 'layout_review_started_at')) {
                $table->timestamp('layout_review_started_at')->nullable()->after('content_review_finished_at');
            }

            if (!Schema::hasColumn('publications', 'layout_review_finished_at')) {
                $table->timestamp('layout_review_finished_at')->nullable()->after('layout_review_started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $columns = [
                'draft_submitted_at',
                'content_review_started_at',
                'content_review_finished_at',
                'layout_review_started_at',
                'layout_review_finished_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('publications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};