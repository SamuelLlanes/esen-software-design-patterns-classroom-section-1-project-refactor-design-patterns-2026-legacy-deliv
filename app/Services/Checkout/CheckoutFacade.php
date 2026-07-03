<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\Customer;
use App\Services\Checkout\CustomerValidator;
use App\Services\Checkout\VendorValidator;
use App\Services\Checkout\CartProcessor;
use App\Services\Checkout\PricingCalculator;
use App\Services\Checkout\PaymentProcessor;
use Illuminate\Support\Facades\Log;

/**
 * FACADE PATTERN - Simplifica el complejo proceso de checkout
 * 
 * Problema: placeOrder() en Customer.php contiene:
 * - Validaciones del customer
 * - Validaciones del vendor y horarios
 * - Construcción de items
 * - Cálculo de subtotal
 * - Aplicación de descuentos
 * - Procesamiento de pagos
 * - Creación de la orden
 * 
 * Esto viola SRP y hace difícil testear cada parte independientemente.
 * 
 * Solución: CheckoutFacade coordina estos subsistemas y expone
 * una interfaz simple: placeOrder(customer, cart, paymentMethod).
 */
class CheckoutFacade
{
    public function __construct(
        private CustomerValidator $customerValidator,
        private VendorValidator $vendorValidator,
        private CartProcessor $cartProcessor,
        private PricingCalculator $pricingCalculator,
        private PaymentProcessor $paymentProcessor,
    ) {}

    /**
     * Orquesta todo el flujo de checkout.
     * 
     * @param Customer $customer
     * @param array $cart ['vendor_id' => int, 'items' => [...]]
     * @param string $paymentMethod 'wompi', 'n1co', 'bac'
     * @return Order
     * @throws \Exception si algo falla en cualquier etapa
     */
    public function placeOrder(Customer $customer, array $cart, string $paymentMethod): Order
    {
        Log::info("Checkout: starting for customer #{$customer->id}");

        // 1. Validar customer
        $this->customerValidator->validate($customer);

        // 2. Validar vendor y disponibilidad
        $vendor = $this->vendorValidator->validate($cart['vendor_id']);

        // 3. Procesar items del carrito
        $cartItems = $this->cartProcessor->processItems($cart['items']);

        // 4. Calcular precios
        $pricing = $this->pricingCalculator->calculate($cartItems, $vendor);

        // 5. Procesar pago
        $paymentResult = $this->paymentProcessor->process(
            $customer,
            $pricing['total'],
            $paymentMethod
        );

        if (!$paymentResult['success']) {
            Log::error("Checkout: payment failed for customer #{$customer->id}", $paymentResult);
            throw new \Exception("Payment processing failed: {$paymentResult['error']}");
        }

        // 6. Crear la orden
        $order = Order::create([
            'customer_id'     => $customer->id,
            'vendor_id'       => $vendor->id,
            'subtotal'        => $pricing['subtotal'],
            'discount_total'  => $pricing['discount_amount'],
            'delivery_fee'    => $pricing['delivery_fee'],
            'total'           => $pricing['total'],
            'status'          => 'pending',
            'delivery_address'=> $customer->address,
        ]);

        // 7. Agregar items a la orden
        foreach ($cartItems as $item) {
            $order->items()->create([
                'item_type'  => $item['type'],
                'item_id'    => $item['id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal'   => $item['subtotal'],
            ]);
        }

        Log::info("Checkout: order #{$order->id} created successfully");
        
        return $order;
    }
}
