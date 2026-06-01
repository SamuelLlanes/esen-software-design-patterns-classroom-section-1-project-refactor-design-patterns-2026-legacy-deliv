<?php

namespace App\Models\Commands;

use App\Models\Order;

class CancelCommand implements OrderCommand
{
    public function execute(Order $order): void
    {
        $order->transitionTo('cancelled');
        $order->notify('cancelled');
        $order->dispatchSideEffects('cancelled');
    }
}
