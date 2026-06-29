<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('publication_reviews')) {
            Schema::create('publication_reviews', function (Blueprint $table) {
                $table->id();

                $table->foreignId('publication_draft_id')
                    ->constrained('publication_drafts')
                    ->cascadeOnDelete();

                $table->foreignId('publication_team_id')
                    ->constrained('publication_teams')
                    ->cascadeOnDelete();

                $table->foreignId('publication_id')
                    ->constrained('publications')
                    ->cascadeOnDelete();

                $table->foreignId('reviewer_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->enum('review_type', ['konten', 'layout']);
                $table->enum('result', ['disetujui', 'revisi']);

                $table->json('checklist')->nullable();
                $table->text('notes')->nullable();

                $table->timestamp('reviewed_at')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_reviews');
    }
};