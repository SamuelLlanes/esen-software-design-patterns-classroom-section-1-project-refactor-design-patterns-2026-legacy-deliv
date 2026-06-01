<?php

namespace App\Models\States;

use App\Models\OrderState;

class PendingState implements OrderState
{
    public function getName(): string
    {
        return 'created';
    }

    public function canTransitionTo(string $newState): bool
    {
        return in_array($newState, ['paid', 'cancelled']);
    }
}
