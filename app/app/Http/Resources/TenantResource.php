<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Tenant */
class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'plan' => $this->plan,
            'trial_ends_at' => $this->trial_ends_at,
            'domains' => $this->whenLoaded('domains', fn () => $this->domains->pluck('domain')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
