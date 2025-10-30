<?php

namespace App\Http\Middleware;

use App\Models\TenantUser;
use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureTenantUser
{
    public function __construct(private readonly AuthFactory $auth)
    {
    }

    public function handle(Request $request, Closure $next, ?string $guard = null)
    {
        if (! tenant()) {
            throw new NotFoundHttpException('Tenant context could not be resolved.');
        }

        $user = $this->auth->guard($guard ?? 'sanctum')->user();

        if (! $user instanceof TenantUser) {
            throw new AccessDeniedHttpException('Only tenant users may access this resource.');
        }

        return $next($request);
    }
}
