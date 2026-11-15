# Carverse KE

## Reviews and cases for approved providers

Open **Provider approvals → Approved**, then choose **Reviews** or **Cases** beside a garage or mechanic. Both show totals and pending moderation counts. The selected provider's records are paginated, filterable by moderation status, and include recent moderation history.

Reviews require an administrator's recorded publication decision, reason and content check. Approval is allowed only for the owning customer's completed transaction. Only approved, eligible reviews contribute to public rating counts and averages. Original ratings are not edited by moderation. Provider replies wait for review separately; submitting a reply does not hide an already approved review or alter its rating contribution.

Case approval accepts the evidence-backed submission for review; it does not establish the allegation as true, resolve the dispute, or publish the case. Administrators can inspect private evidence, record a resolution, and separately approve an intentionally redacted public summary. The summary requires an approved case, a recorded resolution, a publication reason and redaction confirmation. Reopening moderation, changing a resolution, or an appeal withdraws the earlier summary until a new publication decision is recorded. Public pages never include case evidence or the private case body.

Run `php artisan migrate` after updating. Existing reviews and cases begin with pending moderation; their original contents and ratings are retained. This management addition does not re-enable retired booking or customer-submission routes.


## Garage and mechanic approvals and logos

Provider applicants can register as a garage, skilled mechanic, or parts supplier through `/app/provider`. Applications include private supporting evidence and may include claimed experience, private qualification details, a separate business logo (PNG/JPEG/WebP, up to 2 MB) and a provider photo. New applications remain pending. Existing members can add or replace their organization's logo in the same workspace.

The super-user menu now includes **Provider approvals** (`/app/admin?tab=approvals`), with Pending, Approved and Rejected tabs, provider-type/search filters, private document downloads, applicant contacts, logo preview, reviewer attribution and decision notes. Approval requires available evidence, explicit confirmation of review, public verification scope and private feedback. Only approved providers are listed publicly and gain organization staff access. Rejection or a return to pending removes both. Decision feedback is visible to the applicant; qualifications and review notes are excluded from public profiles. Recorded checks describe the reviewed scope, not a blanket guarantee of skill or work quality.

Pending provider logos are visible only to administrators and organization members. Approved provider logos appear on their directory profiles. Logos use private storage with a scoped image endpoint; SVG uploads are not accepted. Run `php artisan migrate` to apply the provider review and logo columns.


## Cars management

The super user now has **Cars management** alongside WhatsApp groups and racing. Manage separate brands, models and trims/variants, with linked dropdowns in the WhatsApp group form. The seeded catalog has 422 brands, 11,054 bundled models (plus existing local model names), 52,124 trim/configuration records and 184 sourced logos. Coverage is broad rather than universal; year/market and equipment-grade versus powertrain-variant distinctions are retained.

For installation or deployment: `php artisan migrate`, then `php -d memory_limit=512M artisan db:seed --class=CarCatalogSeeder --force`. This catalog-only seeder preserves administrator edits and creates no demo accounts. See [catalog documentation](docs/catalog/README.md) for sources, attribution, logo handling and coverage limits.


## Current super-user workspace

`owner@carverse.test` with the existing demo password opens `/app/admin` directly. Its workspace contains only WhatsApp group link management and Drag Racing Leaderboards. Group links support creation, search, editing and removal.

Racing entries require a driver display name, vehicle, event, venue, date, distance, elapsed time, GPS evidence and timing slip. Private evidence files are downloadable by administrators only. New runs remain pending. Verification requires a recorded manual review of both evidence sources and confirmation that they describe the same run. Only verified entries rank, within the selected event and distance, ordered by elapsed milliseconds; equal times share a rank. Reopening or rejecting a run removes it from the verified leaderboard. No automatic GPS timing or equipment certification is claimed. No fictional racing results are seeded.

Run `php artisan migrate` after updating to create the racing-entry table. The super-user racing workspace is `/app/admin?tab=racing`.


> Current scope: Requests & bookings, Vehicle passport, and Maintenance have been removed from the product. Their navigation, dashboard sections, page/submission routes, public history links, and scheduled reminder delivery are disabled. Stored historical data and domain regression tests are retained. The original workflow descriptions below document that archived implementation, not currently available features.


A working Laravel + Inertia automotive pilot for Nairobi. Owners add vehicles, request parts or service, accept a provider quote, record a demo payment, follow fulfilment, confirm completion, keep a private service history, review a completed transaction, and plan maintenance.

