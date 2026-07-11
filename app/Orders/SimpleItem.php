<?php

namespace App\Orders;

class SimpleItem implements OrderItemInterface
{
    public function __construct(
        private string $name,
        private float  $unitPrice,
        private int    $quantity,
    ) {}

    public function getTotal(): float
    {
        return $this->unitPrice * $this->quantity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }
}
