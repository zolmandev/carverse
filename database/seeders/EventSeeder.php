<?php

namespace Database\Seeders;

use App\Actions\Workflow;
use App\Models\MotoringEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Fictional event seeding is forbidden in production.');
        }

        $admin = User::where('email', 'owner@carverse.test')->where('role', 'admin')->first()
            ?? User::where('role', 'admin')->firstOrFail();
        $evidencePath = 'events/demo/FICTIONAL-EVENT-EVIDENCE.txt';
        Storage::disk('local')->put($evidencePath, "FICTIONAL DEMO EVIDENCE.\nThese event details are training fixtures for the Carverse pilot. No real venue booking, organizer, safety approval or event endorsement is represented.\n");

        $events = [
            ['title' => 'Nairobi Night Drive', 'category' => 'meetup', 'description' => 'An evening meet for the Carverse community. Bring your build story, meet other enthusiasts and leave room for a respectful conversation about road safety.', 'organizer_name' => 'Carverse KE Club', 'venue' => 'Industrial Yard', 'location' => 'Industrial Area, Nairobi', 'starts_at' => now('Africa/Nairobi')->addDays(6)->setTime(18, 0), 'ends_at' => now('Africa/Nairobi')->addDays(6)->setTime(21, 0), 'approval_scope' => 'Organizer details, schedule and venue information reviewed.', 'review_note' => 'Event approved for listing.'],
            ['title' => 'Eastlands Sunday Car Show', 'category' => 'car_show', 'description' => 'A display day for clean daily drivers, thoughtful builds and the people who keep them moving.', 'organizer_name' => 'Carverse KE Collective', 'venue' => 'Community Grounds', 'location' => 'Eastlands, Nairobi', 'starts_at' => now('Africa/Nairobi')->addDays(12)->setTime(10, 0), 'ends_at' => now('Africa/Nairobi')->addDays(12)->setTime(16, 0), 'approval_scope' => 'Organizer details, schedule and venue information reviewed.', 'review_note' => 'Event approved for listing.'],
            ['title' => 'Track Day Prep Workshop', 'category' => 'workshop', 'description' => 'A classroom-style session about preparing a car for a closed-course day, documenting changes and asking better questions before fitting parts.', 'organizer_name' => 'Carverse KE Workshop', 'venue' => 'Training Garage', 'location' => 'Ngong Road, Nairobi', 'starts_at' => now('Africa/Nairobi')->addDays(19)->setTime(9, 0), 'ends_at' => now('Africa/Nairobi')->addDays(19)->setTime(13, 0), 'approval_scope' => 'Organizer details, schedule and venue information reviewed.', 'review_note' => 'Event approved for listing.'],
        ];

        DB::transaction(function () use ($admin, $events, $evidencePath): void {
            foreach ($events as $data) {
                $event = MotoringEvent::firstOrCreate(
                    ['title' => $data['title']],
                    $data + [
                        'user_id' => $admin->id,
                        'contact_email' => 'events@carverse.test',
                        'contact_phone' => '+254700000099',
                        'website_url' => null,
                        'poster_path' => null,
                        'evidence_path' => $evidencePath,
                        'evidence_name' => 'FICTIONAL-EVENT-EVIDENCE.txt',
                        'status' => 'approved',
                        'version' => 1,
                        'approved_version' => 1,
                        'reviewed_by' => $admin->id,
                        'reviewed_at' => now(),
                    ],
                );

                if ($event->wasRecentlyCreated) {
                    Workflow::audit('event.demo_seeded', $event, ['fictional' => true], $admin->id);
                }
            }
        });
    }
}
