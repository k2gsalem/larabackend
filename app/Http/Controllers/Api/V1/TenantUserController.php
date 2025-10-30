<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantUser\StoreTenantUserRequest;
use App\Http\Requests\TenantUser\UpdateTenantUserRequest;
use App\Http\Resources\TenantUserResource;
use App\Models\TenantUser;
use App\Services\TenantUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

class TenantUserController extends Controller
{
    public function __construct(private readonly TenantUserService $tenantUserService)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     operationId="tenantUsersIndex",
     *     tags={"Tenant Users"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Paginated list of tenant users")
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        $users = TenantUser::query()
            ->with(['roles', 'permissions'])
            ->when(request()->filled('search'), function ($query) {
                $term = request()->string('search')->toString();

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(request()->integer('per_page', 15))
            ->withQueryString();

        return TenantUserResource::collection($users);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users",
     *     operationId="tenantUsersStore",
     *     tags={"Tenant Users"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreTenantUserRequest")),
     *     @OA\Response(response=201, description="Tenant user created")
     * )
     */
    public function store(StoreTenantUserRequest $request): JsonResponse
    {
        $user = $this->tenantUserService->create($request->validated());

        return (new TenantUserResource($user))->response()->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{user}",
     *     operationId="tenantUsersShow",
     *     tags={"Tenant Users"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tenant user details")
     * )
     */
    public function show(TenantUser $user): TenantUserResource
    {
        return new TenantUserResource($user->load(['roles', 'permissions']));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/users/{user}",
     *     operationId="tenantUsersUpdate",
     *     tags={"Tenant Users"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateTenantUserRequest")),
     *     @OA\Response(response=200, description="Tenant user updated")
     * )
     */
    public function update(UpdateTenantUserRequest $request, TenantUser $user): TenantUserResource
    {
        $user = $this->tenantUserService->update($user, $request->validated());

        return new TenantUserResource($user);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/users/{user}",
     *     operationId="tenantUsersDestroy",
     *     tags={"Tenant Users"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Tenant user deleted")
     * )
     */
    public function destroy(TenantUser $user): JsonResponse
    {
        $this->tenantUserService->delete($user);

        return response()->json(status: 204);
    }
}
