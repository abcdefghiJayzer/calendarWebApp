<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationalUnitSeeder extends Seeder
{
    public function run(): void
    {
        // Delete all rows to avoid duplicates (no truncate due to FK constraints)
        DB::table('organizational_units')->delete();

        // Create sectors
        $sectors = [
            ['name' => 'Admin', 'type' => 'sector'],
            ['name' => 'Research', 'type' => 'sector'],
            ['name' => 'Development', 'type' => 'sector'],
        ];

        foreach ($sectors as $sector) {
            DB::table('organizational_units')->insert([
                'name' => $sector['name'],
                'type' => $sector['type'],
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Get all sectors
        $sectors = DB::table('organizational_units')
            ->where('type', 'sector')
            ->get();

        // Create divisions for Research and Development sectors only
        foreach ($sectors as $sector) {
            if ($sector->name !== 'Admin') {
                $divisions = [
                    ['name' => "Division 1", 'type' => 'division'],
                    ['name' => "Division 2", 'type' => 'division'],
                ];

                foreach ($divisions as $division) {
                    DB::table('organizational_units')->insert([
                        'name' => $division['name'],
                        'type' => $division['type'],
                        'parent_id' => $sector->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
} 