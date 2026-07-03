<?php

namespace App\Services\Checkout;

use App\Models\Vendor;

/**
 * Calcula el precio final: subtotal, descuentos, delivery, impuestos
 */
class PricingCalculator
{
    public function calculate(array $cartItems, Vendor $vendor): array
    {
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['subtotal'];
        }

        // Aplicar descuentos (si existen)
        $discount = $this->applyDiscounts($subtotal);
        $subtotalAfterDiscount = $subtotal - $discount;

        // Calcular delivery
        $deliveryFee = $this->calculateDeliveryFee($subtotal);

        $total = $subtotalAfterDiscount + $deliveryFee;

        return [
            'subtotal'        => $subtotal,
            'discount_amount' => $discount,
            'delivery_fee'    => $deliveryFee,
            'total'           => $total,
        ];
    }

    private function applyDiscounts(float $subtotal): float
    {
        // Aquí podrías integrar DiscountStrategy del patrón anterior
        // Por ahora, retorna 0
        return 0;
    }

    private function calculateDeliveryFee(float $subtotal): float
    {
        // Tarifa fija de $2.50 (puede convertirse en Strategy)
        return 2.50;
    }
}
