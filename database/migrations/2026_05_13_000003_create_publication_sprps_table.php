<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('publication_sprps')) {
            Schema::create('publication_sprps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('publication_team_id')->unique()->constrained('publication_teams')->cascadeOnDelete();
                $table->foreignId('publication_id')->constrained('publications')->cascadeOnDelete();
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('bidang_bagian')->nullable();
                $table->string('rancangan_perwajahan')->nullable();
                $table->string('judul_publikasi')->nullable();
                $table->boolean('publikasi_baru')->nullable();
                $table->string('ukuran')->nullable();
                $table->string('orientasi')->nullable();
                $table->string('frekuensi_terbit')->nullable();
                $table->string('terbitan_ke')->nullable();
                $table->year('tahun_pertama_terbit')->nullable();
                $table->string('diterbitkan_untuk')->nullable();
                $table->string('kategori_rilis')->nullable();
                $table->date('tanggal_rilis')->nullable();
                $table->string('jumlah_halaman_romawi')->nullable();
                $table->string('jumlah_halaman_arab')->nullable();
                $table->boolean('kerja_sama_luar_bps')->nullable();
                $table->json('bahasa')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_sprps');
    }
};
