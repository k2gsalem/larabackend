<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stancl\Tenancy\Jobs\CreateDatabase;

class TenantService
{
    public function createTenant(array $payload): Tenant
    {
        return DB::transaction(function () use ($payload): Tenant {
            $identifier = Str::slug($payload['name']);
            $tenantId = Str::slug($payload['identifier'] ?? $identifier);

            while (Tenant::query()->whereKey($tenantId)->exists()) {
                $tenantId = $identifier.'-'.Str::random(6);
            }

            $admin = [
                'name' => Arr::get($payload, 'admin.name'),
                'email' => Arr::get($payload, 'admin.email'),
                'password_hash' => Hash::make(Arr::get($payload, 'admin.password', Str::random(32))),
            ];

            $this->purgeExistingTenantDatabase($tenantId);

            $tenant = Tenant::create([
                'id' => $tenantId,
            ]);

            $tenant->forceFill([
                'name' => $payload['name'],
                'plan' => $payload['plan'] ?? 'free',
                'admin' => $admin,
            ])->save();

            $tenant->refresh();

            dispatch_sync(new CreateDatabase($tenant));

            $this->migrateTenant($tenant);
            app(TenantProvisioner::class)->provision($tenant, $admin);

            if ($domain = Arr::get($payload, 'domain')) {
                $tenant->domains()->create([
                    'domain' => $domain,
                ]);
            }

            return $tenant;
        });
    }

    public function updatePlan(Tenant $tenant, string $plan): Tenant
    {
        $tenant->plan = $plan;
        $tenant->save();

        return $tenant;
    }

    private function purgeExistingTenantDatabase(string $tenantId): void
    {
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
}
