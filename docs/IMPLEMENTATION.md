# Implementation report

> Current scope: Requests & bookings, Vehicle passport, and Maintenance have been removed from the product. Their navigation, dashboard sections, page/submission routes, public history links, and scheduled reminder delivery are disabled. Stored historical data and domain regression tests are retained. The original workflow descriptions below document that archived implementation, not currently available features.


## Delivered

Laravel 13.42.0 application in `carverse/`, based on the official React starter kit, with React 19, TypeScript, Inertia 3, Tailwind 4 and retained shadcn starter components. No separate API backend. PostgreSQL is configured in `.env.example`; local preview uses an ignored SQLite database because PostgreSQL tooling is not installed in the environment.

Database migrations, user/vehicle/organization factories, fictional non-production seed data (five garages, three suppliers, two owners), private evidence storage, role/membership checks, workflow actions, audit history, queue jobs, notifications and scheduled commands are included.

The primary owner and provider flows are implemented through real Laravel routes and database writes. Form errors, disabled submit states, empty states, success messages, responsive navigation, tables/cards and active vehicle context are provided. All data-dependent screens read database records; the dashboard does not present invented usage statistics.

## Architecture

- `app/Queries/WorkspaceQuery.php`: scoped, paginated Inertia read model, provider aggregates and defined pilot metrics.
- `app/Actions/Workflow.php`: transaction-safe quote acceptance, accepted terms, actor/status rules, completion record creation, corrections and payment reconciliation.
- `app/Policies/WorkspacePolicy.php`: ownership, approved organization, moderation and private attachment decisions.
- `app/Http/Requests/VehicleRequest.php`: vehicle onboarding/edit rules, including imported identifiers and photos; other mutation endpoints use explicit server validation.
- `app/Services/Payments`: payment interface and separate demo/sandbox drivers.
- `app/Jobs`: queued reminder delivery and server-side Daraja reconciliation.
- `resources/js/pages/carverse`: typed product pages and reusable form/card primitives.

## Actual verification

- 56 automated PHPUnit tests passed (215 assertions), including the final full-suite run.
- TypeScript checking, ESLint, and Vite production build passed during development.
- HTTP GET of the running local home page returned 200.
- Migration execution and fictional seeding completed on SQLite; seeding was rerun without deleting existing fixtures.
- HTTP fakes exercise sandbox initiation and callback/status-query reconciliation. No external payment was made.
- The browser tool reported that no browser was available. Mobile/desktop rendering, accessibility-tree inspection and screenshot checks were not performed.

## External dependencies and operational limits

PostgreSQL runtime/migration/concurrency checks remain unverified in this environment: neither PostgreSQL tooling nor `pdo_pgsql` is installed. Docker Compose and setup instructions are provided. SQLite passing tests do not establish PostgreSQL concurrency correctness; acceptance relies on row locks plus unique constraints, which still need deployment-database testing.

Daraja OAuth/STK/query code is sandbox-only. The official portal was reachable, but its interactive endpoint details were not accessible to the available reader. Current contract review, sandbox credentials, a reachable HTTPS callback and credentialed end-to-end testing remain required. Fake tests do not establish live provider compatibility. Missing callbacks/amount evidence remain unpaid for operator investigation. No escrow, automated refunds, settlement, split payments or payouts are implemented.

Reminder delivery is in-app; optional email/SMS channels need a separate configuration/implementation. Authentication mail uses the log mailer until a real mail service is configured. Retention/deletion review is persisted and audited; actual redaction/file removal is an operator procedure, not an automated purge feature.

Article content is plain text with optional allowlisted external video links. Arbitrary HTML, arbitrary embeds and a rich-text editor are not enabled. Each vehicle, provider and part supports one optional photo. The pilot does not implement bulk catalog import, a multi-vendor cart or automated authenticity/recall integrations.

All seed assessments and evidence are fictional training fixtures. No claim of real provider verification, complete vehicle history, predictive diagnosis or legal compliance is made.

## Final check results

- `php artisan test`: 56 passed, 215 assertions.
- `npm run types`: passed.
- `npm run lint`: passed.
- `npm run build`: passed.
- `vendor/bin/pint --test`: passed after formatting corrections.
- `composer validate --strict --no-check-publish`: passed.
- PostgreSQL GitHub Actions workflow supplied; not executed in this local session.

