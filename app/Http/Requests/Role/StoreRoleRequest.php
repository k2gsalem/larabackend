<?php

declare(strict_types=1);

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StoreRoleRequest",
 *     required={"name"},
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
 * )
 */
class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:100'],
        ];
    }
}
