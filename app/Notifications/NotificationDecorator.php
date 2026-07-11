<?php

namespace App\Notifications;

use App\Models\Notification;

abstract class NotificationDecorator
{
    protected ?NotificationDecorator $inner;

    public function __construct(?NotificationDecorator $inner = null)
    {
        $this->inner = $inner;
    }

    abstract public function send(Notification $notification): bool;
}
