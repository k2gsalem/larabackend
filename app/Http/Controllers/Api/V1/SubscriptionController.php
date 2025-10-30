<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\SubscriptionRequest;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptionService)
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/billing/subscriptions",
     *     operationId="tenantSubscriptionStore",
     *     tags={"Billing"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/SubscriptionRequest")),
     *     @OA\Response(response=200, description="Subscription activated")
     * )
     */
    public function store(SubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $subscription = $this->subscriptionService->createOrUpdate(
            $request->user(),
            $validated['plan'],
            $validated['payment_method'] ?? null,
        );

        return response()->json([
            'status' => 'active',
            'plan' => $subscription->stripe_price,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/billing/subscriptions",
     *     operationId="tenantSubscriptionCancel",
     *     tags={"Billing"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=204, description="Subscription cancelled")
     * )
     */
    public function destroy(Request $request)
    {
        $this->subscriptionService->cancel($request->user());

        return response()->noContent();
    }
}
