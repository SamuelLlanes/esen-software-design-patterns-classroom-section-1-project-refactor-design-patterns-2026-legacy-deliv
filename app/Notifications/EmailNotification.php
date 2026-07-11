<?php

namespace App\Notifications;

use App\Models\Notification;
use App\Services\EmailService;

class EmailNotification extends NotificationDecorator
{
    public function __construct(private EmailService $emailService)
    {
        parent::__construct(null);
    }

    public function send(Notification $notification): bool
    {
        $email = $this->getRecipientEmail($notification);

        if ($email === '') {
            return false;
        }

        try {
            $this->emailService->send(
                $email,
                $notification->subject,
                $notification->content,
            );
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function getRecipientEmail(Notification $notification): string
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

        return $recipient?->user?->email ?? '';
    }
}
