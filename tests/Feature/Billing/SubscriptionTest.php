<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Services\Billing\SubscriptionService;
use App\Services\Tenancy\TenantService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription as CashierSubscription;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_subscription_can_be_created_and_cancelled(): void
    {
        $tenant = app(TenantService::class)->createTenant([
            'name' => 'Billing Tenant',
            'admin' => [
                'name' => 'Owner',
                'email' => 'owner@example.com',
                'password' => 'password123',
            ],
        ]);

        $registerResponse = $this->withHeader('X-Tenant', $tenant->id)->postJson('/api/v1/auth/register', [
            'name' => 'Billing User',
            'email' => 'billing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['admin'],
        ])->assertCreated();

        $token = $registerResponse->json('token');

        app()->bind(SubscriptionService::class, static fn (): SubscriptionService => new class extends SubscriptionService {
            public function createOrUpdate(User $user, string $plan, ?string $paymentMethod = null): CashierSubscription
            {
                $subscription = new CashierSubscription();
                $subscription->setConnection('tenant');
                $subscription->forceFill([
                    'user_id' => $user->getKey(),
                    'type' => 'default',
                    'stripe_id' => 'sub_'.Str::random(12),
                    'stripe_status' => 'active',
                    'stripe_price' => $plan,
                ]);
                $subscription->save();

                return $subscription;
            }

            public function cancel(User $user): void
            {
                CashierSubscription::on('tenant')->where('user_id', $user->getKey())->delete();
            }
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Tenant', $tenant->id)
            ->postJson('/api/v1/billing/subscriptions', [
                'plan' => 'price_test',
                'payment_method' => 'pm_card_visa',
            ])->assertOk();

        tenancy()->initialize($tenant);
        $this->assertDatabaseHas('subscriptions', ['stripe_price' => 'price_test'], 'tenant');
        tenancy()->end();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Tenant', $tenant->id)
            ->deleteJson('/api/v1/billing/subscriptions')
            ->assertNoContent();
    }
}
