<?php

namespace App\Services;

use App\Models\Order;
use App\Validators\CustomerValidator;
use App\Validators\VendorValidator;
use App\Validators\StockValidator;
use App\Validators\AddressValidator;
use App\Validators\AmountValidator;
use App\Validators\DiscountValidator;
use App\Support\Logger;

class OrderValidationService
{
    public function validate(Order $order): void
    {
        $customer = new CustomerValidator();
        $vendor   = new VendorValidator();
        $stock    = new StockValidator();
        $address  = new AddressValidator();
        $amount   = new AmountValidator();
        $discount = new DiscountValidator();

        $customer
            ->setNext($vendor)
            ->setNext($stock)
            ->setNext($address)
            ->setNext($amount)
            ->setNext($discount);

        $customer->handle($order);

        app(Logger::class)->log("Order {$order->id} validated successfully via CoR chain.");
    }
}
