<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Services\EmailService;

class EmailOrderObserver implements OrderObserver
{
    public function __construct(private EmailService $emailService)
    {
    }

    public function update(Order $order, string $event): void
    {
        switch ($event) {
            case 'created':
                $this->emailService->send(
                    $order->customer->user->email,
                    'Pedido recibido',
                    "Tu pedido #{$order->id} ha sido recibido."
                );
                $this->emailService->send(
                    $order->vendor->user->email,
                    'Nuevo pedido',
                    "Tienes un nuevo pedido #{$order->id}."
                );
                break;

            case 'paid':
                $this->emailService->send(
                    $order->customer->user->email,
                    'Pago confirmado',
                    "Tu pago para el pedido #{$order->id} fue procesado."
                );
                break;

            case 'accepted':
                $this->emailService->send(
                    $order->customer->user->email,
                    'Pedido aceptado',
                    "Tu pedido #{$order->id} está siendo preparado."
                );
                break;

            case 'delivered':
                $this->emailService->send(
                    $order->customer->user->email,
                    'Pedido entregado',
                    "Tu pedido #{$order->id} fue entregado. ¡Buen provecho!"
                );
                break;

            case 'cancelled':
                $this->emailService->send(
                    $order->customer->user->email,
                    'Pedido cancelado',
                    "Tu pedido #{$order->id} fue cancelado."
                );
                break;

            case 'refunded':
                $this->emailService->send(
                    $order->customer->user->email,
                    'Reembolso procesado',
                    "El reembolso de tu pedido #{$order->id} fue procesado."
                );
                break;
        }
    }
}
