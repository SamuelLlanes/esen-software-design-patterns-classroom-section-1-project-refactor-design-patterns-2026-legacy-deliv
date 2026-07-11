<?php

namespace App\Notifications;

use App\Models\Notification;
use App\Services\PushService;

class PushDecorator extends NotificationDecorator
{
    public function __construct(
        NotificationDecorator $inner,
        private PushService $pushService,
    ) {
        parent::__construct($inner);
    }

    public function send(Notification $notification): bool
    {
        $result = $this->inner->send($notification);

        try {
            $this->pushService->send(
                $notification->recipient_id,
                $notification->subject,
                $notification->content,
            );
        } catch (\Exception) {
            $result = false;
        }

        return $result;
    }
}
