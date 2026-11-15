<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\CarFeaturesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CarFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function payload(): array
    {
        return ['title' => 'A carefully built coupe', 'author' => 'Test editor', 'category' => 'Car features', 'sources' => 'Test editorial notes', 'body' => 'An owner-approved editorial build story.', 'status' => 'published', 'reviewed_at' => today()->toDateString(), 'is_car_feature' => '1', 'feature_vehicle' => 'Test coupe', 'feature_year' => 2001, 'feature_engine' => 'Owner-supplied 2.0L', 'feature_transmission' => 'Manual', 'feature_location' => 'Nairobi', 'feature_credit' => 'Owner alias with permission', 'feature_modifications' => 'Owner-supplied suspension notes.'];
    }

    public function test_only_published_car_features_appear_on_home_and_searchable_listing(): void
    {
        $published = Article::create($this->payload() + ['published_at' => today('Africa/Nairobi')]);
        Article::create(array_replace($this->payload(), ['status' => 'draft']));
        Article::create(array_replace($this->payload(), ['is_car_feature' => false, 'published_at' => today('Africa/Nairobi')]));
        Article::create(array_replace($this->payload(), ['published_at' => today('Africa/Nairobi')->addDay()]));
        $this->get('/features')->assertInertia(fn (Assert $p) => $p->component('carverse/car-features')->has('features.data', 1)->where('features.data.0.id', $published->id));
        $this->get('/features?q=missing')->assertInertia(fn (Assert $p) => $p->has('features.data', 0));
        $this->get('/features?q=coupe')->assertInertia(fn (Assert $p) => $p->has('features.data', 1));
        $this->get('/')->assertInertia(fn (Assert $p) => $p->has('featuredCars', 1)->where('featuredCars.0.id', $published->id));
        $this->get('/blog/'.$published->slug)->assertInertia(fn (Assert $p) => $p->where('article.feature_engine', 'Owner-supplied 2.0L')->where('article.is_car_feature', true));
    }

    public function test_admin_can_manage_features_but_owner_cannot_and_feature_vehicle_is_required(): void
    {
        $this->actingAs(User::factory()->create())->post('/articles', $this->payload())->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/articles', array_replace($this->payload(), ['feature_vehicle' => '']))->assertSessionHasErrors('feature_vehicle');
        $this->post('/articles', $this->payload() + ['is_demo_feature' => true])->assertSessionHasNoErrors()->assertRedirect('/app/admin?tab=blog&content=features');
        $feature = Article::firstOrFail();
        $this->assertFalse($feature->is_demo_feature);
        $this->get('/app/admin?tab=blog&content=features')->assertInertia(fn (Assert $p) => $p->has('blogPosts.data', 1));
        $this->put('/articles/'.$feature->id, array_replace($this->payload(), ['status' => 'draft']))->assertSessionHasNoErrors();
        $this->get('/features')->assertInertia(fn (Assert $p) => $p->has('features.data', 0));
    }

    public function test_demo_feature_seed_is_labeled_and_preserves_editor_changes(): void
    {
        Storage::fake('local');
        $this->seed(CarFeaturesSeeder::class);
        $feature = Article::firstOrFail();
        $this->assertTrue($feature->is_demo_feature);
        $this->assertTrue($feature->is_car_feature);
        Storage::disk('local')->assertExists($feature->featured_image_path);
        $feature->update(['title' => 'Edited concept']);
        $this->seed(CarFeaturesSeeder::class);
        $this->assertSame(1, Article::count());
        $this->assertSame('Edited concept', $feature->fresh()->title);
    }
}
