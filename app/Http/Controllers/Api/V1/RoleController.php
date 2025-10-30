<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        Role::setDefaultGuardName('tenant');
        Permission::setDefaultGuardName('tenant');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/roles",
     *     operationId="tenantRolesIndex",
     *     tags={"Tenant Roles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of roles")
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        $roles = Role::query()
            ->where('guard_name', 'tenant')
            ->with('permissions')
            ->orderBy('name')
            ->paginate(request()->integer('per_page', 25))
            ->withQueryString();

        return RoleResource::collection($roles);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/roles",
     *     operationId="tenantRolesStore",
     *     tags={"Tenant Roles"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreRoleRequest")),
     *     @OA\Response(response=201, description="Role created")
     * )
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::findOrCreate($request->string('name')->toString(), 'tenant');

        $this->syncPermissions($role, $request->validated('permissions') ?? []);

        return (new RoleResource($role->load('permissions')))->response()->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/roles/{role}",
     *     operationId="tenantRolesShow",
     *     tags={"Tenant Roles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Role details")
     * )
     */
    public function show(Role $role): RoleResource
    {
        abort_unless($role->guard_name === 'tenant', 404);

        return new RoleResource($role->load('permissions'));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/roles/{role}",
     *     operationId="tenantRolesUpdate",
     *     tags={"Tenant Roles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateRoleRequest")),
     *     @OA\Response(response=200, description="Role updated")
     * )
     */
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        abort_unless($role->guard_name === 'tenant', 404);

        if ($request->filled('name')) {
            $role->name = $request->string('name')->toString();
            $role->save();
        }

        if ($request->has('permissions')) {
            $this->syncPermissions($role, $request->validated('permissions') ?? []);
        }

        return new RoleResource($role->load('permissions'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/roles/{role}",
     *     operationId="tenantRolesDestroy",
     *     tags={"Tenant Roles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Role deleted")
     * )
     */
    public function destroy(Role $role): JsonResponse
    {
        abort_unless($role->guard_name === 'tenant', 404);

        $role->delete();

        return response()->json(status: 204);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function syncPermissions(Role $role, array $permissions): void
    {
        $resolved = collect($permissions)
            ->filter()
            ->map(fn (string $permission) => Permission::findOrCreate($permission, 'tenant'))
            ->map->name
            ->toArray();

        $role->syncPermissions($resolved);
    }
}
