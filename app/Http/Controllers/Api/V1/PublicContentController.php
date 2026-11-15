<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\MotoringEvent;
use App\Models\Organization;
use App\Models\RacingEntry;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicContentController extends Controller
{
    public function leaderboard(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'distance' => ['nullable', 'integer', 'in:201,402,805'],
            'event' => ['nullable', 'string', 'max:160'],
        ]);
        $distance = (int) ($filters['distance'] ?? 402);
        $isDemo = ! RacingEntry::query()
            ->where('status', 'verified')
            ->where('is_demo', false)
            ->where('distance_metres', $distance)
            ->exists();
        $events = RacingEntry::query()
            ->where('status', 'verified')
            ->where('is_demo', $isDemo)
            ->where('distance_metres', $distance)
            ->distinct()
            ->orderBy('event')
            ->pluck('event');
        $selectedEvent = in_array($filters['event'] ?? null, $events->all(), true)
            ? $filters['event']
            : $events->first();
        $runs = $selectedEvent
            ? RacingEntry::query()
                ->where('status', 'verified')
                ->where('is_demo', $isDemo)
                ->where('distance_metres', $distance)
                ->where('event', $selectedEvent)
                ->orderBy('elapsed_ms')
                ->orderBy('id')
                ->limit(100)
                ->get(['id', 'driver', 'vehicle', 'event', 'venue', 'race_date', 'distance_metres', 'elapsed_ms'])
            : collect();
        $rank = 0;
        $position = 0;
        $lastTime = null;

        return response()->json([
            'data' => $runs->map(function (RacingEntry $run) use (&$rank, &$position, &$lastTime) {
                $position++;
                if ($lastTime !== $run->elapsed_ms) {
                    $rank = $position;
                    $lastTime = $run->elapsed_ms;
                }

                return [
                    'id' => $run->id,
                    'rank' => $rank,
                    'driver' => $run->driver,
                    'vehicle' => $run->vehicle,
                    'event' => $run->event,
                    'venue' => $run->venue,
                    'race_date' => $run->race_date?->format('Y-m-d'),
                    'distance_metres' => $run->distance_metres,
                    'elapsed_ms' => $run->elapsed_ms,
                    'elapsed_seconds' => round($run->elapsed_ms / 1000, 3),
                ];
            })->values(),
            'meta' => [
                'distance_metres' => $distance,
                'event' => $selectedEvent,
                'events' => $events->values(),
                'is_demo' => $isDemo && $runs->isNotEmpty(),
                'distances' => [201, 402, 805],
            ],
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'in:meetup,car_show,track_day,motorsport,workshop,other'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);
        $events = MotoringEvent::published()
            ->where('ends_at', '>=', now())
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $term = '%'.mb_strtolower($search).'%';
                $query->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(location) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(venue) LIKE ?', [$term]);
            }))
            ->orderBy('starts_at')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return $this->paginated($events, fn (MotoringEvent $event) => $this->eventData($event));
    }

    public function event(MotoringEvent $event): JsonResponse
    {
        abort_unless($event->status === 'approved' && $event->approved_version === $event->version, 404);

        return response()->json(['data' => $this->eventData($event)]);
    }

    public function articles(Request $request): JsonResponse
    {
        return $this->articleIndex($request, false);
    }

    public function article(Article $article): JsonResponse
    {
        return $this->articleShow($article, false);
    }

    public function features(Request $request): JsonResponse
    {
        return $this->articleIndex($request, true);
    }

    public function feature(Article $article): JsonResponse
    {
        return $this->articleShow($article, true);
    }

    public function garages(Request $request): JsonResponse
    {
        return $this->providerIndex($request, 'garage');
    }

    public function garage(Organization $organization): JsonResponse
    {
        return $this->providerShow($organization, 'garage');
    }

    public function mechanics(Request $request): JsonResponse
    {
        return $this->providerIndex($request, 'mechanic');
    }

    public function mechanic(Organization $organization): JsonResponse
    {
        return $this->providerShow($organization, 'mechanic');
    }

    private function articleIndex(Request $request, bool $feature): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);
        $articles = Article::published()
            ->where('is_car_feature', $feature)
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $term = '%'.mb_strtolower($search).'%';
                $query->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(category) LIKE ?', [$term]);
                if ($feature) {
                    $query->orWhereRaw('LOWER(feature_vehicle) LIKE ?', [$term]);
                }
            }))
            ->latest('published_at')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return $this->paginated($articles, fn (Article $article) => $this->articleData($article, false));
    }

    private function articleShow(Article $article, bool $feature): JsonResponse
    {
        abort_unless(Article::published()->whereKey($article->id)->where('is_car_feature', $feature)->exists(), 404);

        return response()->json(['data' => $this->articleData($article, true)]);
    }

    private function providerIndex(Request $request, string $kind): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:160'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);
        $providers = Organization::query()
            ->where('status', 'approved')
            ->where('kind', $kind)
            ->when($filters['location'] ?? null, fn ($query, $location) => $query->whereRaw('LOWER(location) LIKE ?', ['%'.mb_strtolower($location).'%']))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $term = '%'.mb_strtolower($search).'%';
                $query->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(location) LIKE ?', [$term]);
            }))
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
        $ratings = $this->ratingMap(collect($providers->items())->pluck('id'));

        return $this->paginated($providers, fn (Organization $provider) => $this->providerData($provider, $ratings->get($provider->id)));
    }

    private function providerShow(Organization $organization, string $kind): JsonResponse
    {
        abort_unless($organization->status === 'approved' && $organization->kind === $kind, 404);
        $rating = $this->ratingMap(collect([$organization->id]))->get($organization->id);
        $reviews = Review::query()
            ->join('transactions', 'transactions.id', '=', 'reviews.transaction_id')
            ->where('transactions.organization_id', $organization->id)
            ->where('transactions.status', 'completed')
            ->whereColumn('reviews.user_id', 'transactions.user_id')
            ->where('reviews.moderation_status', 'approved')
            ->latest('reviews.created_at')
            ->limit(10)
            ->get(['reviews.id', 'reviews.quality', 'reviews.communication', 'reviews.timeliness', 'reviews.value', 'reviews.body', 'reviews.response', 'reviews.response_status', 'reviews.created_at'])
            ->map(fn ($review) => [
                'id' => $review->id,
                'ratings' => $review->only(['quality', 'communication', 'timeliness', 'value']),
                'body' => $review->body,
                'response' => $review->response_status === 'approved' ? $review->response : null,
                'created_at' => $review->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => [...$this->providerData($organization, $rating), 'reviews' => $reviews],
        ]);
    }

    private function eventData(MotoringEvent $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'category' => $event->category,
            'description' => $event->description,
            'organizer_name' => $event->organizer_name,
            'venue' => $event->venue,
            'location' => $event->location,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'timezone' => 'Africa/Nairobi',
            'website_url' => $event->website_url,
            'poster_url' => $event->poster_url,
            'web_url' => url('/events'),
        ];
    }

    private function articleData(Article $article, bool $includeBody): array
    {
        $data = [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'author' => $article->author,
            'excerpt' => $article->excerpt ?: Str::limit(strip_tags($article->body), 220),
            'category' => $article->category,
            'published_at' => $article->published_at?->format('Y-m-d'),
            'reviewed_at' => $article->reviewed_at?->format('Y-m-d'),
            'featured_image_url' => $article->featured_image_url ? url($article->featured_image_url) : null,
            'featured_image_alt' => $article->featured_image_alt,
            'video_url' => $article->video_url,
            'views_count' => $article->views_count,
            'is_feature' => $article->is_car_feature,
            'web_url' => url('/blog/'.$article->slug),
        ];

        if ($article->is_car_feature) {
            $data['vehicle'] = [
                'name' => $article->feature_vehicle,
                'year' => $article->feature_year,
                'engine' => $article->feature_engine,
                'transmission' => $article->feature_transmission,
                'location' => $article->feature_location,
                'credit' => $article->feature_credit,
                'modifications' => $article->feature_modifications,
            ];
        }

        if ($includeBody) {
            $data['body'] = $article->body;
            $data['sources'] = $article->sources;
        }

        return $data;
    }

    private function providerData(Organization $provider, mixed $rating): array
    {
        return [
            'id' => $provider->id,
            'name' => $provider->name,
            'type' => $provider->kind,
            'location' => $provider->location,
            'phone' => $provider->phone,
            'description' => $provider->description,
            'specialties' => $provider->specialties ?? [],
            'opening_hours' => $provider->opening_hours,
            'years_experience' => $provider->years_experience,
            'logo_url' => $provider->logo_url,
            'photo_url' => $provider->photo_url,
            'review_count' => (int) ($rating?->review_count ?? 0),
            'ratings' => [
                'quality' => $rating?->quality !== null ? round((float) $rating->quality, 1) : null,
                'communication' => $rating?->communication !== null ? round((float) $rating->communication, 1) : null,
                'timeliness' => $rating?->timeliness !== null ? round((float) $rating->timeliness, 1) : null,
                'value' => $rating?->value !== null ? round((float) $rating->value, 1) : null,
            ],
            'web_url' => url('/directory/'.$provider->id),
        ];
    }

    private function ratingMap(Collection $organizationIds): Collection
    {
        if ($organizationIds->isEmpty()) {
            return collect();
        }

        return Review::query()
            ->join('transactions', 'transactions.id', '=', 'reviews.transaction_id')
            ->whereIn('transactions.organization_id', $organizationIds)
            ->where('reviews.moderation_status', 'approved')
            ->where('transactions.status', 'completed')
            ->whereColumn('reviews.user_id', 'transactions.user_id')
            ->selectRaw('transactions.organization_id, COUNT(*) as review_count, AVG(quality) as quality, AVG(communication) as communication, AVG(timeliness) as timeliness, AVG(value) as value')
            ->groupBy('transactions.organization_id')
            ->get()
            ->keyBy('organization_id');
    }

    private function paginated(LengthAwarePaginator $paginator, callable $transform): JsonResponse
    {
        return response()->json([
            'data' => collect($paginator->items())->map($transform)->values(),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'previous' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
