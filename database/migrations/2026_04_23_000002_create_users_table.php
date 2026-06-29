<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('login_id')->unique();
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();

            $table->enum('role', [
                'admin_sistem',
                'admin_provinsi',
                'admin_kabkota',
                'tim_penyusun',
                'pemeriksa_konten',
                'pemeriksa_layout',
                'pegawai',
                'pimpinan',
            ]);

            $table->string('password');
            $table->text('password_text')->nullable(); // terenkripsi, hanya untuk tampilan admin sistem
            $table->boolean('is_active')->default(true);

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};