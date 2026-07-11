<?php

namespace App\Validators;

use App\Models\Order;

abstract class OrderValidationHandler
{
    private ?OrderValidationHandler $next = null;

    public function setNext(OrderValidationHandler $next): static
    {
        $this->next = $next;
        return $next;
    }

    abstract public function handle(Order $order): void;

    protected function next(Order $order): void
    {
        $this->next?->handle($order);
    }
}
