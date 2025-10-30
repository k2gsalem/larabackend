<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Policies\TenantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Tenant::class => TenantPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
