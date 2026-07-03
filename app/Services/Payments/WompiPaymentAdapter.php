<?php

namespace App\Services\Payments;

use App\Services\Payments\PaymentGatewayAdapter;
use App\Services\Payments\WompiHandler;

/**
 * Adaptador concreto para Wompi
 * 
 * Mapea los métodos de WompiHandler (cobrar, reembolsar, consultarEstado)
 * a la interfaz común PaymentGatewayAdapter.
 */
class WompiPaymentAdapter implements PaymentGatewayAdapter
{
    private WompiHandler $handler;

    public function __construct()
    {
        $this->handler = new WompiHandler();
    }

    public function processPayment(float $amount, string $currency, array $paymentDetails): array
    {
        // Mapea la interfaz común a los métodos de Wompi
        $result = $this->handler->cobrar($amount, $currency, $paymentDetails);
        
        return [
            'success'        => $result['estado'] === 'APROBADO',
            'transaction_id' => $result['id_transaccion'],
            'error'          => $result['estado'] !== 'APROBADO' ? 'Payment declined by Wompi' : null,
            'raw_response'   => $result,
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        $result = $this->handler->reembolsar($transactionId, $amount);
        
        return [
            'success'   => $result['estado'] === 'PROCESADO',
            'refund_id' => $result['id_reembolso'],
            'error'     => $result['estado'] !== 'PROCESADO' ? 'Refund failed at Wompi' : null,
        ];
    }

    public function getTransactionStatus(string $transactionId): array
    {
        $result = $this->handler->consultarEstado($transactionId);
        
        $statusMap = [
            'APROBADO' => 'approved',
            'PENDIENTE' => 'pending',
            'RECHAZADO' => 'failed',
        ];
        
        return [
            'status'  => $statusMap[$result['estado']] ?? 'unknown',
            'details' => $result,
        ];
    }

    public function getProviderName(): string
    {
        return 'Wompi';
    }
}
