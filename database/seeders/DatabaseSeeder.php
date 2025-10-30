<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\Tenancy\TenantService as TenancyTenantService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('ChangeMeNow!123'),
                'is_super_admin' => true,
            ]
        );

        /** @var \App\Models\Tenant  */
         = Tenant::query()->find('acme');

        if (! ) {
            /** @var TenancyTenantService  */
             = app(TenancyTenantService::class);

             = ->createTenant([
                'identifier' => 'acme',
                'name' => 'Acme Incorporated',
                'plan' => 'pro',
                'domain' => 'acme.localhost',
                'admin' => [
                    'name' => 'Acme Owner',
                    'email' => 'owner@acme.test',
                    'password' => 'Password!123',
                ],
            ]);
        }

        tenancy()->initialize();

        try {
             = TenantUser::query()->updateOrCreate(
                ['email' => 'manager@acme.test'],
                [
                    'name' => 'Acme Manager',
                    'password' => 'Password!123',
                    'phone' => '+15550000001',
                ]
            );

            if (! ->hasRole('admin')) {
                ->assignRole('admin');
            }

            if (! TenantUser::query()->whereNotIn('email', ['owner@acme.test', 'manager@acme.test'])->exists()) {
                TenantUser::factory()->count(3)->create();
            }
        } finally {
            tenancy()->end();
        }
    }
}
