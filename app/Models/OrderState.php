<?php

namespace App\Models;

interface OrderState
{
    /** Return the canonical name of the state */
    public function getName(): string;

    /** Whether this state allows transition to the given state name */
    public function canTransitionTo(string $newState): bool;
}
