<?php

namespace App\Models\States;

use App\Models\OrderState;

class DeliveredState implements OrderState
{
    public function getName(): string
    {
        return 'delivered';
    }

    public function canTransitionTo(string $newState): bool
    {
        return in_array($newState, ['refunded', 'cancelled']);
    }
}
