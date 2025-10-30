<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasFactory;
    use HasDatabase;

    protected $fillable = [
        'id',
        'name',
        'plan',
        'admin',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'admin' => 'array',
    ];

    /**
     * Mutator to store plan information inside the data attribute.
     */
    protected function plan(): Attribute
    {
        return Attribute::get(fn () => $this->data['plan'] ?? null)
            ->set(function (?string $value): array {
                $data = $this->data ?? [];
                $data['plan'] = $value;

                return $data;
            });
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function getConnectionName()
    {
        return config('tenancy.database.central_connection');
    }
}
