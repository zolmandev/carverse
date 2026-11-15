<?php

namespace Database\Seeders;

use App\Actions\Workflow;
use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            ['slug' => 'reading-carverse-fitment-labels', 'title' => 'A potential match is a starting point: understanding part fitment', 'category' => 'Parts', 'excerpt' => 'What Carverse’s compatibility labels tell you—and which questions remain before you choose a part.', 'body' => <<<'TEXT'
Two parts can look almost identical and still belong to different vehicle configurations. That is why Carverse separates a part’s listing from an assessment of whether it fits a particular vehicle.

START WITH VEHICLE CONTEXT

A make and model narrow the search, but they do not capture every difference. A listing may need engine, transmission, production-year or variant details before a supplier can assess it. Full vehicle identifiers should be shared only where needed through an appropriate private channel, rather than posted in a public community discussion.

READ THE LABEL LITERALLY

“Confirmed for this vehicle” means an explicit assessment has been recorded with a reference or evidence, an assessor, a date and relevant vehicle details. It is a statement about that recorded assessment, rather than a promise covering every possible installation circumstance.

“Potential match—confirmation required” means more checking is needed. “Compatibility unknown” means no usable assessment is available. “Not compatible” means the recorded assessment indicates a mismatch. None of those labels should be silently upgraded because a listing mentions the same model name.

KEEP THE QUESTIONS SEPARATE

Fitment, supplier approval and part authenticity answer different questions. A supplier’s approved application does not establish the authenticity of every item it lists, and a compatibility assessment does not establish the condition of the part.

Read the assessment alongside the actual item description, reference numbers and condition. If the vehicle details or part specification have changed, an earlier confirmation may need a fresh assessment. The useful question is not simply “Does it fit?” but “What was checked, against which vehicle details, and when?”
TEXT],
            ['slug' => 'what-provider-approval-means', 'title' => 'What an approved garage profile actually tells you', 'category' => 'Specialists', 'excerpt' => 'Read the approval scope, date and review breakdown to understand the evidence behind a provider profile.', 'body' => <<<'TEXT'
An approval badge is most useful when it comes with an explanation. On Carverse, a provider profile shows the scope and date of the checks recorded during application review. Those details help you understand the badge without treating it as a guarantee of every future job.

LOOK BEHIND THE BADGE

Open the profile and read what was checked. An application review covers the evidence submitted for that review; it does not automatically establish manufacturer authorization, insurance cover or expertise for every vehicle. If a claim matters to your decision, distinguish what the profile explicitly supports from what you would still need to establish with the provider.

MATCH THE PROFILE TO YOUR NEED

Supported makes and specialist services provide context. A general workshop description and a specific area of expertise are different pieces of information. The location, opening hours and contact details help you start a conversation with the actual business.

READ REVIEWS AS A BREAKDOWN

Carverse separates workmanship or product quality, communication, timeliness and value. The number of ratings matters alongside the average: a profile with very few reviews offers a smaller picture of customer experience. Reviews are linked to completed transactions and subject to moderation; they do not describe every customer or every service the business has ever provided.

UNDERSTAND WHAT MODERATION DOES

Review moderation and complaint handling are separate from provider application approval. Accepting a case for review does not establish that an allegation is true. Public complaint summaries require a deliberate, redacted publication decision rather than automatic exposure of private evidence.

A good profile makes its evidence readable. Use its scope, dates and customer feedback together, and keep any unanswered questions explicit when you contact the specialist.
TEXT],
            ['slug' => 'discover-car-communities-on-carverse', 'title' => 'Find your car community without sharing your whole life', 'category' => 'Community', 'excerpt' => 'How vehicle-specific WhatsApp links work on Carverse, and where the public directory ends and the external group begins.', 'body' => <<<'TEXT'
A conversation with people who know your model can begin with a small question: what does this trim label mean, where can I find an owners’ handbook, or who else enjoys the same car? Carverse’s community directory helps you find a relevant starting point through links organized around vehicle brands and models.

CHOOSE THE VEHICLE CONTEXT

The directory separates brand, model and, where relevant, trim. A model-specific group may be more closely related to your question than a broad automotive group, but the label alone does not establish who is in the group or the quality of its advice.

JOIN IS AN EXTERNAL ACTION

The Join button opens a WhatsApp invitation. Carverse does not automatically enroll you. The external invitation is the point at which you can inspect the destination and decide whether to continue. Participation is then subject to the group’s rules and the external service’s own settings.

KEEP PUBLIC QUESTIONS LIMITED

A vehicle description can often be discussed without posting a full chassis number, personal phone number, home address or an unredacted invoice. Think about what the question actually needs before sharing an attachment. Information copied into a community conversation may travel beyond the original thread.

A RECOMMENDATION IS A LEAD

A member’s recommendation is useful context, but it is separate from a provider’s recorded approval scope or a part’s compatibility assessment on Carverse. A familiar model name or an enthusiastic reply does not establish fitment, authenticity or business credentials.

Community conversations work best as a place to exchange experience and discover questions worth asking. Keep the source of a claim visible, check the relevant profile or assessment when available, and leave yourself room to confirm the details independently.
TEXT],
        ];
        DB::transaction(function () use ($posts) {
            foreach ($posts as $post) {
                $article = Article::firstOrCreate(['slug' => $post['slug']], $post + ['author' => 'Carverse KE Editorial', 'sources' => 'Original Carverse KE product guide, based on the platform’s implemented community, fitment and provider-review workflows. No external technical specifications or manufacturer endorsements are asserted.', 'status' => 'published', 'published_at' => today('Africa/Nairobi'), 'reviewed_at' => today('Africa/Nairobi')]);
                if ($article->wasRecentlyCreated) {
                    Workflow::audit('article.published', $article, ['source' => 'BlogArticlesSeeder']);
                }
            }
        });
    }
}
