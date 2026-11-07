# LJS Realty and Brokerage

Laravel 12 application for property listings, professional profiles, appointments, messaging, referrals, and staff administration.

## Requirements

- PHP 8.2.35+, 8.3.36+, 8.4.27+, or 8.5.12+ within the corresponding release branch, with the extensions required by Laravel and SQLite/MySQL
- Composer 2
- Node.js 24.22.1 LTS or newer within the 24.x branch and npm (also supports patched 22.x from 22.23.4 and 26.x from 26.12.1)
- SQLite for a local setup, or MySQL for a hosted installation

## Security updates before deployment

The PHP minimum versions above include the October 21, 2026 fixes for CVE-2026-109618 and CVE-2026-109619. Update the PHP runtime serving web requests as well as the CLI and queue workers; Composer updates alone do not patch PHP. Restart the web server/PHP workers and queue workers after updating, and verify the serving PHP version in the hosting control panel. The official advisories are available at [PHP security announcements](https://www.php.net/) and [Node.js October 2026 security releases](https://nodejs.org/en/blog/vulnerability/october-2026-security-releases).

Set `session.upload_progress.enabled=0` in the serving PHP configuration and restart PHP workers, following PHP's October 23 session upload progress advisory. This is an additional precaution, not a replacement for the runtime update. Plan to move off PHP 8.2 before its security support ends on December 31, 2026.

Install the committed dependency versions with `composer install --no-dev --optimize-autoloader` and `npm ci`, then run `npm run build` in the build environment. Run `composer audit --locked` and `npm audit` before release. The production web root must be `public/`, with `APP_ENV=production` and `APP_DEBUG=false`. Keep the Vite development server private; serve the compiled assets in production.

## Local setup

1. Install PHP and JavaScript dependencies:

   ```sh
   composer install
   npm install
   ```

2. Create the environment file and application key:

   ```sh
   cp .env.example .env
   php artisan key:generate
   ```

   On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

3. Configure `.env`. For SQLite, set `DB_CONNECTION=sqlite` and create `database/database.sqlite`. Set `ADMIN_NAME`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD` before running the seeder.

4. Run migrations and seed the initial Admin account:

   ```sh
   php artisan migrate --seed
   ```

5. Link public uploads and build the frontend:

   ```sh
   php artisan storage:link
   npm run build
   ```

6. Start the application and queue worker in separate terminals:

   ```sh
   php artisan serve
   php artisan queue:work --tries=3
   ```

   Open `http://127.0.0.1:8000`.

## Tests and checks

Run the automated suite with:

```sh
php artisan test
```

Build production assets with `npm run build`. Check dependencies with `composer audit` and `npm audit --audit-level=high`. The test suite uses an in-memory SQLite database and runs the migration chain from scratch.

## Main workflows

- Clients browse approved listings, search professionals, request appointments, and message professionals.
- Agents and Brokers submit properties for Admin review. Admin-mode Lead Broker actions follow the Admin workflow; Broker-mode actions follow the professional workflow.
- Admins review listings, record rejection reasons, and update listing status. Admin-listed properties route client messages and appointments to the Admin who created the listing.
- Admin listings are approved immediately and receive a referral link owned by the listing Admin; it can be shared with the externally assigned seller/endorser, while client messages and appointments remain directed to the Admin.
- Agent/Broker listings stay in the Admin review queue. Approval makes the property public and creates a referral link tied to the professional who listed it.
- Reports support date ranges, visible listing-owner filters, inquiry/appointment/completion/sale counts, activity trends, and CSV export. Sold and completed event dates are tracked from this release forward.
- Message, appointment, contact-form email and property geocoding work are queued in the database. Login and registration OTP email remains synchronous so delivery errors can be reported immediately.
- Staff pages are role-gated through `config/roles.php` and the `/app/{page}` route.

## Production checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, a strong `APP_KEY`, the public `APP_URL`, and production database credentials.
- Configure a real SMTP transport and `CONTACT_EMAIL` before launch. Login and client registration use email OTP verification; message, appointment, and contact-form emails are processed by the queue worker.
- Run a supervised worker with `php artisan queue:work --tries=3`; monitor failed jobs with `php artisan queue:failed` and retry them after resolving the cause.
- Use HTTPS, configure the web server to serve `public/`, and run `php artisan storage:link`.
- After deploying this attachment change, run `php artisan messages:secure-attachments` once to move existing message files from public to private storage.
- Back up the database and uploaded files before migrations; run `php artisan migrate --force` during deployment.
- Build assets with `npm ci && npm run build`, then run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.
- Verify account roles, Admin review permissions, uploads, mail delivery, and client-to-professional contact flows in the deployment environment.

## Configuration notes

Profile photos and property images use Laravel's public disk. Message attachments use private local storage and can be downloaded only by conversation participants. Professional credential documents are stored on the private local disk and can be downloaded only by an Admin. New staff accounts receive an initial password from the Admin and can change it from Settings. For production email delivery, set `MAIL_MAILER=smtp` and provide `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME`; set `CONTACT_EMAIL` to the address that should receive website inquiries.
