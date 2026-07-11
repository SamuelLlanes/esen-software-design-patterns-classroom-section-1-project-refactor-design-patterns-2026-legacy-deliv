<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Notifications\EmailNotification;
use App\Notifications\SmsDecorator;
use App\Notifications\PushDecorator;
use App\Services\EmailService;
use App\Services\SMSService;
use App\Services\PushService;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['recipient_id', 'recipient_type', 'channel', 'subject', 'content',
                           'is_encrypted', 'is_logged', 'is_signed', 'sent', 'sent_at', 'attempts'];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_logged'    => 'boolean',
        'is_signed'    => 'boolean',
        'sent'         => 'boolean',
        'sent_at'      => 'datetime',
    ];

    public function prepareContent(): string
    {
        $content = $this->content;

        if ($this->is_encrypted && $this->is_signed && $this->is_logged) {
            $content = $this->encryptContent($content);
            $content = $this->addSignature($content);
            $this->doLog($content);
        } elseif ($this->is_encrypted && $this->is_signed) {
            $content = $this->encryptContent($content);
            $content = $this->addSignature($content);
        } elseif ($this->is_encrypted && $this->is_logged) {
            $content = $this->encryptContent($content);
            $this->doLog($content);
        } elseif ($this->is_signed && $this->is_logged) {
            $content = $this->addSignature($content);
            $this->doLog($content);
        } elseif ($this->is_encrypted) {
            $content = $this->encryptContent($content);
        } elseif ($this->is_signed) {
            $content = $this->addSignature($content);
        } elseif ($this->is_logged) {
            $this->doLog($content);
        }

        return $content;
    }

    public function send(): bool
    {
        $this->attempts++;

        $this->content = $this->prepareContent();
        $chain = $this->buildDecoratorChain();

        try {
            $result = $chain->send($this);

            $this->sent    = true;
            $this->sent_at = now();
            $this->save();

            return $result;

        } catch (\Exception $e) {
            $this->save();
            return false;
        }
    }

    private function buildDecoratorChain(): \App\Notifications\NotificationDecorator
    {
        $chain = new EmailNotification(new EmailService());

        if (in_array($this->channel, ['sms', 'whatsapp', null], true)) {
            $chain = new SmsDecorator($chain, new SMSService());
        }

        if (in_array($this->channel, ['push', null], true)) {
            $chain = new PushDecorator($chain, new PushService());
        }

        return $chain;
    }

    private function getRecipientEmail(): string
    {
        $recipient = $this->getRecipient();
        return $recipient?->user?->email ?? '';
    }

    private function getRecipientPhone(): string
    {
        $recipient = $this->getRecipient();
        return $recipient?->user?->phone ?? '';
    }

    private function getRecipient()
    {
        if ($this->recipient_type === 'customer') {
            return Customer::find($this->recipient_id);
        } elseif ($this->recipient_type === 'vendor') {
            return Vendor::find($this->recipient_id);
        } elseif ($this->recipient_type === 'courier') {
            return Courier::find($this->recipient_id);
        }
        return null;
    }

    private function encryptContent(string $content): string
    {
        return base64_encode($content);
    }

    private function addSignature(string $content): string
    {
        return $content . "\n\n-- Sistema Legacy Delivery";
    }

    private function doLog(string $content): void
    {
        app(\App\Support\Logger::class)->log(
            "Notification [{$this->channel}] to {$this->recipient_type}:{$this->recipient_id} - " .
            substr($content, 0, 60)
        );
    }
}
