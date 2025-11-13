<?php

namespace Nexus\InventoryManagement\Database\Factories;

use Nexus\InventoryManagement\Models\Item;
use Nexus\InventoryManagement\Models\Location;
use Nexus\InventoryManagement\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'itemable_id' => Item::factory(),
            'itemable_type' => Item::class,
            'location_id' => Location::factory(),
            'quantity' => $this->faker->randomFloat(4, 0, 500),
        ];
    }
}
