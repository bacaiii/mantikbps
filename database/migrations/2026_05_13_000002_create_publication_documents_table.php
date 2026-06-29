<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('publication_documents')) {
            Schema::create('publication_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('publication_team_id')->constrained('publication_teams')->cascadeOnDelete();
                $table->foreignId('publication_id')->constrained('publications')->cascadeOnDelete();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('document_type', [
                    'surat_pernyataan_rilis',
                    'naskah_pdf',
                    'naskah_zip',
                    'infografis',
                    'daftar_tabel_gambar',
                ]);
                $table->integer('version')->default(1);
                $table->string('file_path');
                $table->string('file_original_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('uploaded_at')->nullable();
                $table->timestamps();

                $table->index(['publication_team_id', 'document_type']);
                $table->index(['publication_id', 'document_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_documents');
    }
};
