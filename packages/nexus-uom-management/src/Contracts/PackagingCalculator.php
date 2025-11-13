<?php

namespace Nexus\UomManagement\Contracts;

use Nexus\UomManagement\Models\UomPackaging;
use Nexus\UomManagement\Models\UomUnit;
use Brick\Math\BigDecimal;

interface PackagingCalculator
{
    public function resolvePackaging(UomUnit|string|int $base, UomUnit|string|int $package): UomPackaging;

    public function packagesToBase(BigDecimal|int|float|string $packages, UomPackaging|int $packaging, ?int $precision = null): BigDecimal;

    public function baseToPackages(BigDecimal|int|float|string $baseQuantity, UomPackaging|int $packaging, ?int $precision = null): BigDecimal;
}
