<?php

namespace Database\Seeders;

use App\Actions\Workflow;
use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CarFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Fictional car features cannot be seeded in production.');
        }
        if (Article::where('slug', 'crimson-after-hours-a-coupe-concept')->exists()) {
            return;
        }
        $path = 'blog-images/demo-crimson-coupe-concept.png';
        Storage::disk('local')->put($path, file_get_contents(public_path('images/culture/crimson-coupe-concept.png')));
        $article = Article::create(['slug' => 'crimson-after-hours-a-coupe-concept', 'title' => 'Crimson, after hours.', 'author' => 'Carverse KE Editorial', 'category' => 'Car features', 'excerpt' => 'A fictional coupe study in restraint: clean lines, deep red paint and an unmistakably late-night mood.', 'body' => "FICTIONAL CONCEPT FEATURE\n\nThis is an editorial design study, not a profile of a real owner or a documented vehicle. The image was generated with AI and does not establish the identity, specification or condition of an actual car.\n\nTHE IDEA\n\nStart with the silhouette. A low coupe, a long bonnet and a simple stance make the shape the focus. The crimson finish is the point of contrast against an industrial setting at blue hour. Warm workshop light and reflections on the tarmac give the scene its atmosphere.\n\nTHE DETAILS\n\nThe imagined build pairs dark alloy wheels with a restrained exterior. Its appeal is in the balance of the proportions and the color, rather than a list of claimed performance figures. There is no dyno result, timed run or measured specification attached to this concept.\n\nYOUR BUILD COULD BE NEXT\n\nCarverse’s car features are a space for stories about the machines and decisions behind them. Published profiles can include vehicle details, build credits, modifications and photographs supplied with permission. Full identifiers and personal contact details do not belong in a public build story.", 'sources' => 'Original fictional Carverse concept. AI-generated image; no real owner, verified specifications, endorsement or performance results are asserted.', 'status' => 'published', 'published_at' => today('Africa/Nairobi'), 'reviewed_at' => today('Africa/Nairobi'), 'featured_image_path' => $path, 'featured_image_alt' => 'AI-generated crimson sports coupe parked at a fictional industrial venue at blue hour', 'is_car_feature' => true, 'is_demo_feature' => true, 'feature_vehicle' => 'Crimson coupe concept', 'feature_location' => 'Nairobi-inspired setting', 'feature_credit' => 'Carverse concept series', 'feature_modifications' => 'Illustrative styling: crimson finish, dark alloy wheels and a clean exterior. No mechanical modifications or performance claims have been verified.']);
        Workflow::audit('article.demo_feature_created', $article, ['fictional' => true, 'ai_generated_image' => true]);
    }
}
