<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CentralLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class CentralAuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/auth/login",
     *     operationId="centralLogin",
     *     tags={"Central Auth"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CentralLoginRequest")),
     *     @OA\Response(response=200, description="Authenticated")
     * )
     */
    public function login(CentralLoginRequest $request): JsonResponse
    {
        [$user, $token] = $this->authService->loginCentral($request->validated());

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/auth/me",
     *     operationId="centralMe",
     *     tags={"Central Auth"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Authenticated user")
     * )
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/auth/logout",
     *     operationId="centralLogout",
     *     tags={"Central Auth"},
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
