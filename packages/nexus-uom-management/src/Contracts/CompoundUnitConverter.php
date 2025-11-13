<?php

namespace Nexus\UomManagement\Contracts;

use Nexus\UomManagement\Models\UomCompoundUnit;
use Brick\Math\BigDecimal;

interface CompoundUnitConverter
{
    public function convert(BigDecimal|int|float|string $value, UomCompoundUnit|int|string $from, UomCompoundUnit|int|string $to, ?int $precision = null): BigDecimal;
}
