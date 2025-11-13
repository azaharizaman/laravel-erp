<?php

namespace Nexus\InventoryManagement\Database\Factories\Transactions;

use Nexus\InventoryManagement\Models\Stock;
use Nexus\InventoryManagement\Models\Transactions\StockOut;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockOutFactory extends Factory
{
    protected $model = StockOut::class;

    public function definition(): array
    {
        return [
            'stock_id' => Stock::factory(),
            'expected_quantity' => $this->faker->randomFloat(4, 1, 250),
            'dispatched_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'document_number' => $this->faker->optional()->bothify('SO-#####'),
            'note' => $this->faker->optional()->sentence(),
            'reference_type' => null,
            'reference_id' => null,
        ];
    }
}
