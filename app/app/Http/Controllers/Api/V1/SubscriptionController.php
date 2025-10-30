<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\SubscriptionRequest;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptionService)
    {
        $this->middleware('tenant.auth');
    }

    public function store(SubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->createOrUpdate(
            $request->user(),
            $request->validated('plan'),
            $request->validated('payment_method'),
        );

        return response()->json([
            'status' => 'active',
            'plan' => $subscription->stripe_price,
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request): Response
    {
        $this->subscriptionService->cancel($request->user());

        return response()->noContent();
    }
}
