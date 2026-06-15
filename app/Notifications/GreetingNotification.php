<?php

namespace App\Notifications;

use App\Models\GreetingTemplate;
use Illuminate\Notifications\Notification;

class GreetingNotification extends Notification
{
    public function __construct(
        public readonly GreetingTemplate $template,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'greeting',
            'template_id'    => $this->template->id,
            'nama_template'  => $this->template->nama_template,
            'jenis'          => $this->template->jenis,
            'isi'            => strip_tags($this->template->isi ?? ''),
            'cover_img'      => $this->template->cover_img,
            'message'        => "[{$this->template->jenis}] {$this->template->nama_template}",
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
