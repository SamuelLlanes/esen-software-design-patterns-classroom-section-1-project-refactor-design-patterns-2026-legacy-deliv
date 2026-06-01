<?php
namespace App\Models\Discounts;

use App\Models\Order;

class FreeDeliveryStrategy implements DiscountStrategy
{
    public function calculate(Order $order, array $discountData): float
    {
        return (float) $order->delivery_fee;
    }
}
