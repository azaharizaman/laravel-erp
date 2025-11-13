<?php

namespace Nexus\InventoryManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\InventoryManagement\Models\Location;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition()
    {
        return [
            'name' => $this->faker->city,
        ];
    }
}
