<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Delete all rows to avoid duplicates (no truncate due to FK constraints)
        DB::table('users')->delete();

        // Create admin user
        DB::table('users')->insert([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'organizational_unit_id' => null,
            'is_division_head' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create sector heads
        $sectors = DB::table('organizational_units')
            ->where('type', 'sector')
            ->get();

        foreach ($sectors as $sector) {
            $emailPrefix = strtolower(str_replace(' ', '', $sector->name));
            // Special case for admin sector head
            $email = $sector->name === 'Admin' ? 'adminsector.head@example.com' : $emailPrefix . '.sector.head@example.com';
            DB::table('users')->insert([
                'name' => "{$sector->name} Sector Head",
                'email' => $email,
                'password' => Hash::make('password'),
                'organizational_unit_id' => $sector->id,
                'is_division_head' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create division heads and employees for Research and Development sectors
        $divisions = DB::table('organizational_units as divisions')
            ->join('organizational_units as sectors', 'divisions.parent_id', '=', 'sectors.id')
            ->where('divisions.type', 'division')
            ->where('sectors.name', '!=', 'Admin')
            ->select('divisions.*', 'sectors.name as sector_name')
            ->get();

        foreach ($divisions as $division) {
            $sectorName = strtolower($division->sector_name);
            $divisionName = strtolower(str_replace(' ', '', $division->name));
            
            // Create division head
            DB::table('users')->insert([
                'name' => "{$division->sector_name} {$division->name} Head",
                'email' => "{$sectorName}.{$divisionName}.head@example.com",
                'password' => Hash::make('password'),
                'organizational_unit_id' => $division->id,
                'is_division_head' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create one employee for each division
            DB::table('users')->insert([
                'name' => "{$division->sector_name} {$division->name} Employee",
                'email' => "{$sectorName}.{$divisionName}.employee@example.com",
                'password' => Hash::make('password'),
                'organizational_unit_id' => $division->id,
                'is_division_head' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
