<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class DirectMessageNotification extends Notification
{
    public function __construct(
        public readonly string $message,
        public readonly string $sentBy = '',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'direct_message',
            'message' => $this->message,
            'sent_by' => $this->sentBy,
        ];
    }
}
