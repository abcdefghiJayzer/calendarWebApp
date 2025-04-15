<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Institute admin
        DB::table('users')->insert([
            'name' => 'Institute Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'division' => 'institute',
            'is_division_head' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // SECTOR 1
        DB::table('users')->insert([
            'name' => 'Sector 1 Head',
            'email' => 'sector1@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector1',
            'is_division_head' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Sector 1 Division 1 Head',
            'email' => 'sector1div1head@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector1_div1',
            'is_division_head' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Sector 1 Division 1 Member',
            'email' => 'sector1div1@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector1_div1',
            'is_division_head' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // SECTOR 2
        DB::table('users')->insert([
            'name' => 'Sector 2 Head',
            'email' => 'sector2@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector2',
            'is_division_head' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Sector 2 Division 1 Head',
            'email' => 'sector2div1head@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector2_div1',
            'is_division_head' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Sector 2 Division 1 Member',
            'email' => 'sector2div1@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector2_div1',
            'is_division_head' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // SECTOR 3
        DB::table('users')->insert([
            'name' => 'Sector 3 Head',
            'email' => 'sector3@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector3',
            'is_division_head' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Sector 3 Division 1 Head',
            'email' => 'sector3div1head@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector3_div1',
            'is_division_head' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Sector 3 Division 1 Member',
            'email' => 'sector3div1@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector3_div1',
            'is_division_head' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // SECTOR 4
        DB::table('users')->insert([
            'name' => 'Sector 4 Head',
            'email' => 'sector4@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector4',
            'is_division_head' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Sector 4 Division 1 Head',
            'email' => 'sector4div1head@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector4_div1',
            'is_division_head' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Sector 4 Division 1 Member',
            'email' => 'sector4div1@example.com',
            'password' => Hash::make('password'),
            'division' => 'sector4_div1',
            'is_division_head' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
