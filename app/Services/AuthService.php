<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * @param  array{name:string,email:string,password:string,roles?:array<int,string>,permissions?:array<int,string>,device_name?:string,phone?:string}  $data
     */
    public function registerTenantUser(array $data): array
    {
        $user = TenantUser::query()->create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
        ]);

        $this->syncRolesAndPermissions($user, $data['roles'] ?? null, $data['permissions'] ?? null);

        $token = $user->createToken($data['device_name'] ?? 'tenant-api', ['*'])->plainTextToken;

        return [$user, $token];
    }

    /**
     * @param  array{email:string,password:string,device_name?:string}  $credentials
     */
    public function loginTenant(array $credentials): array
    {
        $user = TenantUser::query()
            ->where('email', Str::lower($credentials['email']))
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $user->forceFill(['last_login_at' => Carbon::now()])->save();

        $token = $user->createToken($credentials['device_name'] ?? 'tenant-api', ['*'])->plainTextToken;

        return [$user, $token];
    }

    /**
     * @param  array{email:string,password:string,device_name?:string}  $credentials
     */
    public function loginCentral(array $credentials): array
    {
        $user = User::query()
            ->where('email', Str::lower($credentials['email']))
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $abilities = $user->is_super_admin
            ? ['tenants:view', 'tenants:create', 'tenants:update', 'tenants:delete']
            : ['tenants:view'];

        $token = $user->createToken($credentials['device_name'] ?? 'central-api', $abilities)->plainTextToken;

        return [$user, $token];
    }

    public function logout(Authenticatable $user, ?string $token = null): void
    {
        if ($token) {
            $user->tokens()->where('id', $token)->delete();

            return;
        }

        $user->currentAccessToken()?->delete();
    }

    /**
     * @param  array<int, string>|null  $roles
     * @param  array<int, string>|null  $permissions
     */
    private function syncRolesAndPermissions(TenantUser $user, ?array $roles, ?array $permissions): void
    {
        if ($roles !== null) {
            $resolvedRoles = collect($roles)
                ->filter()
                ->map(fn (string $role) => Role::findOrCreate($role, 'tenant'))
                ->map->name
                ->toArray();

            $user->syncRoles($resolvedRoles);
        }

        if ($permissions !== null) {
            $resolvedPermissions = collect($permissions)
                ->filter()
                ->map(fn (string $permission) => Permission::findOrCreate($permission, 'tenant'))
                ->map->name
                ->toArray();

            $user->syncPermissions($resolvedPermissions);
        }
    }
}
