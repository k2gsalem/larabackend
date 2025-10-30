<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Cashier;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasFactory;
    use HasDatabase;
    use Billable;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'plan',
        'admin',
        'data',
        'billing_meta',
    ];

    protected $casts = [
        'data' => 'array',
        'admin' => 'array',
        'billing_meta' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Mutator to store plan information inside the data attribute.
     */
    protected function plan(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ?? $this->data['plan'] ?? null,
            set: function (?string $value): array {
                $data = $this->data ?? [];

                if ($value === null) {
                    unset($data['plan']);
                } else {
                    $data['plan'] = $value;
                }

                return [
                    'plan' => $value,
                    'data' => $data,
                ];
            }
        );
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function getConnectionName()
    {
        return config('tenancy.database.central_connection');
    }

    public static function getCustomColumns(): array
    {
        return array_merge(parent::getCustomColumns(), [
            'name',
            'slug',
            'plan',
            'billing_meta',
            'stripe_id',
            'pm_type',
            'pm_last_four',
            'trial_ends_at',
        ]);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Cashier::$subscriptionModel, 'user_id')->orderBy('created_at', 'desc');
    }
}
