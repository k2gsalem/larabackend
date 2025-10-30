<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        $slug = Str::slug($name.'-'.$this->faker->unique()->word());

        return [
            'id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => $slug,
            'plan' => 'starter',
            'data' => [
                'owner_email' => $this->faker->unique()->safeEmail(),
            ],
        ];
    }
}
