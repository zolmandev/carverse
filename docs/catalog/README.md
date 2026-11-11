# Car catalog snapshot and management

Cars management is available to the super user at `/app/admin?tab=cars`.

The database stores brands, models and trims/variants separately, with parent foreign keys. WhatsApp groups store the selected brand/model/optional trim IDs; the legacy text label remains for older records and exports. Dependent dropdowns load model and trim choices on demand. Backend validation enforces matching parents and active choices.

Administrators can create and edit each level, archive or reactivate entries, and upload a PNG/JPEG/WebP brand logo (2 MB maximum). Archiving does not delete linked groups. Catalog edits and archives produce audit events. Model and trim parent changes are intentionally rejected; create a new record under the correct parent instead.

## Included data

The committed `database/data/car-catalog.json.gz` contains 422 brands, 11,054 model records, and 52,124 year-specific trim/configuration records. Seeding also preserves model names already used by local vehicles or community groups; this workspace consequently has 11,055 models before any subsequent administrator additions.

Sources:

- **VehiclesDB 2026.11.6**, https://github.com/vehiclesdb/vehiclesdb/tree/v2026.11.6 — 321 car makes and 6,072 car nameplates, augmented with additional model names from the other sources. CC BY 4.0. Visible attribution is included in product footers. The upstream attribution notices and release manifest are retained in this directory.
- **Trimslate**, https://trimslate.com/data — CSV retrieved November 11, 2026. Attribution and link are displayed in product footers. The export contains equipment/powertrain labels in its `trim` field. These are imported as **variant/configuration** records, not automatically asserted to be equipment grades. Original model year and United States market context are retained. Administrators can add equipment grades and other markets as separate records with sources.
- **VehicleSpecs brand logos**, https://github.com/vehiclespecs/brand-logos — 184 identification assets stored locally in `public/car-logos`. Repository metadata is MIT licensed; logo marks remain the property of their respective owners. See `LOGO-LICENSE.txt`. Logo inclusion is not an affiliation or endorsement. Missing logos show a clearly ordinary initials tile, not an invented manufacturer mark.
- The earlier Carverse reference choices and names already recorded in local vehicles supplement the catalog. Their source labels distinguish them from imported datasets.

**Coverage is not universal.** This is a broad snapshot of the available source records, not a guarantee of every historical brand, model or trim worldwide. Market-specific grades, particularly imported Japanese vehicles, may be missing. A model appearing in the catalog does not establish part compatibility, exact equipment, authenticity or availability in Kenya. Absent trims are left absent rather than fabricated.

`SNAPSHOT.json` records retrieval date, bundle counts and source-download SHA-256 hashes. The actual normalized seed bundle is committed so installation does not require network access. Brand and model names are matched conservatively; unrelated model names are not fuzzily merged. Some sources distinguish names differently, so administrators should review any desired merges rather than assuming all cross-source names are equivalent.

## Seeding

```bash
php artisan migrate
php -d memory_limit=512M artisan db:seed --class=CarCatalogSeeder --force
```

This reference-data seeder may run without demo accounts, including during deployment. The general `DatabaseSeeder` still refuses production demo seeding. Rerunning `CarCatalogSeeder` inserts missing source keys and preserves administrator names, active/archive settings, uploaded logos and existing group links. It does not overwrite local corrections or delete retired source rows.

The seed bundle is a frozen snapshot. Updating upstream sources requires preparing a new normalized bundle, reviewing counts/attribution, retaining source provenance, and rerunning the idempotent seeder. There is no automatic live synchronization or manufacturer-grade verification.
