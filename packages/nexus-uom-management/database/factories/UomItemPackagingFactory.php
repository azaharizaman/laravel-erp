<?php

namespace Nexus\UomManagement\Database\Factories;

use Nexus\UomManagement\Models\UomItem;
use Nexus\UomManagement\Models\UomItemPackaging;
use Nexus\UomManagement\Models\UomPackaging;
use Illuminate\Database\Eloquent\Factories\Factory;

class UomItemPackagingFactory extends Factory
{
    protected $model = UomItemPackaging::class;

    public function definition(): array
    {
        return [
            'item_id' => UomItem::factory(),
            'packaging_id' => UomPackaging::factory(),
        ];
    }
}
