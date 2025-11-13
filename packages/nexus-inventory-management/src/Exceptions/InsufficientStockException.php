<?php

namespace Nexus\InventoryManagement\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(string $message, public readonly ?string $itemIdentifier = null, public readonly ?string $locationIdentifier = null)
    {
        parent::__construct($message);
    }
}
