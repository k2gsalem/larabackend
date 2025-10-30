<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        abort_if(! $tenant instanceof TenantContract, Response::HTTP_NOT_FOUND, 'Tenant context missing.');

        $user = $request->user();

        abort_if($user === null, Response::HTTP_UNAUTHORIZED, 'Authentication required.');

        if ($user->getAttribute('tenant_id') !== null && $user->getAttribute('tenant_id') !== $tenant->getKey()) {
            abort(Response::HTTP_FORBIDDEN, 'This action is only allowed for tenant members.');
        }

        return $next($request);
    }
}
