<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Cashier\Billable;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasFactory;
    use Billable;
    use HasDatabase;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'plan',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'billing_meta',
        'data',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'data' => 'array',
    ];

    public function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->data['name'] ?? $this->id,
        );
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'plan',
            'stripe_id',
            'pm_type',
            'pm_last_four',
            'trial_ends_at',
            'billing_meta',
            'created_at',
            'updated_at',
            'data',
        ];
    }
}
