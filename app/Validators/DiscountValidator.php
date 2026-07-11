<?php

namespace App\Validators;

use App\Models\Order;

class DiscountValidator extends OrderValidationHandler
{
    public function handle(Order $order): void
    {
        foreach ($order->discounts as $discount) {
            if (now() > $discount->valid_to) {
                throw new \Exception("Discount '{$discount->code}' has expired.");
            }

            if ($discount->vendor_id && $discount->vendor_id !== $order->vendor_id) {
                throw new \Exception(
                    "Discount '{$discount->code}' is not valid for this vendor."
                );
            }
        }

        $this->next($order);
    }
}
