<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InspectionGuidelineSeeder extends Seeder
{
    public function run(): void
    {
        $existingDefaultRows = DB::table('inspection_guidelines')
            ->whereNull('tenant_id')
            ->count();

        if ($existingDefaultRows > 0) {
            return;
        }

        $path = database_path('data/inspection_guidelines.json');

        if (!file_exists($path)) {
            return;
        }

        $rows = json_decode(file_get_contents($path), true);

        if (!is_array($rows) || empty($rows)) {
            return;
        }

        $now = now();

        $rows = array_map(function ($row) use ($now) {
            return [
                'tenant_id' => null,
                'type' => $row['type'],
                'anatomy_section' => $row['anatomy_section'],
                'inspection_item' => $row['inspection_item'],
                'requirement_detail' => $row['requirement_detail'],
                'sort_order' => $row['sort_order'],
                'is_active' => $row['is_active'] ?? true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $rows);

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('inspection_guidelines')->insert($chunk);
        }
    }
}
