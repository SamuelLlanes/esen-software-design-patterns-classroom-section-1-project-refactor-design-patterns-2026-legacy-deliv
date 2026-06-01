<?php

namespace App\Models\Commands;

use App\Models\Order;

class PayCommand implements OrderCommand
{
    public function execute(Order $order): void
    {
        // In this simplified command we only apply the logical transition
        // and trigger notifications/side effects. Real payment provider
        // interaction should be done here or injected into the command.
        $order->transitionTo('paid');
        $order->notify('paid');
        $order->dispatchSideEffects('paid');
    }
}
