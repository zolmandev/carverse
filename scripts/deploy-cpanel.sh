#!/usr/bin/env bash

# Runs on cPanel after GitHub Actions uploads a built release.
# The upload excludes the server-owned .env, SQLite database, and storage.
set -euo pipefail

: "${PHP_BIN:=php}"
: "${COMPOSER_BIN:=composer}"

if [[ ! -f artisan || ! -f .env || ! -f public/build/manifest.json ]]; then
    echo 'Deployment stopped: artisan, .env, or the Vite production manifest is missing.' >&2
    exit 1
fi

"$PHP_BIN" -r 'if (version_compare(PHP_VERSION, "8.3.0", "<")) { fwrite(STDERR, "PHP 8.3 or newer is required. Found: ".PHP_VERSION.PHP_EOL); exit(1); }'

"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress

mkdir -p database storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

DB_CONNECTION="$($PHP_BIN -r '$e=parse_ini_file(".env", false, INI_SCANNER_RAW); echo $e["DB_CONNECTION"] ?? "sqlite";')"
if [[ "$DB_CONNECTION" == "sqlite" ]]; then
    touch database/database.sqlite
    chmod 660 database/database.sqlite
fi

chmod -R ug+rwX database storage bootstrap/cache

if ! grep -Eq '^APP_KEY=.+$' .env; then
    "$PHP_BIN" artisan key:generate --force --no-interaction
fi

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" -d memory_limit=512M artisan db:seed --class=CarCatalogSeeder --force
"$PHP_BIN" artisan storage:link --force
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan queue:restart || true

echo 'Carverse cPanel deployment complete.'
