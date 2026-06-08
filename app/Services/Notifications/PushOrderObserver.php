<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Services\PushService;

class PushOrderObserver implements OrderObserver
{
    public function __construct(private PushService $pushService)
    {
    }

    public function update(Order $order, string $event): void
    {
        switch ($event) {
            case 'paid':
                $this->pushService->send(
                    $order->customer->user->id,
                    'Pago recibido',
                    'Tu pago fue procesado exitosamente.'
                );
                break;

            case 'accepted':
                $this->pushService->send(
                    $order->customer->user->id,
                    'Pedido aceptado',
                    'El restaurante aceptó tu pedido.'
                );
                break;

            case 'preparing':
                $this->pushService->send(
                    $order->customer->user->id,
                    'Preparando tu pedido',
                    'Tu comida está siendo preparada.'
                );
                break;

            case 'ready':
                if ($order->courier) {
                    $this->pushService->send(
                        $order->courier->user->id,
                        'Pedido listo para recoger',
                        "El pedido #{$order->id} está listo."
                    );
                }
                break;

            case 'picked_up':
                $this->pushService->send(
                    $order->customer->user->id,
                    'Pedido en camino',
                    '¡Tu pedido está en camino!'
                );
                break;

            case 'delivered':
                $this->pushService->send(
                    $order->customer->user->id,
                    '¡Pedido entregado!',
                    '¡Disfruta tu pedido!'
                );
                break;
        }
    }
}
