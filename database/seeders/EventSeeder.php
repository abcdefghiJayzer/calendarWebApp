<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Event;
use App\Models\EventGuest;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // Create some test guests
        $guests = [
            'guest1@example.com',
            'guest2@example.com',
            'guest3@example.com',
        ];

        foreach ($guests as $email) {
            EventGuest::firstOrCreate(['email' => $email]);
        }

        // Helper function to get user by division
        $getUser = function($division) {
            return User::where('division', $division)->first()->id;
        };

        // Institute-level events
        Event::create([
            'title' => 'Annual Institute Meeting',
            'description' => 'Yearly planning meeting for all sectors and divisions',
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(7)->addHours(3),
            'location' => 'Main Conference Room',
            'user_id' => $getUser('institute'),
            'color' => '#3b82f6',
            'calendar_type' => 'institute',
            'is_all_day' => false,
        ]);

        Event::create([
            'title' => 'Institute Foundation Day',
            'description' => 'Celebration of institute founding',
            'start_date' => now()->addMonths(1),
            'end_date' => now()->addMonths(1),
            'location' => 'Institute Grounds',
            'user_id' => $getUser('institute'),
            'color' => '#22c55e',
            'calendar_type' => 'institute',
            'is_all_day' => true,
        ]);

        // Sector 1 events
        Event::create([
            'title' => 'Sector 1 Quarterly Review',
            'description' => 'Review of Q2 performance',
            'start_date' => now()->addDays(14),
            'end_date' => now()->addDays(14)->addHours(2),
            'location' => 'Sector 1 Conference Room',
            'user_id' => $getUser('sector1'),
            'color' => '#ef4444',
            'calendar_type' => 'sector1',
            'is_all_day' => false,
        ]);

        // Sector 1 Division 1 events
        Event::create([
            'title' => 'Division 1 Team Building',
            'description' => 'Annual team building activity',
            'start_date' => now()->addDays(21),
            'end_date' => now()->addDays(21)->addHours(8),
            'location' => 'Resort',
            'user_id' => $getUser('sector1_div1'),
            'color' => '#eab308',
            'calendar_type' => 'sector1_div1',
            'is_all_day' => true,
        ]);

        // Sector 2 events
        Event::create([
            'title' => 'Sector 2 Planning Session',
            'description' => '2024 Planning',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(5)->addHours(4),
            'location' => 'Sector 2 Meeting Room',
            'user_id' => $getUser('sector2'),
            'color' => '#3b82f6',
            'calendar_type' => 'sector2',
            'is_all_day' => false,
        ]);

        // Additional events for other sectors...
        // Add more events as needed following the same pattern
    }
}
