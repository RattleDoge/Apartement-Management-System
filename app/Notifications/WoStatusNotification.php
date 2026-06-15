<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Notifications\Notification;

class WoStatusNotification extends Notification
{
    public function __construct(
        public readonly WorkOrder $workOrder,
        public readonly string    $message,
        public readonly string    $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'wo_status',
            'wo_id'       => $this->workOrder->id,
            'no_wo'       => $this->workOrder->no_wo,
            'no_complain' => $this->workOrder->no_complain,
            'lot_no'      => $this->workOrder->lot_no,
            'status'      => $this->status,
            'message'     => $this->message,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
