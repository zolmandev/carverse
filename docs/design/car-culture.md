# Car culture design update

The public experience now emphasizes editorial car features, meets, racing, and community. The existing leaderboard stays on the left; approved-event slides remain the main banner when available; upcoming events and the journal stay in the right sidebar. Light, dark, keyboard focus, reduced-motion and mobile layouts are supported. No new public performance claims or invented real owner profiles are introduced.

## Managing features

Super user → Car features uses the existing article publishing workflow. Mark an article as a car feature and add a build name, year, engine, transmission, region, public credit and modification notes. Images use the existing private draft-image storage and published-image access controls. Only published, non-future-dated features appear at `/features` and on the landing page. Public detail pages use stable article URLs. Do not include full vehicle identifiers or personal contact information in public build notes.

Run `php artisan migrate` when updating an installation. The explicitly invoked `php artisan db:seed --class=CarFeaturesSeeder` adds one clearly fictional editorial concept with an AI-generated image. It refuses production environments, is idempotent and preserves administrator edits. It has been run locally. No real ownership, mechanical specification, brand endorsement or performance result is asserted by the concept.

## Generated asset

- Mode: built-in image_gen tool, using the imagegen skill; no API/CLI fallback.
- Saved project asset: `public/images/culture/crimson-coupe-concept.png`.
- The landing fallback banner identifies the image as AI-generated. The seeded concept feature is separately labeled fictional and AI-generated.
- Final prompt:

> Use case: photorealistic-natural. Asset type: wide automotive editorial cover for Carverse KE, a Kenyan car culture website. Create a cinematic photorealistic editorial scene of a clean crimson red Japanese-inspired 1990s sports coupe parked at a closed industrial car meet venue in Nairobi at blue hour. Low three-quarter front view, car fully in frame positioned in the right two thirds, dark uncluttered space on the left for website headlines. Subtle wet tarmac reflections, charcoal architecture, warm workshop lights, realistic proportions, tasteful alloy wheels, authentic materials. Stationary car, no driving action, no people, no racing on public roads. No visible logos, no legible plates, no writing, no watermarks. Landscape widescreen composition.

The generated result includes a badge-like mark on the car; it is illustrative rather than a representation of a verified model or affiliation.
