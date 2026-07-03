<?php

namespace App\Services\Checkout;

use App\Models\Customer;
use App\Services\Payments\PaymentAdapterFactory;
use Illuminate\Support\Facades\Log;

/**
 * Procesa el pago usando el adaptador apropiado
 */
class PaymentProcessor
{
    public function process(Customer $customer, float $total, string $paymentMethod): array
    {
        try {
            // Crear adaptador según el proveedor
            $adapter = PaymentAdapterFactory::create($paymentMethod);

            Log::info("Payment: processing ${$total} via {$adapter->getProviderName()} for customer #{$customer->id}");

            // Procesar el pago a través del adaptador
            $result = $adapter->processPayment($total, 'USD', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->user->email,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error("Payment: error processing payment", ['error' => $e->getMessage()]);
            return [
                'success'        => false,
                'transaction_id' => null,
                'error'          => $e->getMessage(),
            ];
        }
    }
}
