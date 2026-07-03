<?php

namespace App\Services\Payments;

use App\Services\Payments\PaymentGatewayAdapter;
use App\Services\Payments\BacTransferHandler;

/**
 * Adaptador concreto para BAC (Banco Agrícola Central)
 */
class BacPaymentAdapter implements PaymentGatewayAdapter
{
    private BacTransferHandler $handler;

    public function __construct()
    {
        $this->handler = new BacTransferHandler();
    }

    public function processPayment(float $amount, string $currency, array $paymentDetails): array
    {
        // BAC usa transferencias, adaptamos el método
        $result = $this->handler->initiateTransfer($amount, $paymentDetails['customer_id'] ?? 0);
        
        return [
            'success'        => $result['code'] === '00',
            'transaction_id' => $result['authorization'],
            'error'          => $result['code'] !== '00' ? 'Transfer failed at BAC' : null,
            'raw_response'   => $result,
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        // BAC requiere confirmación manual para reembolsos
        $result = $this->handler->cancelTransfer($transactionId);
        
        return [
            'success'   => $result['code'] === '00',
            'refund_id' => $transactionId . '-reversed',
            'error'     => $result['code'] !== '00' ? 'Cancellation failed at BAC' : null,
        ];
    }

    public function getTransactionStatus(string $transactionId): array
    {
        // BAC no tiene un método de consulta específico
        return [
            'status'  => 'pending',
            'details' => ['authorization' => $transactionId],
        ];
    }

    public function getProviderName(): string
    {
        return 'BAC Transfer';
    }
}
