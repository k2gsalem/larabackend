<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_register_and_login(): void
    {
        $tenant = Tenant::factory()->create([
            'id' => (string) Str::uuid(),
            'data' => [
                'owner_email' => 'owner@example.com',
            ],
        ]);

        $register = $this->withHeader('X-Tenant', $tenant->getTenantKey())
            ->postJson('/api/v1/auth/register', [
                'name' => 'Tenant Admin',
                'email' => 'admin@example.com',
                'password' => 'TenantPass!123',
                'password_confirmation' => 'TenantPass!123',
                'roles' => ['owner'],
            ]);

        $register->assertCreated();

        tenancy()->initialize($tenant);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com'], 'tenant');
        tenancy()->end();

        $login = $this->withHeader('X-Tenant', $tenant->getTenantKey())
            ->postJson('/api/v1/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'TenantPass!123',
            ]);

        $login->assertOk()->assertJsonStructure(['token', 'user']);
    }
}
