<?php

namespace Nexus\UomManagement\Tests\Unit\Models;

use Nexus\UomManagement\Models\UomAlias;
use Nexus\UomManagement\Models\UomType;
use Nexus\UomManagement\Models\UomUnit;
use Nexus\UomManagement\Models\UomUnitGroup;
use Nexus\UomManagement\Tests\TestCase;

class UomUnitTest extends TestCase
{
    public function test_factory_creates_unit_with_relationships(): void
    {
    $type = UomType::factory()->create(['slug' => 'test-type']);
        $group = UomUnitGroup::factory()->create();

        $unit = UomUnit::factory()->for($type, 'type')->create(['code' => 'UNITX']);
        $alias = UomAlias::factory()->for($unit, 'unit')->create(['alias' => 'unitx-alt']);
        $group->units()->attach($unit->getKey());

        $unit->refresh();

        $this->assertTrue($unit->type->is($type));
        $this->assertTrue($unit->aliases->contains($alias));
        $this->assertTrue($unit->groups->contains($group));
    }
}
