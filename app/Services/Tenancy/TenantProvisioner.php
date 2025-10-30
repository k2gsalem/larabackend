<?php

namespace App\Services\Tenancy;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Tenancy;

class TenantProvisioner
{
    public function __construct(private readonly Tenancy $tenancy)
    {
    }

    public function provision(TenantWithDatabase $tenant, array $adminData = []): void
    {
        $this->tenancy->initialize($tenant);

        try {
            $this->createDefaultRoles();
            $this->createDefaultPermissions();
            $this->createAdminUser($tenant, $adminData);
        } finally {
            $this->tenancy->end();
        }
    }

    private function createDefaultRoles(): void
    {
        foreach (['owner', 'admin', 'member'] as $roleName) {
            Role::findOrCreate($roleName, 'tenant');
        }
    }

    private function createDefaultPermissions(): void
    {
        $permissions = [
            'tenants.manage',
            'users.invite',
            'users.view',
            'users.update',
            'billing.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'tenant');
        }

        Role::findByName('owner', 'tenant')->givePermissionTo($permissions);
        Role::findByName('admin', 'tenant')->givePermissionTo([
            'users.invite',
            'users.view',
            'users.update',
            'billing.manage',
        ]);
    }

    private function createAdminUser(Tenant $tenant, array $providedAdminData = []): void
    {
        $adminData = $providedAdminData === []
            ? (array) ($tenant->getAttribute('admin') ?? [])
            : $providedAdminData;
        if (! array_key_exists('email', $adminData)) {
            return;
        }

        $passwordHash = $adminData['password_hash'] ?? Hash::make(Str::random(32));

        $user = TenantUser::query()->firstOrCreate(
            ['email' => Str::lower($adminData['email'])],
            [
                'name' => $adminData['name'] ?? 'Tenant Owner',
                'password' => $passwordHash,
            ],
        );

        if (! $user->hasRole('owner', 'tenant')) {
            $user->assignRole('owner');
        }
    }
}
