<?php
namespace App\Models\Discounts;

use App\Models\Order;

class FirstPurchaseStrategy implements DiscountStrategy
{
    public function calculate(Order $order, array $discountData): float
    {
        // Verifica si es la primera compra del customer
        $previousOrders = Order::where('customer_id', $order->customer_id)
            ->whereIn('status', ['delivered', 'paid', 'accepted', 'preparing', 'ready', 'picked_up'])
            ->where('id', '!=', $order->id)
            ->count();

        if ($previousOrders > 0) {
            return 0.0;
        }

        $discount = $order->subtotal * ($discountData['value'] / 100);
        
        if (isset($discountData['max_discount_amount']) && $discountData['max_discount_amount'] !== null) {
            $discount = min($discount, $discountData['max_discount_amount']);
        }
        
        return (float) $discount;
    }
}
