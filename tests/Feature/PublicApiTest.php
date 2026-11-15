<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\MotoringEvent;
use App\Models\Organization;
use App\Models\RacingEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_returns_only_verified_runs_without_evidence(): void
    {
        $user = User::factory()->create();
        $run = $this->racingRun($user, ['elapsed_ms' => 11234]);
        $this->racingRun($user, ['status' => 'pending', 'elapsed_ms' => 10000]);

        $this->getJson('/api/v1/leaderboard?distance=402&event=Test+Sprint')
            ->assertOk()
            ->assertJsonPath('data.0.id', $run->id)
            ->assertJsonPath('data.0.rank', 1)
            ->assertJsonPath('data.0.elapsed_seconds', 11.234)
            ->assertJsonPath('meta.event', 'Test Sprint')
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.gps_path')
            ->assertJsonMissingPath('data.0.gps_name')
            ->assertJsonMissingPath('data.0.review_note');
    }

    public function test_events_api_only_returns_current_approved_versions_without_private_contacts(): void
    {
        $approved = $this->event(['status' => 'approved', 'approved_version' => 1]);
        $this->event(['title' => 'Pending event', 'status' => 'pending', 'approved_version' => null]);
        $this->event(['title' => 'Expired event', 'status' => 'approved', 'approved_version' => 1, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approved->id)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.contact_email')
            ->assertJsonMissingPath('data.0.contact_phone')
            ->assertJsonMissingPath('data.0.evidence_path')
            ->assertJsonMissingPath('data.0.review_note');

        $this->getJson('/api/v1/events/'.$approved->id)->assertOk()->assertJsonPath('data.title', 'Approved event');
    }

    public function test_articles_and_car_features_are_published_and_separated(): void
    {
        $article = Article::create($this->articlePayload(['title' => 'Latest workshop guide']));
        $feature = Article::create($this->articlePayload([
            'title' => 'Featured coupe',
            'is_car_feature' => true,
            'feature_vehicle' => 'Test Coupe',
            'feature_year' => 2001,
        ]));
        Article::create($this->articlePayload(['title' => 'Private draft', 'status' => 'draft', 'published_at' => null]));

        $this->getJson('/api/v1/articles')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $article->slug)
            ->assertJsonPath('data.0.is_feature', false)
            ->assertJsonMissingPath('data.0.body');

        $this->getJson('/api/v1/features')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $feature->slug)
            ->assertJsonPath('data.0.vehicle.name', 'Test Coupe');

        $this->getJson('/api/v1/articles/'.$article->slug)
            ->assertOk()
            ->assertJsonPath('data.body', 'Public article body.');
        $this->getJson('/api/v1/articles/'.$feature->slug)->assertNotFound();
        $this->getJson('/api/v1/features/'.$article->slug)->assertNotFound();
    }

    public function test_garages_and_mechanics_are_approved_scoped_and_paginated(): void
    {
        $garage = Organization::factory()->create([
            'kind' => 'garage',
            'name' => 'Nairobi Garage',
            'phone' => '+254700000001',
            'qualifications' => 'Private evidence details',
            'review_note' => 'Private reviewer note',
        ]);
        $mechanic = Organization::factory()->create(['kind' => 'mechanic', 'name' => 'Skilled Mechanic', 'years_experience' => 12]);
        Organization::factory()->create(['kind' => 'garage', 'status' => 'pending', 'name' => 'Pending Garage']);
        Organization::factory()->create(['kind' => 'supplier', 'name' => 'Parts Supplier']);

        $this->getJson('/api/v1/garages?q=Nairobi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $garage->id)
            ->assertJsonPath('data.0.type', 'garage')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.qualifications')
            ->assertJsonMissingPath('data.0.review_note');

        $this->getJson('/api/v1/mechanics')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mechanic->id)
            ->assertJsonPath('data.0.years_experience', 12);

        $this->getJson('/api/v1/garages/'.$mechanic->id)->assertNotFound();
        $this->getJson('/api/v1/mechanics/'.$mechanic->id)->assertOk()->assertJsonMissingPath('data.qualifications');
    }

    public function test_api_rejects_oversized_pages_and_invalid_filters(): void
    {
        $this->getJson('/api/v1/events?per_page=100')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->getJson('/api/v1/leaderboard?distance=999')->assertUnprocessable()->assertJsonValidationErrors('distance');
    }

    private function racingRun(User $user, array $overrides = []): RacingEntry
    {
        return RacingEntry::create(array_replace([
            'driver' => 'Test Driver',
            'vehicle' => 'Test Coupe',
            'event' => 'Test Sprint',
            'venue' => 'Closed test venue',
            'race_date' => today(),
            'distance_metres' => 402,
            'elapsed_ms' => 12000,
            'gps_path' => 'private/run.gpx',
            'gps_name' => 'run.gpx',
            'slip_path' => 'private/slip.pdf',
            'slip_name' => 'slip.pdf',
            'status' => 'verified',
            'is_demo' => false,
            'created_by' => $user->id,
            'review_note' => 'Private review',
        ], $overrides));
    }

    private function event(array $overrides = []): MotoringEvent
    {
        return MotoringEvent::create(array_replace([
            'user_id' => User::factory()->create()->id,
            'title' => 'Approved event',
            'category' => 'meetup',
            'description' => 'Public event description.',
            'organizer_name' => 'Test Club',
            'contact_email' => 'private@example.test',
            'contact_phone' => '+254700000001',
            'venue' => 'Test Venue',
            'location' => 'Nairobi',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'evidence_path' => 'private/evidence.pdf',
            'evidence_name' => 'evidence.pdf',
            'status' => 'approved',
            'version' => 1,
            'approved_version' => 1,
            'review_note' => 'Private review note',
        ], $overrides));
    }

    private function articlePayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Public article',
            'author' => 'Test Editor',
            'body' => 'Public article body.',
            'sources' => 'Editorial source notes.',
            'status' => 'published',
            'published_at' => today('Africa/Nairobi'),
            'reviewed_at' => today('Africa/Nairobi'),
            'excerpt' => 'A public article excerpt.',
            'category' => 'Guides',
            'is_car_feature' => false,
        ], $overrides);
    }
}
