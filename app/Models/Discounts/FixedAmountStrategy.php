<?php
namespace App\Models\Discounts;

use App\Models\Order;

class FixedAmountStrategy implements DiscountStrategy
{
    public function calculate(Order $order, array $discountData): float
    {
        return (float) min($discountData['value'], $order->subtotal);
    }
}
