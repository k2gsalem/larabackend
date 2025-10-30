<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TenantLoginRequest;
use App\Http\Requests\Auth\TenantRegisterRequest;
use App\Http\Resources\TenantUserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class TenantAuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     operationId="tenantRegister",
     *     tags={"Tenant Auth"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TenantRegisterRequest")),
     *     @OA\Response(response=201, description="Registered successfully")
     * )
     */
    public function register(TenantRegisterRequest $request): JsonResponse
    {
        [$user, $token] = $this->authService->registerTenantUser($request->validated());

        return response()->json([
            'token' => $token,
            'user' => new TenantUserResource($user->load(['roles', 'permissions'])),
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     operationId="tenantLogin",
     *     tags={"Tenant Auth"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TenantLoginRequest")),
     *     @OA\Response(response=200, description="Authenticated")
     * )
     */
    public function login(TenantLoginRequest $request): JsonResponse
    {
        [$user, $token] = $this->authService->loginTenant($request->validated());

        return response()->json([
            'token' => $token,
            'user' => new TenantUserResource($user->load(['roles', 'permissions'])),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     operationId="tenantMe",
     *     tags={"Tenant Auth"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Authenticated user")
     * )
     */
    public function me(Request $request): TenantUserResource
    {
        return new TenantUserResource($request->user()->load(['roles', 'permissions']));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     operationId="tenantLogout",
     *     tags={"Tenant Auth"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=204, description="Logged out")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(status: 204);
    }
}
