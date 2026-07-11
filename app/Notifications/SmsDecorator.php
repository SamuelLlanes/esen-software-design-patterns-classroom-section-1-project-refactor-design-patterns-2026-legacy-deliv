<?php

namespace App\Notifications;

use App\Models\Notification;
use App\Services\SMSService;

class SmsDecorator extends NotificationDecorator
{
    public function __construct(
        NotificationDecorator $inner,
        private SMSService $smsService,
    ) {
        parent::__construct($inner);
    }

    public function send(Notification $notification): bool
    {
        $result = $this->inner->send($notification);

        $phone = $this->getRecipientPhone($notification);

        if ($phone === '') {
            return $result;
        }

        try {
            $content = $notification->content;
            $this->smsService->send($phone, $content);
        } catch (\Exception) {
            $result = false;
        }

        return $result;
    }

    private function getRecipientPhone(Notification $notification): string
    {
        if ($notification->recipient_type === 'customer') {
            $recipient = \App\Models\Customer::find($notification->recipient_id);
        } elseif ($notification->recipient_type === 'vendor') {
            $recipient = \App\Models\Vendor::find($notification->recipient_id);
        } elseif ($notification->recipient_type === 'courier') {
            $recipient = \App\Models\Courier::find($notification->recipient_id);
        } else {
            return '';
        }

        return $recipient?->user?->phone ?? '';
    }
}
