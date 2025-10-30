<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Http\Requests\Auth\TenantLoginRequest;
use App\Http\Requests\Auth\TenantMobileOtpRequest;
use App\Http\Requests\Auth\TenantMobileVerifyRequest;
use App\Http\Requests\Auth\TenantRegisterRequest;
use App\Http\Resources\TenantUserResource;
use App\Services\AuthService;
use App\Services\MobileAuthService;
use App\Services\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class TenantAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly SocialAuthService $socialAuthService,
        private readonly MobileAuthService $mobileAuthService
    ) {
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
     * @OA\Post(
     *     path="/api/v1/auth/social",
     *     operationId="tenantSocialLogin",
     *     tags={"Tenant Auth"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/SocialLoginRequest")),
     *     @OA\Response(response=200, description="Authenticated via social provider")
     * )
     */
    public function socialLogin(SocialLoginRequest $request): JsonResponse
    {
        $provider = $request->string('provider')->lower()->toString();
        $accessToken = $request->string('access_token')->toString();
        $deviceName = $request->string('device_name')->trim()->toString();

        [$user, $token] = $this->socialAuthService->authenticate(
            $provider,
            $accessToken,
            $deviceName !== '' ? $deviceName : null
        );

        return response()->json([
            'token' => $token,
            'user' => new TenantUserResource($user->load(['roles', 'permissions'])),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/mobile/request",
     *     operationId="tenantMobileRequest",
     *     tags={"Tenant Auth"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TenantMobileOtpRequest")),
     *     @OA\Response(response=202, description="OTP dispatched")
     * )
     */
    public function requestOtp(TenantMobileOtpRequest $request): JsonResponse
    {
        $otp = $this->mobileAuthService->request($request->validated());

        $payload = [
            'message' => 'Verification code dispatched successfully.',
            'expires_at' => $otp->expires_at,
        ];

        if (config('app.debug')) {
            $payload['debug_code'] = $otp->getAttribute('plain_code');
        }

        return response()->json($payload, 202);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/mobile/verify",
     *     operationId="tenantMobileVerify",
     *     tags={"Tenant Auth"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TenantMobileVerifyRequest")),
     *     @OA\Response(response=200, description="Authenticated via mobile OTP")
     * )
     */
    public function verifyOtp(TenantMobileVerifyRequest $request): JsonResponse
    {
        $deviceName = $request->string('device_name')->trim()->toString();

        [$user, $token] = $this->mobileAuthService->verify(
            $request->string('phone')->toString(),
            $request->string('code')->toString(),
            $deviceName !== '' ? $deviceName : null
        );

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
