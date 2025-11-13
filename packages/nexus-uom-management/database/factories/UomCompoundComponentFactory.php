<?php

namespace Nexus\UomManagement\Database\Factories;

use Nexus\UomManagement\Models\UomCompoundComponent;
use Nexus\UomManagement\Models\UomCompoundUnit;
use Nexus\UomManagement\Models\UomUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UomCompoundComponentFactory extends Factory
{
    protected $model = UomCompoundComponent::class;

    public function definition(): array
    {
        return [
            'compound_unit_id' => UomCompoundUnit::factory(),
            'unit_id' => UomUnit::factory(),
            'exponent' => $this->faker->randomElement([-3, -2, -1, 1, 2, 3]),
        ];
    }
}
