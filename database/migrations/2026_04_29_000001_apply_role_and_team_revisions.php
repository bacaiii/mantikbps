<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin_sistem','admin_provinsi','admin_kabkota','tim_penyusun','pemeriksa_konten','pemeriksa_layout','pegawai','pimpinan') NOT NULL");

            DB::table('users')
                ->whereIn('role', ['tim_penyusun', 'pemeriksa_konten', 'pemeriksa_layout'])
                ->update(['role' => 'pegawai']);

            DB::statement("ALTER TABLE users MODIFY role ENUM('admin_sistem','admin_provinsi','admin_kabkota','pegawai','pimpinan') NOT NULL");
        }

        if (!Schema::hasTable('publication_teams')) {
            Schema::create('publication_teams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('publication_id')->unique()->constrained('publications')->cascadeOnDelete();
                $table->string('name');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('publication_team_assignments')) {
            try {
                Schema::table('publication_team_assignments', function (Blueprint $table) {
                    $table->dropUnique('pub_team_assignment_unique');
                });
            } catch (\Throwable $th) {
                // Index mungkin sudah tidak ada pada database tertentu.
            }

            if (!Schema::hasColumn('publication_team_assignments', 'publication_team_id')) {
                Schema::table('publication_team_assignments', function (Blueprint $table) {
                    $table->foreignId('publication_team_id')
                        ->nullable()
                        ->after('publication_id')
                        ->constrained('publication_teams')
                        ->cascadeOnDelete();
                });
            }

            DB::statement("ALTER TABLE publication_team_assignments MODIFY assignment_role ENUM('ketua_penyusun','anggota_penyusun','pemeriksa_konten','pemeriksa_layout','pimpinan','penyusun_naskah','ketua_pemeriksa_konten','anggota_pemeriksa_konten','ketua_pemeriksa_layout','anggota_pemeriksa_layout','operator_website','operator_infografis') NOT NULL");

            DB::table('publication_team_assignments')->where('assignment_role', 'ketua_penyusun')->update(['assignment_role' => 'penyusun_naskah']);
            DB::table('publication_team_assignments')->where('assignment_role', 'anggota_penyusun')->update(['assignment_role' => 'penyusun_naskah']);
            DB::table('publication_team_assignments')->where('assignment_role', 'pemeriksa_konten')->update(['assignment_role' => 'ketua_pemeriksa_konten']);
            DB::table('publication_team_assignments')->where('assignment_role', 'pemeriksa_layout')->update(['assignment_role' => 'ketua_pemeriksa_layout']);
            DB::table('publication_team_assignments')->where('assignment_role', 'pimpinan')->delete();

            if (Schema::hasTable('publications')) {
                $publicationIds = DB::table('publication_team_assignments')
                    ->select('publication_id')
                    ->distinct()
                    ->pluck('publication_id');

                foreach ($publicationIds as $publicationId) {
                    $publication = DB::table('publications')->where('id', $publicationId)->first();

                    if (!$publication) {
                        continue;
                    }

                    $teamId = DB::table('publication_teams')->where('publication_id', $publicationId)->value('id');

                    if (!$teamId) {
                        $teamId = DB::table('publication_teams')->insertGetId([
                            'publication_id' => $publicationId,
                            'name' => 'Tim Kerja ' . $publication->nama_publikasi,
                            'created_by' => $publication->created_by,
                            'notes' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('publication_team_assignments')
                        ->where('publication_id', $publicationId)
                        ->update(['publication_team_id' => $teamId]);
                }
            }

            DB::statement("ALTER TABLE publication_team_assignments MODIFY assignment_role ENUM('penyusun_naskah','ketua_pemeriksa_konten','anggota_pemeriksa_konten','ketua_pemeriksa_layout','anggota_pemeriksa_layout','operator_website','operator_infografis') NOT NULL");
        }
    }

    public function down(): void
    {
        // Revisi ini mengubah skema inti dan data lama, sehingga rollback aman tidak disediakan.
    }
};
