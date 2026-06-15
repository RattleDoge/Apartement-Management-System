<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WoEscalationNotification extends Notification
{
    public function __construct(
        public readonly WorkOrder $workOrder,
        public readonly int $level,           // 1 = Supervisor/Chief, 2 = Manager/GM
        public readonly int $elapsedMinutes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $levelLabel = $this->level === 1
            ? 'Supervisor / Chief'
            : 'Manager / General Manager';

        $subject = $this->level === 1
            ? "⚠️ WO Belum Ditangani ({$this->elapsedMinutes} menit): {$this->workOrder->no_wo}"
            : "🚨 ESKALASI WO Belum Ditangani ({$this->elapsedMinutes} menit): {$this->workOrder->no_wo}";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Yth. {$notifiable->name},")
            ->line(
                $this->level === 1
                    ? "Work Order berikut belum ada teknisi yang ditugaskan selama **{$this->elapsedMinutes} menit** (batas respon 15 menit)."
                    : "⚠️ **ESKALASI**: Work Order berikut masih belum ditangani selama **{$this->elapsedMinutes} menit**. Mohon segera ditindaklanjuti."
            )
            ->line('')
            ->line("**No WO :** {$this->workOrder->no_wo}")
            ->line("**No Complain :** {$this->workOrder->no_complain}")
            ->line("**Lot No :** {$this->workOrder->lot_no}")
            ->line("**Nama :** {$this->workOrder->name}")
            ->line("**Deskripsi :** {$this->workOrder->descs}")
            ->line("**Tanggal Masuk :** {$this->workOrder->tanggal->format('d/m/Y H:i:s')}")
            ->line("**Status :** {$this->workOrder->status_comp}")
            ->action('Buka Work Order', url('/karyawan/cs/work-order'))
            ->line("Notifikasi ini dikirim secara otomatis kepada {$levelLabel}.")
            ->salutation('Salam,  ' . config('app.name') . ' — Madison Park');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'wo_id'    => $this->workOrder->id,
            'no_wo'    => $this->workOrder->no_wo,
            'lot_no'   => $this->workOrder->lot_no,
            'name'     => $this->workOrder->name,
            'descs'    => $this->workOrder->descs,
            'level'    => $this->level,
            'elapsed'  => $this->elapsedMinutes,
            'message'  => $this->level === 1
                ? "WO {$this->workOrder->no_wo} (Lot {$this->workOrder->lot_no}) belum ada teknisi — {$this->elapsedMinutes} menit"
                : "🚨 ESKALASI: WO {$this->workOrder->no_wo} masih belum ditangani — {$this->elapsedMinutes} menit",
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
