<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantPlanRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\BillingService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
        private readonly BillingService $billingService
    ) {
        $this->authorizeResource(Tenant::class, 'tenant');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tenants",
     *     operationId="listTenants",
     *     tags={"Tenants"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="plan", in="query", description="Filter by subscription plan", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Results per page", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of tenants")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Tenant::query()
            ->with('domains')
            ->when($request->filled('plan'), fn ($builder) => $builder->where('plan', $request->string('plan')))
            ->orderByDesc('created_at');

        $tenants = $query->paginate($request->integer('per_page', 15))->withQueryString();

        return TenantResource::collection($tenants);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/tenants",
     *    operationId="createTenant",
     *    tags={"Tenants"},
     *    security={{"sanctum": {}}},
     *    @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TenantCreateRequest")),
     *    @OA\Response(response=201, description="Tenant created successfully"),
     *    @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->create($request->validated());

        return (new TenantResource($tenant->load('domains')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tenants/{tenant}",
     *     operationId="showTenant",
     *     tags={"Tenants"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="tenant", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Tenant details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Tenant $tenant): TenantResource
    {
        return new TenantResource($tenant->load('domains'));
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/tenants/{tenant}",
     *     operationId="updateTenantPlan",
     *     tags={"Tenants"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="tenant", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TenantPlanUpdateRequest")),
     *     @OA\Response(response=200, description="Tenant updated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateTenantPlanRequest $request, Tenant $tenant): TenantResource
    {
        $tenant = $this->tenantService->updatePlan(
            $tenant,
            $request->string('plan')->toString(),
            $request->validated('payment_method')
        );

        return new TenantResource($tenant);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/tenants/{tenant}",
     *     operationId="deleteTenant",
     *     tags={"Tenants"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="tenant", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=204, description="Tenant deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->billingService->cancel($tenant);
        $tenant->delete();

        return response()->json(status: 204);
    }
}
