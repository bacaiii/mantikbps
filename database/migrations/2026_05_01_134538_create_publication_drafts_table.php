<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('publication_drafts')) {
            Schema::create('publication_drafts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('publication_team_id')->constrained('publication_teams')->cascadeOnDelete();
            $table->foreignId('publication_id')->constrained('publications')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->integer('version')->default(1);
            $table->string('file_path');
            $table->string('file_original_name');
            $table->string('mime_type')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_drafts');
    }
};