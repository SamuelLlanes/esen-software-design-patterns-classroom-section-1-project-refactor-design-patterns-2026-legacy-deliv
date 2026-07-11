<?php

namespace App\Orders;

interface OrderItemInterface
{
    public function getTotal(): float;

    public function getName(): string;

    public function getQuantity(): int;
}
