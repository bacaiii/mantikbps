<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_templates')) {
            Schema::create('document_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
                $table->enum('template_type', [
                    'surat_pernyataan_rilis',
                    'surat_persetujuan_rilis',
                ]);
                $table->string('title');
                $table->string('file_path');
                $table->string('file_original_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'template_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
