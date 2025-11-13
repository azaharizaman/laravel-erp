<?php

namespace Nexus\UomManagement\Database\Factories;

use Nexus\UomManagement\Models\UomConversion;
use Nexus\UomManagement\Models\UomUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UomConversionFactory extends Factory
{
    protected $model = UomConversion::class;

    public function definition(): array
    {
        return [
            'source_unit_id' => UomUnit::factory(),
            'target_unit_id' => UomUnit::factory(),
            'factor' => $this->faker->randomFloat(6, 0.001, 1000),
            'offset' => $this->faker->boolean(10) ? $this->faker->randomFloat(6, -100, 100) : 0,
            'direction' => $this->faker->randomElement(['both', 'to_target', 'from_target']),
            'is_linear' => true,
            'metadata' => null,
        ];
    }

    public function linear(): static
    {
        return $this->state(fn () => ['offset' => 0, 'is_linear' => true]);
    }
}
