<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $centralPath = database_path('testing.sqlite');
        $tenantPath = database_path('tenant-testing.sqlite');

        if (! file_exists($centralPath)) {
            touch($centralPath);
        }

        if (! file_exists($tenantPath)) {
            touch($tenantPath);
        }

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
        config()->set('cache.default', 'array');
        config()->set('permission.cache.store', 'array');
        config()->set('queue.default', 'sync');
    }
}
