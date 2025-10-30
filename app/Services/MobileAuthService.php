<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TenantUser;
use App\Models\TenantUserOtp;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileAuthService
{
    public function __construct(private readonly int $otpTtlMinutes = 10, private readonly int $maxAttempts = 5)
    {
    }

    /**
     * @param  array{phone:string,name?:string,email?:string}  $payload
     */
    public function request(array $payload): TenantUserOtp
    {
        $phone = $payload['phone'];

        $user = TenantUser::query()->where('phone', $phone)->first();

        if (! $user) {
            $email = $payload['email'] ?? sprintf('%s@mobile.local', Str::uuid());

            $user = TenantUser::query()->create([
                'name' => $payload['name'] ?? 'Mobile User',
                'email' => Str::lower($email),
                'password' => Hash::make(Str::random(32)),
                'phone' => $phone,
            ]);
        }

        TenantUserOtp::query()->where('phone', $phone)->delete();

        $code = (string) random_int(100000, 999999);

        $otp = TenantUserOtp::query()->create([
            'user_id' => $user->id,
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes($this->otpTtlMinutes),
        ]);

        $otp->setAttribute('plain_code', $code);

        return $otp;
    }

    /**
     * @return array{0: TenantUser, 1: string}
     */
    public function verify(string $phone, string $code, ?string $deviceName = null): array
    {
        $otp = TenantUserOtp::query()
            ->where('phone', $phone)
            ->orderByDesc('created_at')
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            if ($otp->attempts >= $this->maxAttempts) {
                $otp->delete();
            }

            throw new AuthenticationException('Invalid verification code.');
        }

        $user = $otp->user;

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['No user is associated with this phone number.'],
            ]);
        }

        $user->forceFill([
            'phone' => $phone,
            'phone_verified_at' => Carbon::now(),
            'last_login_at' => Carbon::now(),
        ])->save();

        $otp->delete();

        $token = $user->createToken($deviceName ?? 'tenant-api', ['*'])->plainTextToken;

        return [$user, $token];
    }
}
