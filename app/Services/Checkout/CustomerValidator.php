<?php

namespace App\Services\Checkout;

use App\Models\Customer;

/**
 * Valida el estado del customer antes de permitir checkout
 */
class CustomerValidator
{
    public function validate(Customer $customer): void
    {
        if (!$customer->verified) {
            throw new \Exception('Customer account is not verified.');
        }
    }
}
