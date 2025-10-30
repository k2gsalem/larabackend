<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TenantUser;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthService
{
    /**
     * @return array{0: \App\Models\TenantUser, 1: string}
     */
    public function authenticate(string $provider, string $accessToken, ?string $deviceName = null): array
    {
        $driver = Socialite::driver($provider);

        if (method_exists($driver, 'stateless')) {
            /** @var Provider $driver */
            $driver = $driver->stateless();
        }

        $providerUser = $driver->userFromToken($accessToken);

        $email = $providerUser->getEmail();
        $providerId = (string) $providerUser->getId();

        if (! $providerId) {
            throw new AuthenticationException('Unable to authenticate with the selected provider.');
        }

        $name = $providerUser->getName() ?: $providerUser->getNickname() ?: $email;

        if (! $name) {
            $name = Str::title($provider).' User';
        }

        $email = $email ? Str::lower($email) : null;

        return DB::connection('tenant')->transaction(function () use ($provider, $providerId, $providerUser, $email, $name, $deviceName) {
            $query = TenantUser::query()->where('provider_name', $provider)->where('provider_id', $providerId);

            if ($email) {
                $query->orWhere('email', $email);
            }

            $user = $query->lockForUpdate()->first();

            if (! $user) {
                if (! $email) {
                    $email = sprintf('%s@%s.local', Str::uuid(), Str::lower($provider));
                }

                $user = TenantUser::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'email_verified_at' => $providerUser->getEmail() ? Carbon::now() : null,
                    'password' => Hash::make(Str::random(32)),
                ]);
            }

            $user->forceFill([
                'provider_name' => $provider,
                'provider_id' => $providerId,
                'provider_avatar' => $providerUser->getAvatar(),
                'email' => $email ?? $user->email,
                'last_login_at' => Carbon::now(),
            ])->save();

            $token = $user->createToken($deviceName ?? 'tenant-api', ['*'])->plainTextToken;

            return [$user, $token];
        });
    }
}
