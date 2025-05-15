<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\OrganizationalUnit;
use Carbon\Carbon;

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

        // Get organizational units
        $adminSector = OrganizationalUnit::where('type', 'sector')->where('name', 'Admin')->first();
        $sectors = OrganizationalUnit::where('type', 'sector')->where('name', '!=', 'Admin')->get();
        $divisions = OrganizationalUnit::where('type', 'division')->get();

        // Get users
        $users = User::all();

        // Define color scheme for different user roles
        $colors = [
            'admin' => '#33b679',       // Admin - Green color from request
            'sectoral' => '#039be5',    // Research Sector head - Blue color from request
            'division_head' => '#e8b4bc', // Division head - Pink color from request
            'employee' => '#616161'     // Division employee - Gray color from request
        ];

        // Global events (visible to everyone)
        $adminUser = $users->where('organizational_unit_id', $adminSector->id)->first();
        if ($adminUser) {
            Event::create([
                'title' => 'Annual Planning Meeting',
                'description' => 'Yearly planning meeting for all sectors and divisions',
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(7)->addHours(3),
                'location' => 'Main Conference Room',
                'user_id' => $adminUser->id,
                'color' => $colors['admin'],
                'visibility' => 'global',
                'is_all_day' => false,
                'status' => 'confirmed',
                'private' => false,
            ]);

            Event::create([
                'title' => 'Foundation Day Celebration',
                'description' => 'Annual celebration of our foundation',
                'start_date' => now()->addMonths(1),
                'end_date' => now()->addMonths(1),
                'location' => 'Main Grounds',
                'user_id' => $adminUser->id,
                'color' => $colors['admin'],
                'visibility' => 'global',
                'is_all_day' => true,
                'status' => 'confirmed',
                'private' => false,
            ]);
        }

        // Create sector-specific events
        foreach ($sectors as $sector) {
            for ($i = 0; $i < 3; $i++) {
                $sectorUser = $users->where('organizational_unit_id', $sector->id)->first();
                if ($sectorUser) {
                    Event::create([
                        'title' => "{$sector->name} Sector Event " . ($i + 1),
                        'description' => "This event is visible to the entire {$sector->name} sector.",
                        'start_date' => Carbon::now()->addDays($i + 5)->setHour(13)->setMinute(0),
                        'end_date' => Carbon::now()->addDays($i + 5)->setHour(14)->setMinute(0),
                        'location' => "{$sector->name} Meeting Room",
                        'user_id' => $sectorUser->id,
                        'is_all_day' => false,
                        'status' => 'confirmed',
                        'color' => $colors['sectoral'],
                        'visibility' => $sector->id,
                        'private' => false,
                    ])->organizationalUnits()->attach($sector->id);
                }
            }
        }

        // Create division-specific events
        foreach ($divisions as $division) {
            for ($i = 0; $i < 2; $i++) {
                $divisionUser = $users->where('organizational_unit_id', $division->id)->first();
                if ($divisionUser) {
                    Event::create([
                        'title' => "{$division->name} Division Event " . ($i + 1),
                        'description' => "This event is only visible to the {$division->name} division.",
                        'start_date' => Carbon::now()->addDays($i + 8)->setHour(15)->setMinute(0),
                        'end_date' => Carbon::now()->addDays($i + 8)->setHour(16)->setMinute(0),
                        'location' => "{$division->name} Office",
                        'user_id' => $divisionUser->id,
                        'is_all_day' => false,
                        'status' => 'confirmed',
                        'color' => $colors['division_head'],
                        'visibility' => $division->id,
                        'private' => false,
                    ])->organizationalUnits()->attach($division->id);
                }
            }
        }

        // Create private events
        foreach ($users as $user) {
            if ($user->organizational_unit_id) {
                Event::create([
                    'title' => "Private Event for {$user->name}",
                    'description' => "This is a private event only visible to the creator.",
                    'start_date' => Carbon::now()->addDays(10)->setHour(11)->setMinute(0),
                    'end_date' => Carbon::now()->addDays(10)->setHour(12)->setMinute(0),
                    'location' => "Private Meeting Room",
                    'user_id' => $user->id,
                    'is_all_day' => false,
                    'status' => 'confirmed',
                    'color' => $colors['employee'],
                    'visibility' => $user->organizational_unit_id,
                    'private' => true,
                ])->organizationalUnits()->attach($user->organizational_unit_id);
            }
        }

        // Create all-day events
        for ($i = 0; $i < 3; $i++) {
            Event::create([
                'title' => "All Day Event " . ($i + 1),
                'description' => "This is an all-day event.",
                'start_date' => Carbon::now()->addDays($i + 12)->startOfDay(),
                'end_date' => Carbon::now()->addDays($i + 12)->endOfDay(),
                'location' => "Various Locations",
                'user_id' => $users->random()->id,
                'is_all_day' => true,
                'status' => 'confirmed',
                'color' => $colors['employee'],
                'visibility' => 'global',
                'private' => false,
            ]);
        }

        // Create events with different statuses
        $statuses = ['pending', 'confirmed', 'cancelled'];
        foreach ($statuses as $status) {
            Event::create([
                'title' => "{$status} Event",
                'description' => "This event has a {$status} status.",
                'start_date' => Carbon::now()->addDays(15)->setHour(14)->setMinute(0),
                'end_date' => Carbon::now()->addDays(15)->setHour(15)->setMinute(0),
                'location' => "Status Room",
                'user_id' => $users->random()->id,
                'is_all_day' => false,
                'status' => $status,
                'color' => $colors['employee'],
                'visibility' => 'global',
                'private' => false,
            ]);
        }
    }
}
