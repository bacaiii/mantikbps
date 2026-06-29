<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_templates')) {
            Schema::table('document_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('document_templates', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                }

                if (!Schema::hasColumn('document_templates', 'file_size')) {
                    $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
                }
            });
        }

        if (Schema::hasTable('publication_sprps')) {
            Schema::table('publication_sprps', function (Blueprint $table) {
                if (!Schema::hasColumn('publication_sprps', 'submitted_by')) {
                    $table->foreignId('submitted_by')->nullable()->after('publication_id')->constrained('users')->nullOnDelete();
                }

                if (!Schema::hasColumn('publication_sprps', 'kategori_rilis')) {
                    $table->string('kategori_rilis')->nullable()->after('diterbitkan_untuk');
                }

                if (!Schema::hasColumn('publication_sprps', 'tanggal_rilis')) {
                    $table->date('tanggal_rilis')->nullable()->after('kategori_rilis');
                }

                if (!Schema::hasColumn('publication_sprps', 'submitted_at')) {
                    $table->timestamp('submitted_at')->nullable()->after('bahasa');
                }
            });
        }
    }

    public function down(): void
    {
        // Sengaja tidak melakukan drop kolom agar aman pada database revisi yang sudah berjalan.
    }
};
