<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('publications') && !Schema::hasColumn('publications', 'jadwal_mulai_penyusunan')) {
            Schema::table('publications', function (Blueprint $table) {
                $table->date('jadwal_mulai_penyusunan')->nullable()->after('jadwal_mulai_pemeriksaan');
            });
        }

        if (Schema::hasTable('publication_documents')) {
            Schema::table('publication_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('publication_documents', 'source_type')) {
                    $table->string('source_type', 20)->default('file')->after('version');
                }

                if (!Schema::hasColumn('publication_documents', 'external_url')) {
                    $table->text('external_url')->nullable()->after('file_size');
                }
            });
        }



        if (Schema::hasTable('publication_drafts')) {
            Schema::table('publication_drafts', function (Blueprint $table) {
                if (!Schema::hasColumn('publication_drafts', 'source_type')) {
                    $table->string('source_type', 20)->default('file')->after('version');
                }

                if (!Schema::hasColumn('publication_drafts', 'external_url')) {
                    $table->text('external_url')->nullable()->after('file_path');
                }
            });
        }

        if (Schema::hasTable('tenants')) {
            $kodeWilayah = [
                'Provinsi Bangka Belitung' => '1900',
                'Kabupaten Bangka' => '1901',
                'Kabupaten Belitung' => '1902',
                'Kabupaten Bangka Barat' => '1903',
                'Kabupaten Bangka Tengah' => '1904',
                'Kabupaten Bangka Selatan' => '1905',
                'Kabupaten Belitung Timur' => '1906',
                'Kota Pangkalpinang' => '1971',
            ];

            foreach ($kodeWilayah as $wilayah => $kode) {
                DB::table('tenants')
                    ->where('wilayah', $wilayah)
                    ->update(['code' => $kode]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('publication_documents')) {
            Schema::table('publication_documents', function (Blueprint $table) {
                if (Schema::hasColumn('publication_documents', 'external_url')) {
                    $table->dropColumn('external_url');
                }

                if (Schema::hasColumn('publication_documents', 'source_type')) {
                    $table->dropColumn('source_type');
                }
            });
        }



        if (Schema::hasTable('publication_drafts')) {
            Schema::table('publication_drafts', function (Blueprint $table) {
                if (Schema::hasColumn('publication_drafts', 'external_url')) {
                    $table->dropColumn('external_url');
                }

                if (Schema::hasColumn('publication_drafts', 'source_type')) {
                    $table->dropColumn('source_type');
                }
            });
        }

        if (Schema::hasTable('publications') && Schema::hasColumn('publications', 'jadwal_mulai_penyusunan')) {
            Schema::table('publications', function (Blueprint $table) {
                $table->dropColumn('jadwal_mulai_penyusunan');
            });
        }
    }
};
