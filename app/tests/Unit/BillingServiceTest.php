<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Services\BillingService;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionBuilder;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_initialize_subscription_creates_subscription_when_missing(): void
    {
        $tenant = Mockery::mock(Tenant::class);
        $tenant->shouldReceive('getKey')->andReturn('tenant-1');
        $tenant->shouldReceive('createOrGetStripeCustomer')->once();
        $tenant->shouldReceive('updateDefaultPaymentMethod')->with('pm_card_visa')->once();
        $tenant->shouldReceive('subscribed')->with(BillingService::DEFAULT_SUBSCRIPTION)->andReturnFalse();

        $subscriptionBuilder = Mockery::mock(SubscriptionBuilder::class);
        $tenant->shouldReceive('newSubscription')
            ->with(BillingService::DEFAULT_SUBSCRIPTION, 'price_basic')
            ->andReturn($subscriptionBuilder);
        $subscriptionBuilder->shouldReceive('create')->with('pm_card_visa')->once();

        $service = new BillingService();
        $service->initializeSubscription($tenant, 'price_basic', 'pm_card_visa');
    }

    public function test_swap_plan_updates_existing_subscription(): void
    {
        $tenant = Mockery::mock(Tenant::class);
        $tenant->shouldReceive('getKey')->andReturn('tenant-1');
        $tenant->shouldReceive('subscribed')->with(BillingService::DEFAULT_SUBSCRIPTION)->andReturnTrue();
        $tenant->shouldReceive('updateDefaultPaymentMethod')->with('pm_card_mastercard')->once();

        $subscription = Mockery::mock(Subscription::class);
        $tenant->shouldReceive('subscription')
            ->with(BillingService::DEFAULT_SUBSCRIPTION)
            ->andReturn($subscription);
        $subscription->shouldReceive('swapAndInvoice')->with('price_pro')->once();

        $service = new BillingService();
        $service->swapPlan($tenant, 'price_pro', 'pm_card_mastercard');
    }
}
