<?php

namespace Database\Seeders;

use App\Actions\Workflow;
use App\Models\RacingEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeaderboardSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Fictional leaderboard seeding is forbidden in production.');
        }
        $admin = User::where('email', 'owner@carverse.test')->where('role', 'admin')->first() ?? User::where('role', 'admin')->firstOrFail();
        $drivers = [['Demo Amani', 'Subaru Impreza WRX', 1], ['Demo Nia', 'Mitsubishi Lancer Evolution', 2], ['Demo Baraka', 'Volkswagen Golf GTI', 3], ['Demo Zuri', 'Toyota GR Yaris', 4], ['Demo Kito', 'Honda Civic Type R', 5]];
        Storage::disk('local')->put('racing/demo/README.txt', 'FICTIONAL DEMO FIXTURE. No real GPS recording, timing slip, evidence review or track result exists. All names and times are illustrative.');
        DB::transaction(function () use ($admin, $drivers) {
            foreach ([201 => [7210, 7450, 7450, 8012, 8290], 402 => [11240, 11488, 11903, 12410, 12820], 805 => [18210, 18940, 19520, 20010, 21590]] as $distance => $times) {
                foreach ($drivers as $i => [$driver,$vehicle,$number]) {
                    $entry = RacingEntry::firstOrCreate(['event' => 'Fictional Carverse Demo Meet', 'distance_metres' => $distance, 'driver' => $driver, 'is_demo' => true], ['vehicle' => $vehicle, 'venue' => '', 'race_date' => today('Africa/Nairobi')->subDays(2), 'elapsed_ms' => $times[$i], 'gps_path' => 'racing/demo/README.txt', 'gps_name' => 'FICTIONAL-DEMO-NOT-GPS.txt', 'slip_path' => 'racing/demo/README.txt', 'slip_name' => 'FICTIONAL-DEMO-NOT-A-TIMING-SLIP.txt', 'status' => 'verified', 'created_by' => $admin->id, 'review_note' => 'Fictional demo status only. No real evidence has been reviewed.']);
                    if ($entry->wasRecentlyCreated) {
                        Workflow::audit('racing.demo_seeded', $entry, ['fictional' => true], $admin->id);
                    }
                }
            }
        });
    }
}
