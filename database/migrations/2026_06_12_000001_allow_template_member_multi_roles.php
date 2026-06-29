<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('team_template_members')) {
            return;
        }

        $oldIndex = 'team_template_members_team_template_id_user_id_unique';
        $newIndex = 'team_template_member_role_unique';

        if ($this->indexExists('team_template_members', $oldIndex)) {
            Schema::table('team_template_members', function (Blueprint $table) use ($oldIndex) {
                $table->dropUnique($oldIndex);
            });
        }

        if (!$this->indexExists('team_template_members', $newIndex)) {
            Schema::table('team_template_members', function (Blueprint $table) use ($newIndex) {
                $table->unique(['team_template_id', 'user_id', 'assignment_role'], $newIndex);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('team_template_members')) {
            return;
        }

        $oldIndex = 'team_template_members_team_template_id_user_id_unique';
        $newIndex = 'team_template_member_role_unique';

        if ($this->indexExists('team_template_members', $newIndex)) {
            Schema::table('team_template_members', function (Blueprint $table) use ($newIndex) {
                $table->dropUnique($newIndex);
            });
        }

        if (!$this->indexExists('team_template_members', $oldIndex)) {
            Schema::table('team_template_members', function (Blueprint $table) use ($oldIndex) {
                $table->unique(['team_template_id', 'user_id'], $oldIndex);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $database = DB::getDatabaseName();
            $result = DB::select(
                'SELECT COUNT(1) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$database, $table, $index]
            );

            return ((int) ($result[0]->aggregate ?? 0)) > 0;
        }

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
};
