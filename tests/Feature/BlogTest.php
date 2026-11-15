<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\BlogArticlesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function data(string $status = 'draft'): array
    {
        return ['title' => 'Nairobi motoring guide', 'author' => 'Editorial team', 'body' => '<script>alert(1)</script> Plain text article.', 'category' => 'Guides', 'excerpt' => 'A practical guide.', 'sources' => 'Editorial notes', 'status' => $status, 'reviewed_at' => today()->toDateString()];
    }

    public function test_only_published_posts_appear_on_landing_and_blog(): void
    {
        $published = Article::create($this->data('published') + ['published_at' => today('Africa/Nairobi')]);
        $draft = Article::create($this->data());
        $future = Article::create($this->data('published') + ['published_at' => today('Africa/Nairobi')->addDay()]);
        $this->get('/')->assertInertia(fn (Assert $p) => $p->has('posts', 1)->where('posts.0.id', $published->id));
        $this->get('/blog')->assertInertia(fn (Assert $p) => $p->has('posts.data', 1));
        $this->get('/blog/'.$published->slug)->assertInertia(fn (Assert $p) => $p->where('article.body', $published->body)->has('relatedPosts')->has('popularPosts'));
        $this->assertSame(1, $published->fresh()->views_count);
        $this->get('/blog/'.$draft->slug)->assertNotFound();
        $this->get('/blog/'.$future->slug)->assertNotFound();
    }

    public function test_admin_can_create_edit_publish_and_unpublish_with_stable_slug_and_date(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/articles', $this->data())->assertSessionHasNoErrors();
        $a = Article::firstOrFail();
        $this->get('/app/admin?tab=blog')->assertInertia(fn (Assert $p) => $p->has('blogPosts.data', 1));
        $this->put('/articles/'.$a->id, $this->data('published'))->assertSessionHasNoErrors();
        $a->refresh();
        $slug = $a->slug;
        $published = $a->published_at->toDateString();
        $this->travel(2)->days();
        $this->put('/articles/'.$a->id, array_replace($this->data('published'), ['title' => 'Updated title']))->assertSessionHasNoErrors();
        $this->assertSame($slug, $a->fresh()->slug);
        $this->assertSame($published, $a->fresh()->published_at->toDateString());
        $this->put('/articles/'.$a->id, $this->data())->assertSessionHasNoErrors();
        $this->get('/blog/'.$slug)->assertNotFound();
    }

    public function test_owner_cannot_manage_blog_and_unsafe_video_links_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())->post('/articles', $this->data())->assertForbidden();
        $this->get('/app/admin?tab=blog')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/articles', $this->data() + ['video_url' => 'https://untrusted.test/embed'])->assertSessionHasErrors('video_url');
    }

    public function test_duplicate_titles_generate_distinct_slugs(): void
    {
        $a = Article::create($this->data());
        $b = Article::create($this->data());
        $this->assertNotSame($a->slug, $b->slug);
    }

    public function test_featured_images_are_private_for_drafts_and_can_be_replaced_or_removed(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $image = fn () => UploadedFile::fake()->createWithContent('cover.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jN7cAAAAASUVORK5CYII='));
        $this->actingAs($admin)->post('/articles', $this->data() + ['featured_image' => $image(), 'featured_image_alt' => 'A car at a workshop'])->assertSessionHasNoErrors();
        $a = Article::firstOrFail();
        $old = $a->featured_image_path;
        $this->assertArrayNotHasKey('featured_image_path', $a->toArray());
        $this->get('/blog/'.$a->slug.'/image')->assertOk();
        $this->actingAs(User::factory()->create())->get('/blog/'.$a->slug.'/image')->assertNotFound();
        $this->actingAs($admin)->put('/articles/'.$a->id, $this->data('published') + ['featured_image' => $image(), 'featured_image_alt' => 'A replacement image'])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($old);
        $a->refresh();
        $new = $a->featured_image_path;
        $this->actingAs(User::factory()->create())->get('/blog/'.$a->slug.'/image')->assertOk();
        $this->actingAs($admin)->put('/articles/'.$a->id, $this->data('published') + ['remove_featured_image' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($a->fresh()->featured_image_url);
        Storage::disk('local')->assertMissing($new);
        $this->get('/blog/'.$a->slug.'/image')->assertNotFound();
    }

    public function test_featured_images_reject_unsafe_or_oversize_files(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([UploadedFile::fake()->create('cover.svg', 1, 'image/svg+xml'), UploadedFile::fake()->create('cover.png', 5121, 'image/png')] as $file) {
            $this->post('/articles', $this->data() + ['featured_image' => $file, 'featured_image_alt' => 'Description'])->assertSessionHasErrors('featured_image');
        }
    }

    public function test_editorial_seeder_adds_three_posts_without_overwriting_edits(): void
    {
        $this->seed(BlogArticlesSeeder::class);
        $this->assertSame(3, Article::published()->count());
        $a = Article::firstOrFail();
        $a->update(['title' => 'Edited by administrator']);
        $this->seed(BlogArticlesSeeder::class);
        $this->assertSame(3, Article::count());
        $this->assertSame('Edited by administrator', $a->fresh()->title);
    }
}
