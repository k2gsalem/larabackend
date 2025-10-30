<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_tenant(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        Sanctum::actingAs($admin, ['tenants:create']);

        $payload = [
            'name' => 'Acme Corp',
            'email' => 'owner@acme.test',
            'password' => 'SecurePass!123',
            'plan' => 'pro',
            'domain' => 'acme.localhost',
            'permissions' => ['manage-users'],
        ];

        $response = $this->postJson('/api/v1/admin/tenants', $payload);

        $response->assertCreated();

        $tenant = Tenant::query()->firstWhere('data->owner_email', 'owner@acme.test');
        $this->assertNotNull($tenant);
        $this->assertEquals('pro', $tenant->plan);

        tenancy()->initialize($tenant);
        $this->assertDatabaseHas('users', ['email' => 'owner@acme.test'], 'tenant');
        tenancy()->end();
    }
}
