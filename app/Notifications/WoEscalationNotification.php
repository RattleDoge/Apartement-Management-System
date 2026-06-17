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
        public readonly string $context = 'unassigned', // 'unassigned' | 'not_started'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $levelLabel = $this->level === 1 ? 'Supervisor / Chief' : 'Manager / General Manager';
        [$subject, $body] = $this->buildMessages();

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Yth. {$notifiable->name},")
            ->line($body)
            ->line('')
            ->line("**No WO :** {$this->workOrder->no_wo}")
            ->line("**No Complain :** {$this->workOrder->no_complain}")
            ->line("**Lot No :** {$this->workOrder->lot_no}")
            ->line("**Nama :** {$this->workOrder->name}")
            ->line("**Deskripsi :** {$this->workOrder->descs}")
            ->line("**Tanggal Masuk :** {$this->workOrder->tanggal->format('d/m/Y H:i:s')}")
            ->line("**Assign :** " . ($this->workOrder->assign_staff ?? '-'))
            ->line("**Status :** {$this->workOrder->status_comp}")
            ->action('Buka Work Order', url('/karyawan/cs/work-order'))
            ->line("Notifikasi ini dikirim secara otomatis kepada {$levelLabel}.")
            ->salutation('Salam,  ' . config('app.name') . ' — Madison Park');
    }

    public function toDatabase(object $notifiable): array
    {
        [$subject, , $shortMsg] = $this->buildMessages();

        return [
            'wo_id'    => $this->workOrder->id,
            'no_wo'    => $this->workOrder->no_wo,
            'lot_no'   => $this->workOrder->lot_no,
            'name'     => $this->workOrder->name,
            'descs'    => $this->workOrder->descs,
            'level'    => $this->level,
            'context'  => $this->context,
            'elapsed'  => $this->elapsedMinutes,
            'message'  => $shortMsg,
        ];
    }

    /** Returns [subject, body, shortMessage] */
    private function buildMessages(): array
    {
        $wo = $this->workOrder->no_wo;
        $lot = $this->workOrder->lot_no;
        $min = $this->elapsedMinutes;
        $icon = $this->level === 1 ? '⚠️' : '🚨';

        if ($this->context === 'not_started') {
            // WO sudah di-assign tapi teknisi belum mulai kerja
            $subject  = "{$icon} WO Belum Dikerjakan ({$min} menit): {$wo}";
            $body     = $this->level === 1
                ? "Work Order berikut sudah di-assign ke **{$this->workOrder->assign_staff}** namun belum mulai dikerjakan selama **{$min} menit**."
                : "🚨 **ESKALASI**: WO {$wo} sudah di-assign ke **{$this->workOrder->assign_staff}** namun belum dikerjakan selama **{$min} menit**. Mohon segera ditindaklanjuti.";
            $shortMsg = $this->level === 1
                ? "{$icon} WO {$wo} (Lot {$lot}) ditugaskan ke {$this->workOrder->assign_staff} tapi belum dikerjakan — {$min} menit"
                : "🚨 ESKALASI: WO {$wo} (Lot {$lot}) masih belum dikerjakan — {$min} menit";
        } else {
            // WO belum ada assign staff sama sekali
            $subject  = "{$icon} WO Belum Ditangani ({$min} menit): {$wo}";
            $body     = $this->level === 1
                ? "Work Order berikut belum ada teknisi yang ditugaskan selama **{$min} menit** (batas respon 15 menit)."
                : "🚨 **ESKALASI**: WO {$wo} masih belum ada teknisi yang ditugaskan selama **{$min} menit**. Mohon segera ditindaklanjuti.";
            $shortMsg = $this->level === 1
                ? "{$icon} WO {$wo} (Lot {$lot}) belum ada teknisi — {$min} menit"
                : "🚨 ESKALASI: WO {$wo} (Lot {$lot}) masih belum ditangani — {$min} menit";
        }

        return [$subject, $body, $shortMsg];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
