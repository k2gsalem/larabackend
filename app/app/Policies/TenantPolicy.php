<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->is_super_admin) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_super_admin || $user->tokenCan('tenants:view');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->is_super_admin || $user->tokenCan('tenants:view');
    }

    public function create(User $user): bool
    {
        return $user->is_super_admin || $user->tokenCan('tenants:create');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->is_super_admin || $user->tokenCan('tenants:update');
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->is_super_admin || $user->tokenCan('tenants:delete');
    }
}
