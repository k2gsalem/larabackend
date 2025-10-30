<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\TenantUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantUserService
{
    /**
     * @param  array{name:string,email:string,password?:string,phone?:string,roles?:array<int,string>,permissions?:array<int,string>}  $payload
     */
    public function create(array $payload): TenantUser
    {
        $user = TenantUser::query()->create([
            'name' => $payload['name'],
            'email' => Str::lower($payload['email']),
            'password' => isset($payload['password']) ? Hash::make($payload['password']) : Hash::make(Str::random(32)),
            'phone' => $payload['phone'] ?? null,
        ]);

        return $this->syncAssignments($user, $payload);
    }

    /**
     * @param  array{name?:string,email?:string,password?:string,phone?:string,roles?:array<int,string>,permissions?:array<int,string>}  $payload
     */
    public function update(TenantUser $user, array $payload): TenantUser
    {
        $user->fill(array_filter([
            'name' => $payload['name'] ?? null,
            'email' => isset($payload['email']) ? Str::lower($payload['email']) : null,
            'phone' => $payload['phone'] ?? null,
        ], fn ($value) => ! is_null($value)));

        if (! empty($payload['password'])) {
            $user->password = Hash::make($payload['password']);
        }

        $user->save();

        return $this->syncAssignments($user, $payload);
    }

    public function delete(TenantUser $user): void
    {
        $user->tokens()->delete();
        $user->delete();
    }

    /**
     * @param  array{name?:string,email?:string,password?:string,phone?:string,roles?:array<int,string>,permissions?:array<int,string>}  $payload
     */
    private function syncAssignments(TenantUser $user, array $payload): TenantUser
    {
        if (array_key_exists('roles', $payload)) {
            $roles = collect($payload['roles'] ?? [])
                ->filter()
                ->map(fn (string $role) => Role::findOrCreate($role, 'tenant'))
                ->map->name
                ->toArray();

            $user->syncRoles($roles);
        }

        if (array_key_exists('permissions', $payload)) {
            $permissions = collect($payload['permissions'] ?? [])
                ->filter()
                ->map(fn (string $permission) => Permission::findOrCreate($permission, 'tenant'))
                ->map->name
                ->toArray();

            $user->syncPermissions($permissions);
        }

        return $user->load(['roles', 'permissions']);
    }
}
