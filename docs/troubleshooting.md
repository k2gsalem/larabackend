# Troubleshooting Guide

This guide outlines common problems you may encounter while working with the Laravel backend and how to fix them quickly.

## Composer install fails
- **Symptom:** `composer install` exits with memory or dependency resolution errors.
- **Fix:**
  - Ensure you are running PHP 8.2 or later: `php -v`.
  - Clear Composer caches: `composer clear-cache`.
  - Try installing with additional verbosity for more clues: `composer install -vvv`.

## NPM install or build errors
- **Symptom:** `npm install` or `npm run build` fails due to missing modules or incompatible Node versions.
- **Fix:**
  - Verify that you are using Node 18 LTS or newer: `node -v`.
  - Remove existing dependencies and reinstall: `rm -rf node_modules package-lock.json && npm install`.
  - If Vite fails to compile, run `npm run build -- --clearScreen false` to see the full stack trace.

## Environment configuration issues
- **Symptom:** Application throws configuration errors or cannot find services.
- **Fix:**
  - Copy the example environment file: `cp .env.example .env`.
  - Generate a fresh application key: `php artisan key:generate`.
  - Double-check database credentials in `.env` and confirm that the database server is reachable.

## Database migration failures
- **Symptom:** `php artisan migrate` fails or rolls back unexpectedly.
- **Fix:**
  - Confirm that the database connection information in `.env` matches your local environment.
  - Make sure the database exists and your user has permission to create tables.
  - Run migrations with verbose output for more details: `php artisan migrate -vvv`.

## Permission problems with storage or cache
- **Symptom:** The app cannot write to `storage/` or `bootstrap/cache` directories.
- **Fix:**
  - Set correct ownership (adjust the user to match your environment): `sudo chown -R $USER:$USER storage bootstrap/cache`.
  - Update directory permissions: `chmod -R ug+rw storage bootstrap/cache`.

## Queue or scheduler not processing jobs
- **Symptom:** Jobs remain in the queue or scheduled tasks do not run.
- **Fix:**
  - Confirm that a queue worker is running: `php artisan queue:work` or use a supervisor service.
  - For scheduled tasks, make sure `php artisan schedule:run` is executed every minute via cron.

## Docker containers fail to start
- **Symptom:** `docker-compose up` stops with errors or services exit immediately.
- **Fix:**
  - Ensure Docker Desktop is running and that you have enough memory/CPU allocated.
  - Remove stale containers and volumes: `docker-compose down -v`.
  - Rebuild images to capture recent changes: `docker-compose build --no-cache`.

If these steps do not resolve your issue, open a ticket with detailed logs or stack traces so maintainers can assist.
