<?php

namespace Nexus\InventoryManagement\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface Location
{
    /**
     * Get the stocks for the location.
     */
    public function stocks(): HasMany;

    /**
     * Get the name of the location.
     */
    public function getLocationName(): string;
}