**Seed data is fictional.** Provider checks in the demo are simulations, not real endorsements or verification. Demo payments never move money.

## Installed stack

| Component | Locked version |
| --- | --- |
| PHP used for verification | 8.3.6 |
| Laravel | 13.42.0 |
| Inertia Laravel adapter | 3.7.0 |
| Inertia React adapter | 3.11.0 |
| React | 19.0.0 |
| TypeScript | 5.7.3 |
| Tailwind CSS | 4.0.8 |
| Vite | 6.1.1 |
| PHPUnit | 11.5.57 |

Based on the official `laravel/react-starter-kit` (Packagist v1.0.1), with its dependencies upgraded to compatible stable Laravel 13 / Inertia 3 releases. Existing starter authentication, session routing, TypeScript, Tailwind and shadcn components are retained. `composer.lock` and `package-lock.json` pin the actual installations.

Official references checked November 10, 2026: [Laravel releases](https://laravel.com/framework/docs/releases), [React starter kit](https://laravel.com/framework/docs/starter-kits), [Safaricom Daraja portal](https://developer.safaricom.co.ke/), [Daraja API catalog](https://developer.safaricom.co.ke/apis). The Daraja portal's interactive endpoint documentation was not accessible to the available documentation reader. The adapter is therefore explicitly an **unverified sandbox integration**, covered with HTTP fakes, pending credentialed testing against the current portal contracts.

## Installation with PostgreSQL

Requirements: PHP 8.3+, Composer 2, Node 22+, PostgreSQL, and PHP extensions `pdo_pgsql`, `mbstring`, `xml`, `ctype`, `curl`, `dom`, `fileinfo`, `openssl`, `tokenizer`, `session`. SQLite is needed for the default automated test database.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Set `DB_PASSWORD` to a local password in `.env`. Set `APP_URL` to your development URL. PostgreSQL is the default in `.env.example`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=carverse
DB_USERNAME=carverse
DB_PASSWORD=
PAYMENT_DRIVER=demo
```

Use an existing PostgreSQL database/user, or start the supplied development database with Docker:

```bash
docker compose up -d postgres
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```

The application is available at `http://127.0.0.1:8000`. For asset development, run `npm run dev` in another terminal, or `composer run dev` for the starter's combined development processes.

**This workspace's local preview:** `http://127.0.0.1:8910`, using an ignored SQLite development database. PostgreSQL and `pdo_pgsql` are absent in the build environment, so PostgreSQL runtime checks were not performed. The portable migrations and PostgreSQL configuration are supplied; run the tests and migration checks against PostgreSQL before deployment. SQLite was used for local execution and automated tests, not represented as a verified PostgreSQL deployment.

For a quick SQLite preview on another machine, set `DB_CONNECTION=sqlite`, remove `DB_DATABASE` or set it to the absolute path to `database/database.sqlite`, create that file, and run migrations and seeding. Do not run `migrate:fresh` on a database containing work you need to preserve.

## Demo accounts

Run `php artisan db:seed` in a non-production environment. Password for these **development-only** accounts: `CarverseDemo!2026`.

| Account | Purpose |
| --- | --- |
| `owner@carverse.test` | Super user / administrator with full pilot administration and existing vehicles |
| `owner2@carverse.test` | Separate owner for access-control exploration |
| `garage1@carverse.test` … `garage5@carverse.test` | Staff of five fictional garages |
| `supplier1@carverse.test` … `supplier3@carverse.test` | Staff of three fictional parts suppliers |
| `moderator@carverse.test` | Complaints and appeals; no provider approval or financial permissions |
| `admin@carverse.test` | Provider verification, users, memberships, records, costs, content and audit history |

The seeder refuses to run when `APP_ENV=production`. It is safe to rerun to add missing fixtures; existing work is not deleted. The fictional fixture evidence files are private plain-text training notes; user evidence uploads accept PDF, JPEG and PNG only, up to 5 MB. Demo chassis numbers, references, phones, reviews, and provider checks are not real-world claims.

Registration uses Laravel session authentication plus a six-digit SMS code sent through Hashback. New users provide a Kenyan mobile number and must verify it before entering the workspace; codes expire after 10 minutes and are stored only as hashes. Configure `HASHBACK_SMS_API_KEY` (and optionally `HASHBACK_SMS_BASE_URL`) for delivery. Email remains the sign-in and password-reset identifier, but is not a verification gate. Demo accounts are already phone-verified.

## Working workflows

The public, read-only mobile API is documented in [`docs/MOBILE_API.md`](docs/MOBILE_API.md). It exposes versioned JSON endpoints for leaderboard results, upcoming events, articles, car features, approved garages and approved skilled mechanics.

- **Garage:** multiple vehicles, imported frame/chassis identifiers, encrypted identifiers at rest, private vehicle photos, active vehicle selection and dated odometer readings. Registration numbers are not collected.
- **Parts:** approved supplier listings, reference/category search, OEM references, conditions, stock, optional prices and photos, quote-only listings, supplier editing, four fitment states. A fitment assessment records assessor, time, evidence, relevant vehicle/part snapshots and fingerprints. Changes to identifying vehicle details or part specifications invalidate the current badge. Stock or price changes do not invalidate technical fitment.
- **Quotes/orders/bookings:** one accepted quote per request, database uniqueness and request row locks, itemized integer-minor-unit totals, immutable accepted snapshots, explicitly authorized status transitions, cancellation, disputes and owner confirmation. Quote edits never change accepted terms. Provider staff can also own vehicles.
- **Passport:** owner entries and completion-generated entries, provider attribution, private evidence downloads, source labels, revision snapshots with actor/reason/date, evidence review and invalidation of that review label after corrections.
- **Sharing:** selected entries only, random tokens stored as hashes, 1–30 day expiry, revocation and request throttling. No owner contacts, identifiers or attachments in a shared page. Attachment access is independently authorized; a share token never authorizes evidence downloads.
- **Maintenance:** explicit thresholds or calculated intervals from a real selected service date/odometer; in-app queued reminders, preferences, stale mileage messages and transaction-protected deduplication. No telemetry, diagnosis, predictive claims, recalls or invented model schedules.
- **Reviews/complaints:** one four-metric review per owned completed transaction, counts and averages, provider replies, private evidence-backed complaints, responses, moderator decisions, appeals and audit history. Public summaries require a deliberate moderator action on a resolved complaint. No automatic allegation publication or paid rating/verification changes.
- **Content/community:** draft/published articles, sources, authors and review dates, allowlisted YouTube/Vimeo video links, admin-managed WhatsApp invitation links with deliberate external navigation. Article text is escaped, not executed as HTML; arbitrary rich HTML and third-party embeds are intentionally not accepted.
- **Operations:** provider identity-review scope and date, membership and role administration, pilot requests/quotes/orders, compatibility records, private evidence review, complaint moderation, cost recording, defined pilot metrics and audit events.
- **Data handling:** account JSON export, private file storage, limits, scoped downloads, deletion-review requests that revoke share links, and a documented retention workflow. These controls are not a claim of legal compliance.

## Workflow permissions

Requests: `requested → quoted → accepted`. An owner can cancel a requested or quoted request. Quote acceptance creates a single-provider transaction.

Transactions:

```text
accepted → in_progress → awaiting_confirmation → completed
    │           │                 │
    └───────────┴─────────────────┴──→ disputed
    └→ cancelled                       │
                                      ├→ in_progress
                                      └→ cancelled
```

Approved organization staff start and finish work; only the owner confirms completion. Owners/provider staff can dispute active work. A moderator or administrator resolves fulfilment disputes. Accepted work may be cancelled by a participating owner or provider; later cancellation requires dispute review. Payment status remains independent, and cancellation never silently refunds a payment.

Global roles (`owner`, `moderator`, `admin`) govern platform authority. Approved organization memberships supply garage/supplier capabilities, so membership does not remove ownership features. A moderator cannot approve providers, edit global roles or enter financial adjustments. Server policies and explicit actor checks enforce these rules, independently of navigation visibility.

## Queue, scheduler and storage

```bash
php artisan queue:work --tries=5
php artisan schedule:work
```

Production cron (once per minute):

```cron
* * * * * cd /path/to/carverse && php artisan schedule:run >> /dev/null 2>&1
```

`carverse:remind` queues checks at 08:00 Africa/Nairobi daily. `carverse:reconcile-payments` retries eligible callback-backed sandbox payment reconciliations every five minutes. Manual development checks:

```bash
php artisan carverse:remind
php artisan queue:work --stop-when-empty
php artisan carverse:reconcile-payments
```

Reminders are delivered to the application's maintenance notification panel. Email/SMS reminder delivery is not configured. Repeated scheduler or worker executions do not create a second notification for the same reminder. Edit/create a new reminder for the next service cycle.

Evidence and photos use the **private** local disk (`storage/app/private`). File paths are hidden from serialized page props. Do not expose this directory or link it into `public/storage`. A public storage symlink is not required. Evidence is downloaded with authorization and attachment disposition; private vehicle photos require ownership, and approved provider/part photos have scoped public image routes. Preserve `APP_KEY` securely: encrypted chassis identifiers cannot be recovered without it. Timestamps use UTC; presentation dates use Africa/Nairobi. Prices are stored as integer cents and formatted as KES.

## Payments

`PAYMENT_DRIVER=demo` is the default. It creates a payment attempt, calls the same reconciliation action, and marks the transaction paid with an explicit `DEMO-…` reference. No external request, real charge, escrow, split settlement or payout occurs.

To test the **sandbox-only Daraja adapter**, obtain sandbox credentials from the official portal, verify its current request/response contract, and set:

```dotenv
PAYMENT_DRIVER=daraja
MPESA_CONSUMER_KEY=
MPESA_CONSUMER_SECRET=
MPESA_SHORTCODE=
MPESA_PASSKEY=
MPESA_CALLBACK_TOKEN=
APP_URL=https://your-reachable-sandbox-host.example
```

Generate a long random callback token locally, keep it in the environment, and restart workers after configuration changes. The adapter intentionally fixes its host to `https://sandbox.safaricom.co.ke`; live payments are not enabled. Use sandbox test phone numbers and credentials only.

- OAuth and STK requests are server-side. Browser responses never mark an order paid.
- Initiation stores a separate attempt and provider checkout/merchant references. Pending initiation is serialized with a cache lock; another pending attempt blocks a new prompt.
- Callback token, known attempt/reference and exact expected KES amount are checked; a queued job also queries Daraja and verifies the checkout and merchant references and successful provider result.
- Reconciliation locks the attempt, validates amount/currency/order total, and is idempotent. Late failure cannot downgrade a paid attempt; a later verified success can resolve a failed attempt.
- Callback amount mismatch stays unpaid. Missing callbacks or missing amount metadata stay pending for investigation; a status query by itself cannot independently establish the amount. Callback-backed pending attempts are periodically retried. Duplicate callbacks may enqueue checks but cannot create duplicate paid transitions.
- No full payloads, access tokens, PINs or consumer secrets are written by application payment code. Redact callback URL tokens from reverse-proxy/access logs; use TLS and keep development logs private.

**External blocker:** no Daraja credentials or reachable HTTPS callback host were supplied. HTTP-faked contract tests pass, but no real sandbox prompt, callback or settlement has been tested. Do not treat the adapter as production-approved. Provider SDK/contract changes, onboarding, test cases, verification mechanisms and settlement responsibilities must be reviewed with Safaricom before any real use.

Refunds and provider settlement are manual operations outside this application. It does not hold escrow, issue automatic refunds, split a payment among providers, or pay providers automatically. Cancelling paid work leaves its payment status intact and requires an independently documented refund process. Unknown outcomes after a network timeout require provider-side investigation before retrying.

## Metrics

Admin metrics cover the records currently stored, not fabricated market activity:

| Metric | Definition |
| --- | --- |
| Requests | Count of submitted requests, including subsequently cancelled requests |
| Quotes accepted | Transactions created from accepted quotes, including later cancellations |
| Completed transactions | Owner-confirmed completed transactions |
| Mean fulfilment hours | Mean elapsed time from acceptance to completion for completed transactions |
| Fitment-related returns | Transactions explicitly flagged by an administrator |
| Repeat customers | Owners with at least two completed transactions |
| Commission revenue | Recorded commission on completed transactions, not gross transaction value |
| Variable costs | Recorded variable costs on completed transactions |
| Contribution | Commission less variable costs; unavailable if any completed transaction has missing costs |

Financial inputs are manual accounting records; they do not imply that commission was automatically collected. They are administrative, not moderator controls.

## Deletion and retention

1. Owner requests a review in Preferences and confirms their password. Sharing links are revoked immediately and an audit event is saved.
2. Administrator checks unfinished transactions, complaints, appeals, provider memberships and evidence. Record the grounds and duration for any retention in the deletion-review screen.
3. Export data on request. Revoke sessions, disable credentials, redact profile/contact data and vehicle identifiers where retention is unnecessary, and remove private files after their retention period. Record each action and retained identifiers in the review note. This is an operator procedure, not an automatic purge job.
4. Preserve the minimum transaction/correction/complaint audit records needed for an unresolved matter. Close the request only after documenting follow-up. Choose actual retention periods with appropriate operational/legal guidance; none are invented here.

The starter's immediate account deletion is available only for an empty account without retained vehicles, memberships or actor audit events. Populated accounts use the documented review flow, preventing foreign-key failures or silent loss of transaction evidence.

## Validation

```bash
php artisan test
npm run types
npm run lint
npm run build
./vendor/bin/pint --test
composer validate --strict --no-check-publish
```

Tests cover authentication and SMS phone verification, request creation, imported chassis identifiers, ownership and organization boundaries, quote snapshots and uniqueness, transitions, file privacy and upload limits, share expiry/revocation/redaction, fitment evidence and invalidation, record corrections/review invalidation, review eligibility, complaint publication, role/financial boundaries, reminders and deduplication, deletion review, and payment reference/amount/currency mismatches, duplicate/failed/out-of-order callbacks with fake provider responses.

The implementation report in `docs/IMPLEMENTATION.md` records actual checks and remaining environment limitations. Browser visual inspection was unavailable. Run the application at mobile and desktop widths before launch.

## Deployment checklist

- [ ] Provision PostgreSQL and PHP `pdo_pgsql`; run migrations and tests against PostgreSQL, including concurrent quote-acceptance/payment cases.
- [ ] Use the committed lockfiles (`composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`).
- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, secure session cookies, trusted proxy configuration, and private secrets. Never seed demo accounts in production.
- [ ] Create the first administrator through a controlled operator procedure; do not add public role-selection registration fields.
- [ ] Serve only `public/`; prevent access to `.env`, private storage, backup files, logs and VCS metadata. Redact tokenized callback paths from access logs.
- [ ] Configure real mail delivery and verify registration/reset mail. Start supervised queue workers and the scheduler; monitor failed jobs and queue lag.
- [ ] Back up PostgreSQL, private evidence and the encryption key; test restoration. Set filesystem upload quotas and malware screening appropriate to the pilot environment.
- [ ] Review seeded/provider claims, evidence scope, complaint redaction and retention procedures with actual pilot operators.
- [ ] Complete keyboard, screen-reader, mobile (390 px) and desktop (1440 px) visual QA in a browser. No browser-based visual QA was possible here.
- [ ] Keep demo payments explicit, or complete credentialed Daraja **sandbox** verification. Real payment collection, refunds and settlement require a separate launch review.
- [ ] Configure transaction/callback monitoring without logging secrets. Check throttling behind the real proxy and rehearse reconciliation for missing callbacks.
- [ ] Run configuration/route/view caching, restart workers after deployment, and verify `/up` and authenticated workflows.

## Later phases (not built and not advertised in navigation)

QR event ticketing; real-time SOS or towing dispatch; manufacturer-backed part authenticity; automatic official recalls; consumer subscriptions; native mobile applications. Payment escrow, automatic payouts and split settlement are not claimed features.


### Blog and event approvals

The super-user workspace now includes **Blog** (`/app/admin?tab=blog`) and **Event approvals** (`/app/admin?tab=events`). Use the existing local demo super-user credentials documented above.

Blog posts support title, author, category, introduction, plain-text body, references, optional YouTube/Vimeo link, review date and draft/published status. Drafts are private. Public `/blog` provides search and pagination; individual posts have stable URLs. The landing page displays the three latest published articles from the database. Existing guides are preserved, and the seeded fictional guide stays labeled as demo content. Raw HTML is rendered as text rather than executed. No external publishing integration is required.

Verified signed-in users can submit events at `/events` or `/app/events`, with optional poster and required private organizer evidence. A super user reviews the evidence, records a note and approval scope, and approves or rejects. Only approved current versions of upcoming events appear publicly. Editing an approved event returns it to pending; withdrawal removes it from public listings. Organizer contacts and evidence remain private to the submitter and administrators. Dates entered in Nairobi time are persisted in UTC. Approval describes the recorded checks, not an event safety guarantee. Tickets and payments are not implemented.

Run `php artisan migrate` when updating an existing installation. These features use the configured database and private local filesystem. No event or blog data is automatically published from external sources.

Validation for this update: 98 backend tests / 917 assertions, TypeScript checking, ESLint, Pint and production asset build passed. Browser inspection was unavailable in this environment.

### Featured images and additional editorial posts

In **Super user → Blog**, upload an optional JPG, PNG or WebP featured image (maximum 5 MB) and supply its description for accessibility. Existing images can be replaced or removed. Images appear on landing-page cards, the blog listing and article pages. Draft images are accessible only to administrators; storage paths are never exposed in page data. Run `php artisan migrate` to add the image fields.

Three original product guides are available through `php artisan db:seed --class=BlogArticlesSeeder`: understanding fitment labels, interpreting provider approval, and discovering car communities. This explicitly invoked seeder publishes these articles, preserves existing administrator edits and does not generate duplicate posts on rerun. It has been run in the local development database. These articles do not have uploaded photographs; the existing category artwork appears until an administrator adds an image.

### Landing page events and racing

The landing page now places an event slideshow in the main banner, a verified racing leaderboard on the left and an upcoming-events list on the right. Only approved current event versions whose end time has not passed appear. With no events, the original introductory banner and an explicit empty event list are shown. Event posters are optional. Slideshow controls include previous, next and pause; reduced-motion preferences disable autoplay initially. Sidebars stack below the banner on mobile.

Leaderboard buttons select ¼ mile, ⅛ mile or ½ mile (stored distance categories 402, 201 and 805 metres respectively). An event selector keeps results scoped to the same event and distance; only manually verified runs are public, ordered by elapsed milliseconds with shared ranks for ties. The super-user racing form supports all three distances. Private evidence and review notes are excluded from landing-page data. Event submissions and racing event labels remain independently managed.

`EventSeeder` adds three upcoming, approved fictional events for local development: a Nairobi night drive, an Eastlands car show and a track-day preparation workshop. Their evidence file, organizer, venue and schedule are labeled as demo fixtures; no real booking or endorsement is represented. The seeder is idempotent, refuses production, and is called by the normal `php artisan db:seed` flow. Run `php artisan db:seed --class=EventSeeder` to seed only the events.

### Color themes

The public header, authentication header and workspaces include a **Color theme** selector with Light, Dark and System options. The choice is stored in this browser, shared between its tabs, and applied before the initial page render. System mode follows operating-system theme changes. Preferences are not tied to an account; if browser storage is blocked, switching still works for the current session. Dark styling covers the glass landing page, public articles/events, auth forms, and owner/super-user workspaces. Theme behavior checks: `node --test tests/frontend/appearance.test.mjs`.

### Global public header and demo leaderboard

All front-facing pages, including the specialist directory and authentication screens, share the same logo, Specialists/Blog/Events links, theme selector and session-aware account actions. The signed-in workspaces retain their own navigation.

`php artisan db:seed --class=LeaderboardSeeder` adds 15 explicitly fictional results (five per distance) without duplicating existing runs or replacing edits. It requires an existing administrator, stores clearly fictional evidence placeholders, and refuses production environments. The seeder has been run locally. Demo runs carry `is_demo=true` and are labeled as illustrative, never as actual reviewed race evidence. Real verified runs take priority per distance; demo and real results are not mixed. The event selector appears only when there is more than one available event. Empty leaderboard headings and messages have been removed as requested.

### Car culture and car features

The public design now centers on car features, meets, racing and stories while retaining the requested left leaderboard and right event/blog lists. Open `/features` to browse and search published build stories. In **Super user → Car features**, manage features through the existing blog publishing and private-image workflow, with optional vehicle/year/engine/transmission/region/credit/modification fields. Unpublished features remain private. Build specifications are editorial information rather than certified performance claims.

Apply migrations, then optionally run `php artisan db:seed --class=CarFeaturesSeeder` in development for one explicitly fictional concept feature. It has been seeded locally and refuses production environments. The generated cover asset, exact generation prompt, and design notes are documented in [docs/design/car-culture.md](docs/design/car-culture.md).

Single article pages use a two-column reading layout when space allows. Car features show the build information in a sticky sidebar, followed by related articles and popular reads. Popular ordering uses the article view counter; no popularity numbers are displayed. The sidebar collapses below the article on smaller screens.
