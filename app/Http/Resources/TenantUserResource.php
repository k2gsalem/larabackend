<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TenantUser */
class TenantUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'phone' => $this->phone,
            'phone_verified_at' => $this->phone_verified_at,
            'last_login_at' => $this->last_login_at,
            'provider' => $this->when($this->provider_name, function () {
                return [
                    'name' => $this->provider_name,
                    'id' => $this->provider_id,
                    'avatar' => $this->provider_avatar,
                ];
            }),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
