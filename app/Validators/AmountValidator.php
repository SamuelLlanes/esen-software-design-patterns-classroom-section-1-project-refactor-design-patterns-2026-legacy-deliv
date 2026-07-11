<?php

namespace App\Validators;

use App\Models\Order;

class AmountValidator extends OrderValidationHandler
{
    private const MIN_ORDER_AMOUNT = 5.00;

    public function handle(Order $order): void
    {
        if ($order->subtotal <= 0) {
            throw new \Exception('Order subtotal must be greater than zero.');
        }

        if ($order->total < 0) {
            throw new \Exception('Order total cannot be negative.');
        }

        if ($order->subtotal < self::MIN_ORDER_AMOUNT) {
            throw new \Exception(
                'Minimum order amount is $' . number_format(self::MIN_ORDER_AMOUNT, 2) . '.'
            );
        }

        if ($order->payment && $order->payment->status === 'failed') {
            throw new \Exception('Associated payment has failed.');
        }

        $this->next($order);
    }
}
