<?php

declare(strict_types=1);

namespace App\Http\Requests\TenantUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StoreTenantUserRequest",
 *     required={"name","email"},
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="password", type="string", format="password"),
 *     @OA\Property(property="phone", type="string"),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
 * )
 */
class StoreTenantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'password' => ['nullable', 'string', 'min:12'],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $data['email'] = Str::lower($data['email']);

        return $data;
    }
}
