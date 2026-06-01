<?php

namespace App\Models\States;

use App\Models\OrderState;

class CancelledState implements OrderState
{
    public function getName(): string
    {
        return 'cancelled';
    }

    public function canTransitionTo(string $newState): bool
    {
        return false;
    }
}
