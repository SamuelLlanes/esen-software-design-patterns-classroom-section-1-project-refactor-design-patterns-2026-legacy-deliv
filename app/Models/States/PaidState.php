<?php

namespace App\Models\States;

use App\Models\OrderState;

class PaidState implements OrderState
{
    public function getName(): string
    {
        return 'paid';
    }

    public function canTransitionTo(string $newState): bool
    {
        return in_array($newState, ['accepted', 'cancelled', 'refunded']);
    }
}
