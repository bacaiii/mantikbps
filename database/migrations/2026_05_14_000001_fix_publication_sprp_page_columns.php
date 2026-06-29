<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('publication_sprps')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            if (Schema::hasColumn('publication_sprps', 'jumlah_halaman_romawi')) {
                DB::statement('ALTER TABLE publication_sprps MODIFY jumlah_halaman_romawi VARCHAR(50) NULL');
            }

            if (Schema::hasColumn('publication_sprps', 'jumlah_halaman_arab')) {
                DB::statement('ALTER TABLE publication_sprps MODIFY jumlah_halaman_arab VARCHAR(50) NULL');
            }
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan ke integer karena field romawi memang dapat berisi nilai seperti IX, X, atau XII.
    }
};
