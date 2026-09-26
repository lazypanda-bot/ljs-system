# LJS Realty and Brokerage

Laravel 12 application for property listings, professional profiles, appointments, messaging, referrals, and staff administration.

## Requirements

- PHP 8.2 or newer with the extensions required by Laravel and SQLite/MySQL
- Composer 2
- Node.js 20 or newer and npm
- SQLite for a local setup, or MySQL for a hosted installation

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

6. Start the application:

   ```sh
   php artisan serve
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
- Admins review listings, record rejection reasons, assign professionals, and update listing status.
- Referral links route to an approved property and direct enquiries to the code owner. Admin-mode listings allow selection from their approved assigned professionals.
- Staff pages are role-gated through `config/roles.php` and the `/app/{page}` route.

## Production checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, a strong `APP_KEY`, the public `APP_URL`, and production database credentials.
- Configure a real mail transport; login and client registration use email OTP verification, and Admin-created staff accounts receive one-time password setup links.
- Use HTTPS, configure the web server to serve `public/`, and run `php artisan storage:link`.
- Back up the database and uploaded files before migrations; run `php artisan migrate --force` during deployment.
- Build assets with `npm ci && npm run build`, then run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.
- Verify account roles, Admin review permissions, uploads, mail delivery, and client-to-professional contact flows in the deployment environment.

## Configuration notes

Profile photos and property images use Laravel's public disk. Professional credential documents are stored on the private local disk and can be downloaded only by an Admin. New staff accounts receive a signed, one-time password setup link that expires after 24 hours; configure a real mail transport before creating production accounts.