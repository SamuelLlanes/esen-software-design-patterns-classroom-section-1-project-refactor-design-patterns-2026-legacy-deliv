<?php

namespace App\Validators;

use App\Models\Order;

class CustomerValidator extends OrderValidationHandler
{
    public function handle(Order $order): void
    {
        if (!$order->customer) {
            throw new \Exception('Customer not found.');
        }

        if (!$order->customer->verified) {
            throw new \Exception('Customer account is not verified.');
        }

        $this->next($order);
    }
}
