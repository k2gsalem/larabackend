<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TenantMobileOtpRequest",
 *     required={"phone"},
 *     @OA\Property(property="phone", type="string"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", format="email")
 * )
 */
class TenantMobileOtpRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
