<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TenantMobileVerifyRequest",
 *     required={"phone","code"},
 *     @OA\Property(property="phone", type="string"),
 *     @OA\Property(property="code", type="string"),
 *     @OA\Property(property="device_name", type="string")
 * )
 */
class TenantMobileVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9]{7,15}$/'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
