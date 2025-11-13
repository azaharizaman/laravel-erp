<?php

namespace Nexus\UomManagement\Contracts;

use Nexus\UomManagement\Models\UomUnit;

interface AliasResolver
{
    public function resolve(string $identifier): ?UomUnit;

    public function resolveOrFail(string $identifier): UomUnit;

    /**
     * @return array<int, string>
     */
    public function aliasesFor(UomUnit|string $unit, bool $includeCode = true): array;
}
