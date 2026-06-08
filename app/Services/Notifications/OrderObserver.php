<?php

namespace App\Services\Notifications;

use App\Models\Order;

interface OrderObserver
{
    public function update(Order $order, string $event): void;
}
