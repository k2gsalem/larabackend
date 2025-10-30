<?php

namespace Tests\Feature;

use App\Services\Tenancy\TenantService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class TenantCreationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_tenant_can_be_created_through_service(): void
    {
        /** @var TenantService $service */
        $service = app(TenantService::class);

        $tenant = $service->createTenant([
            'name' => 'Acme Inc',
            'plan' => 'pro',
            'domain' => 'acme.localhost',
            'admin' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password123',
            ],
        ]);

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
        $this->assertTrue($tenant->domains()->where('domain', 'acme.localhost')->exists());

        tenancy()->initialize($tenant);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com'], 'tenant');

        tenancy()->end();
    }
}
