<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Support\Logger;

class LoggerOrderObserver implements OrderObserver
{
    public function __construct(private Logger $logger)
    {
    }

    public function update(Order $order, string $event): void
    {
        $this->logger->log("Order {$order->id} event dispatched: {$event}");
    }
}
