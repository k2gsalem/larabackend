<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="SocialLoginRequest",
 *     required={"provider","access_token"},
 *     @OA\Property(property="provider", type="string", enum={"google","facebook"}),
 *     @OA\Property(property="access_token", type="string"),
 *     @OA\Property(property="device_name", type="string")
 * )
 */
class SocialLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|int|bool>>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'in:google,facebook'],
            'access_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
