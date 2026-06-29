<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_review_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->constrained('publications')->cascadeOnDelete();
            $table->foreignId('publication_team_id')->constrained('publication_teams')->cascadeOnDelete();
            $table->foreignId('publication_document_id')->nullable()->constrained('publication_documents')->nullOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('page_number');
            $table->string('section_name');
            $table->string('note_type')->default('revisi'); // revisi, saran, catatan
            $table->text('note');
            $table->string('status')->default('belum_diperbaiki'); // belum_diperbaiki, sudah_diperbaiki, diverifikasi
            $table->timestamps();

            $table->index(['publication_team_id', 'page_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_review_notes');
    }
};
