<?php

namespace App\Services\Notifications;

use App\Models\Order;

class OrderNotificationDispatcher
{
    /**
     * @var OrderObserver[]
     */
    private array $observers = [];

    public function __construct(array $observers = [])
    {
        $this->observers = $observers;
    }

    public function attach(OrderObserver $observer): void
    {
        $this->observers[] = $observer;
    }

    public function detach(OrderObserver $observer): void
    {
        $this->observers = array_filter(
            $this->observers,
            fn(OrderObserver $item) => $item !== $observer
        );
    }

    public function dispatch(Order $order, string $event): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($order, $event);
        }
    }
}
