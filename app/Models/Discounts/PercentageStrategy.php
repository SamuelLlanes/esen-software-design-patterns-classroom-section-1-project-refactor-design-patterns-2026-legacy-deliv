<?php
namespace App\Models\Discounts;

use App\Models\Order;

class PercentageStrategy implements DiscountStrategy
{
    public function calculate(Order $order, array $discountData): float
    {
        $discount = $order->subtotal * ($discountData['value'] / 100);
        
        if (isset($discountData['max_discount_amount']) && $discountData['max_discount_amount'] !== null) {
            $discount = min($discount, $discountData['max_discount_amount']);
        }
        
        return (float) $discount;
    }
}
