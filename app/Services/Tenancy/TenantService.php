<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class TenantService
{
    public function createTenant(array $payload): Tenant
    {
        return DB::transaction(function () use ($payload): Tenant {
            $tenantId = Str::slug($payload['identifier'] ?? Str::uuid());
            if ($tenantId === '') {
                $tenantId = (string) Str::uuid();
            }

            while (Tenant::query()->whereKey($tenantId)->exists() || $this->tenantDatabaseExists($tenantId)) {
                $tenantId = (string) Str::uuid();
            }

            $slugSource = Str::slug($payload['slug'] ?? $payload['name']);
            $slug = $slugSource;

            while (Tenant::query()->where('slug', $slug)->exists()) {
                $slug = $slugSource.'-'.Str::random(6);
            }

            $admin = [
                'name' => Arr::get($payload, 'admin.name'),
                'email' => Arr::get($payload, 'admin.email'),
                'password_hash' => Hash::make(Arr::get($payload, 'admin.password', Str::random(32))),
            ];

            $this->purgeExistingTenantDatabase($tenantId);

            $tenant = Tenant::create([
                'id' => $tenantId,
                'name' => $payload['name'],
                'slug' => $slug,
                'plan' => $payload['plan'] ?? config('tenancy.defaults.plan', 'starter'),
                'admin' => $admin,
                'data' => [
                    'owner_email' => Arr::get($admin, 'email'),
                ],
            ]);
            $this->migrateTenant($tenant);
            app(TenantProvisioner::class)->provision($tenant, $admin);

            if ($domain = Arr::get($payload, 'domain')) {
                $tenant->domains()->create([
                    'domain' => Str::of($domain)->trim()->lower()->value(),
                ]);
            }

            return $tenant;
        });
    }

    public function updatePlan(Tenant $tenant, string $plan): Tenant
    {
        $tenant->plan = $plan;
        $tenant->save();

        return $tenant->refresh();
    }

    private function purgeExistingTenantDatabase(string $tenantId): void
    {
        DB::disconnect('tenant');
        DB::purge('tenant');

        $templateConnection = config('tenancy.database.template_tenant_connection', config('tenancy.database.central_connection'));
        $connectionConfig = config("database.connections.{$templateConnection}");

        if (($connectionConfig['driver'] ?? null) !== 'sqlite') {
            return;
        }

        $databaseName = (config('tenancy.database.prefix') ?? 'tenant_').$tenantId.(config('tenancy.database.suffix') ?? '');
        $path = database_path($databaseName);

        if (file_exists($path)) {
            @unlink($path);
        }
    }

    private function migrateTenant(Tenant $tenant): void
    {
        tenancy()->initialize($tenant);

        try {
            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->getTenantKey()],
                '--force' => true,
            ]);
        } finally {
            tenancy()->end();
        }
    }

    private function tenantDatabaseExists(string $tenantId): bool
    {
        $databaseName = (config('tenancy.database.prefix') ?? 'tenant_').$tenantId.(config('tenancy.database.suffix') ?? '');

        return file_exists(database_path($databaseName));
    }
}