## Feature removal verification

Requests & bookings, Vehicle passport and Maintenance were removed from product screens and normal runtime routes. Reminder scheduling and already queued reminder delivery are disabled. Historical data remains intact. Archived workflow tests explicitly opt into the retained domain code; normal-runtime tests verify 404 responses and absence of retired page props. Final checks: 59 tests passed (236 assertions), TypeScript, ESLint and production build passed.

## Focused super-user management

The administrator now opens a dedicated workspace with WhatsApp group link CRUD/search and drag racing run submission, private GPS/slip downloads, auditable manual evidence review, and event/distance-specific verified rankings. Normal owner pages are not included in that workspace or its page props. Current verification: 65 tests passed (311 assertions), TypeScript, lint and production build passed. GPS/slip verification is manual; there is no automated GPS integration or timing certification.

## Cars management and normalized group selection

Added database-backed brand → model → optional trim selection, parent validation, active/archive rules, searchable/paginated catalog tables, editing, archiving/reactivation and safe raster logo uploads. Imported an offline, attributed catalog snapshot and local logo assets. Current reference snapshot contains 422 brands, 11,054 models and 52,124 variants; preserved local records add one model in this workspace. There are 184 sourced logos, with initials fallback elsewhere. Imported configuration variants are not described as verified equipment grades or universal trim coverage.

Tests cover admin-only access, mismatched brand/model/trim rejection, normalized group storage, create/edit/archive, seed repeatability and preservation of administrator edits, and private uploaded-logo storage and SVG rejection. Source/coverage and installation details are in `docs/catalog/README.md`. Browser inventory again returned no available browser, so visual inspection was unavailable.

Final catalog verification: 72 tests passed (376 assertions), TypeScript, ESLint, production build and PHP formatting passed. Both catalog migrations ran successfully on the local SQLite database. Browser inventory was empty; PostgreSQL runtime remains the previously documented environment dependency.

## Garage/mechanic applications, approval and logos

Added a dedicated super-user Provider approvals panel with status tabs, search/type filters, private evidence review, public verification scope, feedback, reviewer/time and audit history. Mechanic applications now persist alongside garages and suppliers, with optional experience and private qualification claims. Approval requires available evidence and explicit review confirmation; only approved providers are public and acquire staff capabilities. Rejection/pending revokes those capabilities.

Garages can upload a separate PNG/JPEG/WebP logo during application or replace it through their organization workspace. Pending logos are restricted to members/admin; approved logos appear in the directory. Storage paths, qualifications and feedback are hidden publicly.

Verification: 80 tests passed (570 assertions), TypeScript, lint and production build passed. Provider-specific tests cover garage/mechanic applications, document and logo privacy, approval requirements, admin-only decisions, rejection/reopening, and authorized raster-logo updates/SVG rejection. Database migrations ran on the local SQLite development database. Browser inspection remains unavailable.

## Approved-provider reviews and cases

Approved-provider rows now link to provider-scoped Reviews and Cases management, with counts, pending badges, status filters, pagination and moderation history. Added review publication status, reason/reviewer/time, and provider-response publication status. Only approved reviews from completed transactions belonging to their reviewer contribute publicly; moderation cannot rewrite the rating fields.

Case intake approval, case resolution and public-summary approval are separate actions. Intake approval requires available private evidence. Public summaries require an accepted and resolved/dismissed case plus explicit redaction confirmation and a reason. Review rejection, resolution changes and appeals withdraw stale summaries while retaining audit history. Original private case records and evidence are never automatically published. All new moderation endpoints require admin authority, an approved provider and a matching provider/record relationship. Existing moderator case actions retain their earlier role scope but respect the new publication gate.

The migration preserves existing records and starts moderation as pending. Retired booking and customer-submission modules remain disabled. Verification: 87 tests passed (714 assertions), TypeScript, lint and production build passed. Tests include provider scoping and admin authority, publication eligibility, immutable rating inputs, provider reply moderation without rating suppression, private case approval, resolution/publication/redaction gates, unpublishing, and appeal-driven withdrawal.
