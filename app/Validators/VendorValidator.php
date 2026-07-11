<?php

namespace App\Validators;

use App\Models\Order;

class VendorValidator extends OrderValidationHandler
{
    public function handle(Order $order): void
    {
        if (!$order->vendor) {
            throw new \Exception('Vendor not found.');
        }

        if ($order->vendor->status !== 'active') {
            throw new \Exception('Vendor is not currently active.');
        }

        $this->next($order);
    }
}
