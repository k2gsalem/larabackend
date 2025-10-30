<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="SubscriptionRequest",
 *     required={"plan"},
 *     @OA\Property(property="plan", type="string"),
 *     @OA\Property(property="payment_method", type="string", nullable=true)
 * )
 */
class SubscriptionRequest extends FormRequest
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
            'plan' => ['required', 'string', 'max:191'],
            'payment_method' => ['nullable', 'string', 'max:255'],
        ];
    }
}

