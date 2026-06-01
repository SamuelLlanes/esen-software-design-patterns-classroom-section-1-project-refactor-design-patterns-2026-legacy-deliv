<?php
namespace App\Models\Discounts;

use App\Models\Order;

/**
 * Strategy Pattern: Define la interfaz común para todas las estrategias de cálculo de descuento.
 */
interface DiscountStrategy
{
    /**
     * Calcula el monto del descuento para una orden dada.
     * 
     * @param Order $order La orden a la que se aplica el descuento
     * @param array $discountData Los datos del descuento (value, max_discount_amount, etc.)
     * @return float El monto del descuento a aplicar
     */
    public function calculate(Order $order, array $discountData): float;
}
