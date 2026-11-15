# Automated Carverse cPanel deployment

Carverse deploys pushes to `main` through GitHub Actions. GitHub builds the Vite assets, uploads the release over SSH/rsync, preserves the server-owned `.env`, SQLite database and `storage`, and runs the Laravel deployment script.

## Target

- SSH: `theajase@199.188.200.230:21098`
- Application path: `/home/theajase/carverse`
- Required document root: `/home/theajase/carverse/public`
- GitHub environment: `production`

## Server requirements

- Authorized SSH key and shell access
- PHP 8.3 or newer
- Composer 2
- `rsync`
- PHP extensions `pdo_sqlite` and `sqlite3`
- Domain or subdomain whose document root points to the application's `public` directory

## GitHub environment secrets

Create the `production` environment and add:

| Secret | Value |
| --- | --- |
| `CPANEL_HOST` | `199.188.200.230` |
| `CPANEL_PORT` | `21098` |
| `CPANEL_USER` | `theajase` |
| `CPANEL_APP_PATH` | `/home/theajase/carverse` |
| `CPANEL_SSH_KEY` | Complete private deployment key |
| `CPANEL_PHP_BIN` | Absolute PHP 8.3 CLI path, or `php` |
| `CPANEL_COMPOSER_BIN` | Absolute Composer path, or `composer` |

Never commit the private SSH key or production `.env`.

## One-time server setup

After authorizing the public deployment key in cPanel, connect over SSH and create the app directory:

```bash
ssh -p 21098 -i ~/.ssh/carverse-cpanel-deploy theajase@199.188.200.230
mkdir -p ~/carverse
cd ~/carverse
```

The initial `.env` must be created directly on the server before the first workflow deployment. Required production settings include:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-CARVERSE-DOMAIN

DB_CONNECTION=sqlite
DB_DATABASE=/home/theajase/carverse/database/database.sqlite
DB_FOREIGN_KEYS=true

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
SESSION_SECURE_COOKIE=true

HASHBACK_SMS_API_KEY=YOUR_HASHBACK_KEY
HASHBACK_SMS_BASE_URL=https://www.hashback.co.ke
```

You may leave `APP_KEY=` empty for the first deployment. The deployment generates it once when empty and preserves it on every later deployment because `.env` is never uploaded or deleted.

Configure mail credentials if password-reset email delivery is required. The default log mailer does not send email.

## cPanel cron jobs

Confirm the PHP 8.3 binary with `which php`, then add:

```cron
* * * * * cd /home/theajase/carverse && /absolute/path/to/php artisan schedule:run >/dev/null 2>&1
* * * * * cd /home/theajase/carverse && /absolute/path/to/php artisan queue:work --stop-when-empty --tries=3 >/dev/null 2>&1
```

Do not run `DatabaseSeeder` in production; it deliberately refuses production demo seeding. The deployment runs only the idempotent `CarCatalogSeeder`.

## First deployment

Once the cPanel key, domain document root, `.env`, and GitHub secrets are ready, run **Actions → Deploy to cPanel → Run workflow**. The deployment creates `database/database.sqlite` when absent and preserves it on later uploads. Later pushes to `main` deploy automatically.

Verify:

- `https://YOUR-CARVERSE-DOMAIN/up`
- landing page assets load over HTTPS
- login and SMS verification
- `/api/v1/events`
- profile image upload and authenticated mobile API login

Back up `/home/theajase/carverse/database/database.sqlite` before deploying migrations that remove or transform production data.
