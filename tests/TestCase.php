<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Stancl\Tenancy\Facades\Tenancy;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function tenantServerVariables(string $domain): array
    {
        return [
            'HTTP_HOST' => $domain,
            'SERVER_NAME' => $domain,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        ini_set('memory_limit', '512M');

        $this->configureSqliteConnections();

        $connection = $this->centralConnection();

        $this->prepareSqliteDatabase($connection);
        DB::purge($connection);

        if ($connection !== 'tenant') {
            $this->prepareSqliteDatabase('tenant');
            DB::purge('tenant');
        }

        $exitCode = Artisan::call('migrate:fresh', [
            '--force' => true,
            '--database' => $connection,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Failed to migrate testing database: ' . Artisan::output());
        }
    }

    protected function tearDown(): void
    {
        if (($tenancy = Tenancy::getFacadeRoot()) && $tenancy->initialized) {
            Tenancy::end();
        }

        DB::disconnect('tenant');
        DB::purge('tenant');

        Mockery::close();

        parent::tearDown();
    }

    protected function centralConnection(): string
    {
        return config('tenancy.database.central_connection', config('database.default'));
    }

    protected function configureSqliteConnections(): void
    {
        $centralPath = database_path('testing.sqlite');
        $tenantPath = database_path('tenant-testing.sqlite');

        $this->ensureSqliteFile($centralPath);
        $this->ensureSqliteFile($tenantPath);

        config()->set('database.connections.central', [
            'driver' => 'sqlite',
            'database' => $centralPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        config()->set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => $tenantPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        config()->set('database.default', 'central');
        config()->set('tenancy.database.central_connection', 'central');
        config()->set('cache.default', 'array');
        config()->set('permission.cache.store', 'array');
        config()->set('queue.default', 'sync');
    }

    protected function prepareSqliteDatabase(string $connection): void
    {
        if (! $this->connectionUsesSqlite($connection)) {
            return;
        }

        $database = config("database.connections.{$connection}.database");

        if (! $database || $database === ':memory:') {
            return;
        }

        if (! Str::startsWith($database, ['/'])) {
            $database = base_path($database);
        }

        if (file_exists($database)) {
            unlink($database);
        }

        $directory = dirname($database);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        touch($database);
    }

    protected function ensureSqliteFile(string $path): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (! file_exists($path)) {
            touch($path);
        }
    }

    protected function connectionUsesSqlite(string $connection): bool
    {
        $driver = config("database.connections.{$connection}.driver");

        return $driver === 'sqlite';
    }
}
