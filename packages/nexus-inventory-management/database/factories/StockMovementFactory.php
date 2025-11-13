<?php

namespace Nexus\InventoryManagement\Database\Factories;

use Nexus\InventoryManagement\Models\Stock;
use Nexus\InventoryManagement\Models\StockMovement;
use Nexus\InventoryManagement\Models\Transactions\StockIn;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        $quantityBefore = $this->faker->randomFloat(4, 0, 250);
        $quantityChange = $this->faker->randomFloat(4, 1, 50);

        return [
            'stock_id' => Stock::factory(),
            'serial_number' => $this->faker->unique()->uuid,
            'transactionable_id' => StockIn::factory(),
            'transactionable_type' => StockIn::class,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantityChange,
            'quantity_after' => $quantityBefore + $quantityChange,
            'reason' => $this->faker->sentence,
        ];
    }
}
