<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Services\SMSService;

class SmsOrderObserver implements OrderObserver
{
    public function __construct(private SMSService $smsService)
    {
    }

    public function update(Order $order, string $event): void
    {
        if ($event === 'created' && $order->customer->user->phone) {
            $this->smsService->send(
                $order->customer->user->phone,
                "Pedido #{$order->id} confirmado."
            );
            return;
        }

        if ($event === 'picked_up' && $order->customer->user->phone) {
            $this->smsService->send(
                $order->customer->user->phone,
                "Tu pedido #{$order->id} está en camino."
            );
        }
    }
}
