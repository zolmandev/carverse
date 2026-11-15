<?php

namespace App\Http\Controllers;

use App\Actions\Workflow;
use App\Models\Article;
use App\Models\MotoringEvent;
use App\Models\RacingEntry;
use App\Policies\WorkspacePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function home(Request $r)
    {
        $filters = $r->validate(['race_distance' => 'nullable|in:201,402,805', 'race_event' => 'nullable|string|max:160']);
        $distance = (int) ($filters['race_distance'] ?? 402);
        $demo = ! RacingEntry::where('status', '!=', 'rejected')->where('is_demo', false)->where('distance_metres', $distance)->exists();
        $events = RacingEntry::where('status', '!=', 'rejected')->where('is_demo', $demo)->where('distance_metres', $distance)->distinct()->orderBy('event')->pluck('event');
        $selectedEvent = $filters['race_event'] ?? $events->first();
        $runs = $selectedEvent ? RacingEntry::where('status', '!=', 'rejected')->where('is_demo', $demo)->where('event', $selectedEvent)->where('distance_metres', $distance)->orderBy('elapsed_ms')->orderBy('id')->limit(10)->get(['id', 'driver', 'vehicle', 'event', 'venue', 'race_date', 'distance_metres', 'elapsed_ms', 'is_demo']) : [];

        return Inertia::render('carverse/welcome', ['featuredCars' => Article::published()->where('is_car_feature', true)->latest('published_at')->latest('id')->limit(6)->get(), 'racing' => ['is_demo' => $demo && count($runs) > 0, 'distance' => $distance, 'event' => $selectedEvent, 'events' => $events, 'runs' => $runs], 'upcomingEvents' => MotoringEvent::published()->where('ends_at', '>=', now())->orderBy('starts_at')->limit(5)->get(['id', 'title', 'category', 'venue', 'location', 'starts_at', 'ends_at', 'description', 'poster_path', 'version']), 'posts' => Article::published()->latest('published_at')->latest('id')->limit(3)->get()]);
    }

    public function features(Request $r)
    {
        $d = $r->validate(['q' => 'nullable|string|max:160']);

        return Inertia::render('carverse/car-features', ['features' => Article::published()->where('is_car_feature', true)->when($d['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$s.'%')->orWhere('feature_vehicle', 'like', '%'.$s.'%')))->latest('published_at')->latest('id')->paginate(9)->withQueryString(), 'search' => $d['q'] ?? '']);
    }

    public function index(Request $r)
    {
        $d = $r->validate(['q' => 'nullable|string|max:160']);

        return Inertia::render('carverse/blog', ['posts' => Article::published()->when($d['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$s.'%')->orWhere('category', 'like', '%'.$s.'%')))->latest('published_at')->latest('id')->paginate(9)->withQueryString(), 'search' => $d['q'] ?? '']);
    }

    public function show(string $slug)
    {
        $article = Article::published()->where('slug', $slug)->firstOrFail();
        $article->increment('views_count');

        return Inertia::render('carverse/blog', [
            'article' => $article->fresh(),
            'relatedPosts' => Article::published()->where('id', '!=', $article->id)->where('category', $article->category)->latest('published_at')->limit(4)->get(),
            'popularPosts' => Article::published()->where('id', '!=', $article->id)->orderByDesc('views_count')->latest('published_at')->limit(4)->get(),
        ]);
    }

    public function image(Request $r, string $slug)
    {
        $article = Article::where('slug', $slug)->firstOrFail();
        $public = Article::published()->whereKey($article->id)->exists();
        abort_unless($public || ($r->user() && (new WorkspacePolicy)->admin($r->user())), 404);
        abort_unless($article->featured_image_path && Storage::disk('local')->exists($article->featured_image_path), 404);

        return Storage::disk('local')->response($article->featured_image_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function save(Request $r, ?Article $article = null)
    {
        abort_unless((new WorkspacePolicy)->admin($r->user()), 403);
        $d = $r->validate(['is_car_feature' => 'nullable|boolean', 'feature_vehicle' => 'nullable|required_if:is_car_feature,1|string|max:160', 'feature_year' => 'nullable|integer|min:1900|max:'.(now()->year + 1), 'feature_engine' => 'nullable|string|max:160', 'feature_transmission' => 'nullable|string|max:100', 'feature_location' => 'nullable|string|max:120', 'feature_credit' => 'nullable|string|max:160', 'feature_modifications' => 'nullable|string|max:4000', 'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120', 'featured_image_alt' => 'nullable|string|max:250', 'remove_featured_image' => 'nullable|boolean', 'title' => 'required|string|max:200', 'author' => 'required|string|max:120', 'body' => 'required|string|max:20000', 'excerpt' => 'nullable|string|max:400', 'category' => 'required|string|max:80', 'sources' => 'nullable|string|max:4000', 'video_url' => ['nullable', 'url', 'max:250', 'regex:~^https://(?:www\.)?(?:youtube\.com/watch\?v=[A-Za-z0-9_-]+|youtu\.be/[A-Za-z0-9_-]+|vimeo\.com/[0-9]+)$~'], 'status' => 'required|in:draft,published', 'reviewed_at' => 'required|date|before_or_equal:today']);
        $d['is_car_feature'] = $r->boolean('is_car_feature');
        $a = $article ?? new Article;
        $oldPath = $a->featured_image_path;
        $newPath = null;
        $previous = $a->getAttributes();
        unset($d['featured_image'], $d['remove_featured_image']);
        if ($r->hasFile('featured_image')) {
            $r->validate(['featured_image_alt' => 'required|string|max:250']);
        }
        try {
            if ($r->hasFile('featured_image')) {
                $newPath = $r->file('featured_image')->store('blog-images', 'local');
                $d['featured_image_path'] = $newPath;
            } elseif ($r->boolean('remove_featured_image')) {
                $d['featured_image_path'] = null;
                $d['featured_image_alt'] = null;
            }
            $d['sources'] = $d['sources'] ?? '';
            $d['published_at'] = $a->published_at ?? ($d['status'] === 'published' ? today('Africa/Nairobi') : null);
            DB::transaction(function () use ($a, $d, $previous) {
                $a->fill($d)->save();
                Workflow::audit('article.saved', $a, ['previous' => $previous]);
            });
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            } throw $e;
        }
        if ($oldPath && $oldPath !== $a->featured_image_path) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect('/admin/blog'.($a->is_car_feature ? '?content=features' : ''))->with('message', 'Blog post saved.');
    }
}
