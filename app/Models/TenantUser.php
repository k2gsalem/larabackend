<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;

class TenantUser extends User
{
    use HasRoles;

    protected $connection = 'tenant';
    protected $table = 'users';
    protected string $guard_name = 'tenant';

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'phone',
        'phone_verified_at',
        'provider_name',
        'provider_id',
        'provider_avatar',
        'last_login_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ]);
    }
}
