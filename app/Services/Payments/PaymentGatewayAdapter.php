<?php

namespace App\Services\Payments;

/**
 * ADAPTER PATTERN - Interfaz común para todas las pasarelas de pago
 * 
 * Problema: Cada proveedor (Wompi, N1co, Bac) tiene métodos con nombres y
 * parámetros distintos. Esto viola Liskov Substitution Principle.
 * 
 * Solución: El adapter unififica la interfaz. Los clientes no necesitan
 * saber si trabajan con Wompi, N1co, o Bac — todos cumplen el mismo contrato.
 */
interface PaymentGatewayAdapter
{
    /**
     * Procesa un pago.
     * 
     * @param float $amount Monto en formato decimal
     * @param string $currency Código de moneda (USD, MXN, etc.)
     * @param array $paymentDetails Datos específicos del pago (ref, tarjeta, etc.)
     * @return array ['success' => bool, 'transaction_id' => string, 'error' => string?]
     */
    public function processPayment(float $amount, string $currency, array $paymentDetails): array;

    /**
     * Reembolsa un pago anterior.
     * 
     * @param string $transactionId ID de la transacción original
     * @param float $amount Monto a reembolsar
     * @return array ['success' => bool, 'refund_id' => string, 'error' => string?]
     */
    public function refund(string $transactionId, float $amount): array;

    /**
     * Consulta el estado de una transacción.
     * 
     * @param string $transactionId ID de la transacción
     * @return array ['status' => 'approved'|'pending'|'failed', 'details' => array]
     */
    public function getTransactionStatus(string $transactionId): array;

    /**
     * Retorna el nombre del adaptador (para logs y debugging).
     */
    public function getProviderName(): string;
}
