<?php
namespace App\Models\Discounts;

use App\Models\Order;

class BogoStrategy implements DiscountStrategy
{
    public function calculate(Order $order, array $discountData): float
    {
        // Buy one get one: devuelve el precio del item más barato de la orden
        $cheapestPrice = $order->items
            ->map(fn($item) => $item->unit_price)
            ->sort()
            ->values()
            ->first();
        
        return (float) ($cheapestPrice ?? 0.0);
    }
}
