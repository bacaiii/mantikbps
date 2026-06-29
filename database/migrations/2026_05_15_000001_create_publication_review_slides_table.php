<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('publication_review_slides')) {
            Schema::create('publication_review_slides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('publication_draft_id')->constrained('publication_drafts')->cascadeOnDelete();
                $table->foreignId('publication_team_id')->constrained('publication_teams')->cascadeOnDelete();
                $table->foreignId('publication_id')->constrained('publications')->cascadeOnDelete();
                $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
                $table->enum('review_type', ['konten', 'layout']);
                $table->string('anatomy_section');
                $table->unsignedInteger('sort_order')->default(1);
                $table->json('answers')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('saved_at')->nullable();
                $table->timestamps();

                $table->unique([
                    'publication_draft_id',
                    'publication_team_id',
                    'reviewer_id',
                    'review_type',
                    'anatomy_section',
                ], 'review_slide_unique_per_draft_reviewer_section');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_review_slides');
    }
};
