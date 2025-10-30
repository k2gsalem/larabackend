<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;
use Throwable;

class TenantService
{
    public function __construct(private readonly BillingService $billingService)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Tenant
    {
        return DB::transaction(function () use ($payload) {
            $tenantId = $payload['identifier'] ?? (string) Str::uuid();
            $slug = Str::slug($payload['slug'] ?? $payload['name']);

            /** @var Tenant $tenant */
            $tenant = Tenant::query()->create([
                'id' => $tenantId,
                'name' => $payload['name'],
                'slug' => $slug,
                'plan' => $payload['plan'] ?? config('tenancy.defaults.plan', 'starter'),
                'data' => [
                    'owner_email' => $payload['email'],
                    'meta' => Arr::except($payload, ['name', 'email', 'password', 'domain', 'plan', 'permissions', 'identifier', 'payment_method']),
                ],
            ]);

            if (! empty($payload['domain'])) {
                $this->attachDomain($tenant, $payload['domain']);
            }

            tenancy()->initialize($tenant);

            try {
                $this->seedTenantUser($payload);
            } finally {
                tenancy()->end();
            }

            if (! empty($payload['payment_method'])) {
                $this->billingService->initializeSubscription($tenant, $tenant->plan, $payload['payment_method']);
            }

            return $tenant->refresh();
        });
    }

    public function attachDomain(Tenant $tenant, string $domain): Domain
    {
        $sanitized = Str::lower(Str::of($domain)->trim());

        return $tenant->domains()->create([
            'domain' => $sanitized,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function seedTenantUser(array $payload): void
    {
        /** @var TenantUser $user */
        $user = TenantUser::query()->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
        ]);

        $roleName = $payload['role'] ?? 'owner';
        $role = Role::findOrCreate($roleName, 'tenant');

        $permissions = collect($payload['permissions'] ?? [
            'manage-users',
            'manage-billing',
            'manage-subscription',
        ])
            ->filter()
            ->map(fn (string $name) => Str::of($name)->trim()->lower()->value())
            ->unique()
            ->map(fn (string $name) => Permission::findOrCreate($name, 'tenant'));

        if ($permissions->isNotEmpty()) {
            $role->syncPermissions($permissions);
        }

        $user->assignRole($role);
    }

    public function updatePlan(Tenant $tenant, string $plan, ?string $paymentMethod = null): Tenant
    {
        $tenant->fill([
            'plan' => $plan,
        ])->save();

        try {
            $this->billingService->swapPlan($tenant, $plan, $paymentMethod);
        } catch (Throwable $exception) {
            Log::channel('stack')->error('Unable to sync billing plan', [
                'tenant_id' => $tenant->getKey(),
                'plan' => $plan,
                'message' => $exception->getMessage(),
            ]);
        }

        return $tenant->refresh();
    }
}
