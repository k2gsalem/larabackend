# Setup, Build, and Deployment Guide

This guide explains how to install project dependencies, configure the
application for local development, build the frontend assets, and deploy the
Laravel application into a production environment.

## Prerequisites

The project relies on the following tools:

- PHP 8.2+
- [Composer](https://getcomposer.org/download/)
- Node.js 18+ and npm
- A database supported by Laravel (MySQL, PostgreSQL, SQLite, etc.)
- Optional: Docker and Docker Compose for containerized development

> **Tip:** The repository includes a `docker` directory and
> `docker-compose.yml` for running the application in containers. You can use
> Docker instead of installing PHP and Node.js locally.

## Initial Setup

### 1. Clone the repository

```bash
git clone <repository-url>
cd larabackend
```

### 2. Copy the environment file

```bash
cp .env.example .env
```

Update the `.env` file with the correct database credentials and any other
project-specific settings.

> **SQLite quick start:** To run everything with SQLite locally, set `DB_DRIVER=sqlite` and `TENANT_DB_DRIVER=sqlite` in your `.env`, then create `database/database.sqlite` (central) and `database/tenant.sqlite` (template) files before running migrations.

### 3. Install PHP dependencies

```bash
composer install
```

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Install frontend dependencies

```bash
npm install
```

### 6. Run database migrations (and seeders if required)

```bash
php artisan migrate
php artisan db:seed   # optional
```

If you are using Docker, replace the above commands with the equivalent
`docker compose exec` invocation, for example:

```bash
docker compose exec app php artisan migrate
```

## Local Development Workflow

1. Start the PHP development server:

   ```bash
   php artisan serve
   ```

   Alternatively, run the containerized stack:

   ```bash
   docker compose up -d
   ```

2. Start the Vite dev server for asset compilation and hot reloading:

   ```bash
   npm run dev
   ```

3. Run the automated test suite as needed:

   ```bash
   php artisan test
   ```

## Building Frontend Assets

To produce optimized frontend assets for production, run:

```bash
npm run build
```

This command compiles and minifies assets into the `public/build` directory in
accordance with the project's `vite.config.js` settings.

## Production Deployment

### Prepare the build

1. Ensure dependencies are installed in production:

   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```

2. Cache configuration and routes for improved performance:

   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. Run database migrations with the `--force` flag to avoid confirmation
   prompts:

   ```bash
   php artisan migrate --force
   ```

### Deploying with Docker

1. Build and start the containers:

   ```bash
   docker compose up -d --build
   ```

2. Run the database migrations inside the application container:

   ```bash
   docker compose exec app php artisan migrate --force
   ```

3. (Optional) Seed data or run queue workers using additional `docker compose
   exec` commands.

### Deploying without Docker

1. Upload the project files (or build artifacts) to your server.
2. Configure a web server such as Nginx or Apache to serve the `public`
   directory.
3. Ensure the storage and bootstrap cache directories are writable by the web
   server user.
4. Run the production preparation commands listed above directly on the server.
5. Configure a queue worker (e.g., using Supervisor) if your application uses
   queues or scheduled jobs.

## Maintenance

- Run `php artisan schedule:run` via cron every minute if scheduled tasks are
  used.
- Monitor logs in `storage/logs/` for errors.
- Keep dependencies current by running `composer update` and `npm update`
  periodically, reviewing release notes before applying major upgrades.

## Troubleshooting

- If `php artisan key:generate` fails, verify the `.env` file exists and is
  writable.
- When migrations fail, check your database credentials and ensure the database
  server is running.
- For asset build issues, clear the Vite cache by deleting `node_modules/.vite`
  and re-running `npm install`.

