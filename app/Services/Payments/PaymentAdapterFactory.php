<?php

namespace App\Services\Payments;

/**
 * FACTORY PATTERN - Crea el adaptador apropiado según el proveedor
 * 
 * Trabajar con PaymentGatewayAdapter siempre. El factory decide cuál
 * proveedor concreto usar basándose en el método de pago.
 */
class PaymentAdapterFactory
{
    public static function create(string $paymentProvider): PaymentGatewayAdapter
    {
        return match (strtolower($paymentProvider)) {
            'wompi'  => new WompiPaymentAdapter(),
            'n1co'   => new N1coPaymentAdapter(),
            'bac'    => new BacPaymentAdapter(),
            default  => throw new \InvalidArgumentException("Payment provider '{$paymentProvider}' not supported"),
        };
    }

    /**
     * Retorna los proveedores disponibles.
     */
    public static function getAvailableProviders(): array
    {
        return ['wompi', 'n1co', 'bac'];
    }
}
