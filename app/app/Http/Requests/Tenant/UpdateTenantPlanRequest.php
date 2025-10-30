<?php

namespace App\Http\Requests\Tenant;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TenantPlanUpdateRequest",
 *     required={"plan"},
 *     @OA\Property(property="plan", type="string"),
 *     @OA\Property(property="payment_method", type="string")
 * )
 */
class UpdateTenantPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Tenant $tenant */
        $tenant = $this->route('tenant');

        return $this->user()?->can('update', $tenant) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:255'],
        ];
    }
}
