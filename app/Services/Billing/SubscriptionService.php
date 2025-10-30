<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Subscription as CashierSubscription;
use RuntimeException;

class SubscriptionService
{
    public function __construct(private ?BillingService $billingService = null)
    {
        $this->billingService ??= app(BillingService::class);
    }

    public function createOrUpdate(User $user, string $plan, string $paymentMethod): CashierSubscription
    {
        $tenant = $this->resolveTenant();
        $this->ensureUserCanManageBilling($user);

        $hasActiveSubscription = $tenant->subscribed(BillingService::DEFAULT_SUBSCRIPTION);

        if (! $hasActiveSubscription) {
            $this->billingService->initializeSubscription($tenant, $plan, $paymentMethod);
        } else {
            $this->billingService->swapPlan($tenant, $plan, $paymentMethod);
        }

        $tenant->forceFill(['plan' => $plan])->save();

        $subscription = $tenant->refresh()->subscription(BillingService::DEFAULT_SUBSCRIPTION);

        if (! $subscription instanceof CashierSubscription) {
            throw new RuntimeException('Subscription record could not be retrieved.');
        }

        return $subscription;
    }

    public function cancel(User $user): void
    {
        $tenant = $this->resolveTenant();
        $this->ensureUserCanManageBilling($user);

        $this->billingService->cancel($tenant, false);

        $defaultPlan = Config::get('tenancy.defaults.plan', 'starter');
        $tenant->forceFill(['plan' => $defaultPlan])->save();
    }

    private function resolveTenant(): Tenant
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('Tenant context could not be determined.');
        }

        return $tenant;
    }

    private function ensureUserCanManageBilling(User $user): void
    {
        if (! $user instanceof TenantUser || ! $user->can('billing.manage')) {
            throw new AuthorizationException('You are not authorized to manage billing for this tenant.');
        }
    }
}
