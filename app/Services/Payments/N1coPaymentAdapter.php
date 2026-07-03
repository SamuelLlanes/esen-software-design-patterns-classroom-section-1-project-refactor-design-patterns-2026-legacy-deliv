<?php

namespace App\Services\Payments;

use App\Services\Payments\PaymentGatewayAdapter;
use App\Services\Payments\N1coHandler;

/**
 * Adaptador concreto para N1co
 */
class N1coPaymentAdapter implements PaymentGatewayAdapter
{
    private N1coHandler $handler;

    public function __construct()
    {
        $this->handler = new N1coHandler();
    }

    public function processPayment(float $amount, string $currency, array $paymentDetails): array
    {
        // Adaptamos los métodos de N1co a la interfaz común
        $result = $this->handler->makePayment([
            'amount'     => (int)($amount * 100),
            'currency'   => $currency,
            'order_ref'  => $paymentDetails['customer_id'] ?? '',
        ]);
        
        return [
            'success'        => $result['status'] === 'success',
            'transaction_id' => $result['payment_id'],
            'error'          => $result['status'] !== 'success' ? 'Payment failed at N1co' : null,
            'raw_response'   => $result,
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        $result = $this->handler->reversePayment($transactionId, (int)($amount * 100));
        
        return [
            'success'   => $result['status'] === 'reversed',
            'refund_id' => $result['reversal_id'],
            'error'     => $result['status'] !== 'reversed' ? 'Refund failed at N1co' : null,
        ];
    }

    public function getTransactionStatus(string $transactionId): array
    {
        $result = $this->handler->getPaymentStatus($transactionId);
        
        $statusMap = [
            'success' => 'approved',
            'pending' => 'pending',
            'failed'  => 'failed',
        ];
        
        return [
            'status'  => $statusMap[$result['status']] ?? 'unknown',
            'details' => $result,
        ];
    }

    public function getProviderName(): string
    {
        return 'N1co';
    }
}
