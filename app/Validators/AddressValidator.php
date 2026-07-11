<?php

namespace App\Validators;

use App\Models\Order;

class AddressValidator extends OrderValidationHandler
{
    private const MIN_ADDRESS_LENGTH = 10;

    public function handle(Order $order): void
    {
        if (empty($order->delivery_address)) {
            throw new \Exception('Delivery address is required.');
        }

        if (strlen($order->delivery_address) < self::MIN_ADDRESS_LENGTH) {
            throw new \Exception('Delivery address is too short to be valid.');
        }

        $this->next($order);
    }
}
