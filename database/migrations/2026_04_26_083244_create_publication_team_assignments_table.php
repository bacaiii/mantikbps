<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_team_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('publication_id')->constrained('publications')->cascadeOnDelete();
            $table->foreignId('publication_team_id')->nullable()->constrained('publication_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('assignment_role', [
                'penyusun_naskah',
                'ketua_pemeriksa_konten',
                'anggota_pemeriksa_konten',
                'ketua_pemeriksa_layout',
                'anggota_pemeriksa_layout',
                'operator_website',
                'operator_infografis',
            ]);

            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['publication_id', 'user_id', 'assignment_role'],
                'pub_team_assignment_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_team_assignments');
    }
};