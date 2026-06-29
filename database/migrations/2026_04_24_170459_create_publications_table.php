<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->integer('nomor')->nullable();
            $table->string('nama_publikasi');
            $table->string('estimasi_nomor_publikasi')->nullable();
            $table->string('pembuat_publikasi')->nullable();

            $table->enum('kategori', ['ARC', 'Non-ARC'])->default('ARC');

            $table->date('jadwal_rilis')->nullable();
            $table->date('jadwal_upload')->nullable();
            $table->date('jadwal_mulai_pemeriksaan')->nullable();

            $table->enum('akurasi_publikasi', ['RSE', 'Non-RSE'])->nullable();

            $table->year('tahun')->nullable();
            $table->string('periode')->nullable();
            $table->string('wilayah')->nullable();
            $table->string('jenis_publikasi')->nullable();
            $table->text('deskripsi_singkat')->nullable();

            $table->enum('status', [
                'penyusunan',
                'pemeriksaan_konten',
                'pemeriksaan_layout',
                'pengajuan_rilis',
                'rilis_selesai',
            ])->default('penyusunan');

            $table->date('tanggal_rilis_aktual')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};