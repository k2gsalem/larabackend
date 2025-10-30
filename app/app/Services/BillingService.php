<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Throwable;

class BillingService
{
    public const DEFAULT_SUBSCRIPTION = 'default';

    public function initializeSubscription(Tenant $tenant, string $priceId, string $paymentMethod): void
    {
        try {
            $tenant->createOrGetStripeCustomer();
            $tenant->updateDefaultPaymentMethod($paymentMethod);

            if ($tenant->subscribed(self::DEFAULT_SUBSCRIPTION)) {
                $tenant->subscription(self::DEFAULT_SUBSCRIPTION)->swapAndInvoice($priceId);
            } else {
                $tenant->newSubscription(self::DEFAULT_SUBSCRIPTION, $priceId)->create($paymentMethod);
            }
        } catch (Throwable $exception) {
            Log::channel('stack')->error('Subscription provisioning failed', [
                'tenant_id' => $tenant->getKey(),
                'price_id' => $priceId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function swapPlan(Tenant $tenant, string $priceId, ?string $paymentMethod = null): void
    {
        if (! $tenant->subscribed(self::DEFAULT_SUBSCRIPTION)) {
            if ($paymentMethod === null) {
                throw new \InvalidArgumentException('Payment method is required to create a new subscription.');
            }

            $this->initializeSubscription($tenant, $priceId, $paymentMethod);

            return;
        }

        try {
            if ($paymentMethod) {
                $tenant->updateDefaultPaymentMethod($paymentMethod);
            }

            $tenant->subscription(self::DEFAULT_SUBSCRIPTION)->swapAndInvoice($priceId);
        } catch (Throwable $exception) {
            Log::channel('stack')->error('Failed to swap subscription', [
                'tenant_id' => $tenant->getKey(),
                'price_id' => $priceId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function cancel(Tenant $tenant, bool $immediately = false): void
    {
        if (! $tenant->subscribed(self::DEFAULT_SUBSCRIPTION)) {
            return;
        }

        try {
            if ($immediately) {
                $tenant->subscription(self::DEFAULT_SUBSCRIPTION)?->cancelNow();
            } else {
                $tenant->subscription(self::DEFAULT_SUBSCRIPTION)?->cancel();
            }
        } catch (Throwable $exception) {
            Log::channel('stack')->error('Failed to cancel subscription', [
                'tenant_id' => $tenant->getKey(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function webhookSecret(): ?string
    {
        return config('cashier.webhook.secret');
    }

    public function stripeCurrency(): string
    {
        return Cashier::usesCurrency()[0];
    }
}
