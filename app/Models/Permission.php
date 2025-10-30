<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as BasePermission;

class Permission extends BasePermission
{
    protected $connection = 'tenant';
    protected $guard_name = 'tenant';
}
